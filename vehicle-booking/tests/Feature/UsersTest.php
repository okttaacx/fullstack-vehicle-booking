<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class UsersTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'User Test ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'password' => 'secret123',
            'role'     => 'admin',
        ], $overrides);
    }

    // ---- index() ----

    public function testIndexNeverReturnsPasswordField(): void
    {
        $this->withBodyFormat('json')->post('api/users', $this->userPayload());

        $result = $this->get('api/users');
        $rows   = json_decode($result->getJSON(), true)['data'];

        $this->assertArrayNotHasKey('password', $rows[0]);
    }

    public function testIndexFiltersByRole(): void
    {
        $this->withBodyFormat('json')->post('api/users', $this->userPayload(['role' => 'admin']));
        $this->withBodyFormat('json')->post('api/users', $this->userPayload(['role' => 'approver', 'level' => 1]));

        $result = $this->get('api/users?role=approver');
        $rows   = json_decode($result->getJSON(), true)['data'];

        $this->assertCount(1, $rows);
        $this->assertSame('approver', $rows[0]['role']);
    }

    // ---- create() ----

    public function testCreateFailsWhenRequiredFieldMissing(): void
    {
        $payload = $this->userPayload();
        unset($payload['username']);

        $result = $this->withBodyFormat('json')->post('api/users', $payload);
        $result->assertStatus(400);
    }

    public function testCreateFailsWithDuplicateUsername(): void
    {
        $payload = $this->userPayload(['username' => 'user_duplikat']);

        $this->withBodyFormat('json')->post('api/users', $payload);
        $result = $this->withBodyFormat('json')->post('api/users', $payload);

        $result->assertStatus(409);
    }

    public function testCreateHashesPasswordProperly(): void
    {
        $result = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'username' => 'user_hash_test',
            'password' => 'plaintext123',
        ]));

        $id = json_decode($result->getJSON(), true)['data']['id'];

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();

        $this->assertNotSame('plaintext123', $user['password']);
        $this->assertTrue(password_verify('plaintext123', $user['password']));
    }

    public function testCreateApproverStoresLevel(): void
    {
        $result = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'role'  => 'approver',
            'level' => 2,
        ]));

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('users', [
            'id'    => $id,
            'role'  => 'approver',
            'level' => 2,
        ]);
    }

    public function testCreateAdminIgnoresLevelEvenIfProvided(): void
    {
        $result = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'role'  => 'admin',
            'level' => 1,
        ]));

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('users', [
            'id'    => $id,
            'role'  => 'admin',
            'level' => null,
        ]);
    }

    // ---- update() ----

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->put('api/users/999999', [
            'name' => 'Apapun',
        ]);

        $result->assertStatus(404);
    }

    public function testUpdateFailsWhenNewUsernameAlreadyTaken(): void
    {
        $this->withBodyFormat('json')->post('api/users', $this->userPayload(['username' => 'sudah_dipakai']));

        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload(['username' => 'akan_diubah']));
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/users/{$id}", [
            'username' => 'sudah_dipakai',
        ]);

        $result->assertStatus(409);
    }

    public function testUpdateAllowsKeepingSameUsername(): void
    {
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload(['username' => 'username_sendiri']));
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/users/{$id}", [
            'username' => 'username_sendiri',
            'name'     => 'Nama Diubah',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('users', ['id' => $id, 'name' => 'Nama Diubah']);
    }

    public function testUpdateWithoutPasswordKeepsOldPassword(): void
    {
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'username' => 'user_keep_password',
            'password' => 'passwordlama123',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $this->withBodyFormat('json')->put("api/users/{$id}", [
            'name' => 'Nama Baru Saja',
        ]);

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();

        $this->assertTrue(password_verify('passwordlama123', $user['password']));
    }

    public function testUpdateWithPasswordChangesIt(): void
    {
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'username' => 'user_change_password',
            'password' => 'passwordlama123',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $this->withBodyFormat('json')->put("api/users/{$id}", [
            'password' => 'passwordbaru456',
        ]);

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();

        $this->assertTrue(password_verify('passwordbaru456', $user['password']));
    }

    public function testUpdateRoleFromApproverToAdminClearsLevel(): void
    {
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'role'  => 'approver',
            'level' => 1,
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $this->withBodyFormat('json')->put("api/users/{$id}", [
            'role' => 'admin',
        ]);

        $this->seeInDatabase('users', ['id' => $id, 'role' => 'admin', 'level' => null]);
    }

    // ---- delete() ----

    public function testDeleteReturnsNotFoundForUnknownId(): void
    {
        $result = $this->delete('api/users/999999');
        $result->assertStatus(404);
    }

    public function testDeleteFailsForLastRemainingAdmin(): void
    {
        // Pastikan tidak ada admin lain yang "nyantol" dari test sebelumnya,
        // supaya skenario ini benar-benar menguji kondisi "admin terakhir".
        $db = \Config\Database::connect();
        $db->table('users')->where('role', 'admin')->delete();

        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload(['role' => 'admin']));
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->delete("api/users/{$id}");
        $result->assertStatus(400);

        $this->seeInDatabase('users', ['id' => $id]);
    }

    public function testDeleteSucceedsWhenAnotherAdminExists(): void
    {
        $this->withBodyFormat('json')->post('api/users', $this->userPayload(['role' => 'admin']));
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload(['role' => 'admin']));
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->delete("api/users/{$id}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('users', ['id' => $id]);
    }

    public function testDeleteSucceedsForApprover(): void
    {
        $create = $this->withBodyFormat('json')->post('api/users', $this->userPayload([
            'role'  => 'approver',
            'level' => 1,
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->delete("api/users/{$id}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('users', ['id' => $id]);
    }
}