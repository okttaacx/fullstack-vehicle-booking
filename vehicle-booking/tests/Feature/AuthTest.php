<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesBookingFixtures;

final class AuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CreatesBookingFixtures;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $userId;
    private string $username;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Paksa gunakan Cache 'file' khusus untuk test ini 
        // Mengabaikan config phpunit.xml yang pakai 'dummy' agar Throttler berjalan sungguhan
        $cacheConfig = config('Cache');
        $cacheConfig->handler = 'file';
        $realCache = \CodeIgniter\Cache\CacheFactory::getHandler($cacheConfig);
        \Config\Services::injectMock('cache', $realCache);

        // 2. Bersihkan cache sungguhan di awal setiap test, supaya sisa hitungan rate limit
        // dari test method sebelumnya benar-benar terhapus
        $realCache->clean();

        $db = \Config\Database::connect();

        $this->username = 'auth_test_' . uniqid();

        $db->table('users')->insert([
            'name'     => 'Auth Test User',
            'username' => $this->username,
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'role'     => 'admin',
            'level'    => null,
        ]);

        $this->userId = (int) $db->insertID();
    }

    // ---- Login ----

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => $this->username,
            'password' => 'secret123',
        ]);

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('Login Berhasil', $body['message']);
        $this->assertSame($this->userId, $body['data']['id']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => $this->username,
            'password' => 'password_salah',
        ]);

        $result->assertStatus(401);
    }

    public function testLoginFailsWithUnknownUsername(): void
    {
        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => 'user_tidak_ada_' . uniqid(),
            'password' => 'apapun',
        ]);

        $result->assertStatus(401);
    }

    public function testLoginFailsWithMissingFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => $this->username,
        ]);

        $result->assertStatus(400);
    }

    public function testLoginIsRateLimitedAfterTooManyFailedAttempts(): void
    {
        $payload = [
            'username' => $this->username,
            'password' => 'password_salah',
        ];

        // 5 percobaan pertama: gagal karena password salah (401)
        for ($i = 0; $i < 5; $i++) {
            $result = $this->withBodyFormat('json')->post('api/login', $payload);
            $result->assertStatus(401);
        }

        // Percobaan ke-6: seharusnya sudah kena limit (429),
        // bukan lagi soal password salah
        $result = $this->withBodyFormat('json')->post('api/login', $payload);
        $result->assertStatus(429);
    }

    public function testLoginStillWorksForDifferentUserWhileOneIsRateLimited(): void
    {
        // Catatan: dalam feature test, seluruh request "datang" dari IP yang sama,
        // jadi rate limit login di-throttle per-IP akan mempengaruhi SEMUA username,
        // bukan cuma satu akun. Test ini mendokumentasikan perilaku tersebut secara
        // eksplisit sebagai referensi ke depan (bukan celah yang perlu diperbaiki
        // sekarang, karena tujuannya memang mencegah brute-force dari satu sumber IP).
        $payload = [
            'username' => $this->username,
            'password' => 'password_salah',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->withBodyFormat('json')->post('api/login', $payload);
        }

        $anotherUsername = 'auth_test_other_' . uniqid();
        $db = \Config\Database::connect();
        $db->table('users')->insert([
            'name'     => 'Another User',
            'username' => $anotherUsername,
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'role'     => 'admin',
            'level'    => null,
        ]);

        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => $anotherUsername,
            'password' => 'secret123',
        ]);

        $result->assertStatus(429);
    }

    // ---- Change Password ----

    public function testChangePasswordSucceedsWithCorrectOldPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/change-password', [
            'user_id'      => $this->userId,
            'old_password' => 'secret123',
            'new_password' => 'passwordbaru123',
        ]);

        $result->assertStatus(200);

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $this->userId)->get()->getRowArray();

        $this->assertTrue(password_verify('passwordbaru123', $user['password']));
    }

    public function testChangePasswordFailsWithWrongOldPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/change-password', [
            'user_id'      => $this->userId,
            'old_password' => 'password_salah',
            'new_password' => 'passwordbaru123',
        ]);

        $result->assertStatus(400);

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $this->userId)->get()->getRowArray();

        // Password lama harus tetap utuh, tidak berubah
        $this->assertTrue(password_verify('secret123', $user['password']));
    }

    public function testChangePasswordFailsWithShortNewPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/change-password', [
            'user_id'      => $this->userId,
            'old_password' => 'secret123',
            'new_password' => '123',
        ]);

        $result->assertStatus(400);
    }

    public function testChangePasswordFailsWithMissingFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/change-password', [
            'user_id'      => $this->userId,
            'old_password' => 'secret123',
        ]);

        $result->assertStatus(400);
    }

    public function testChangePasswordFailsForNonexistentUser(): void
    {
        $result = $this->withBodyFormat('json')->post('api/auth/change-password', [
            'user_id'      => 999999,
            'old_password' => 'secret123',
            'new_password' => 'passwordbaru123',
        ]);

        $result->assertStatus(404);
    }

    public function testChangePasswordIsRateLimitedAfterTooManyAttempts(): void
    {
        $payload = [
            'user_id'      => $this->userId,
            'old_password' => 'password_salah',
            'new_password' => 'passwordbaru123',
        ];

        for ($i = 0; $i < 5; $i++) {
            $result = $this->withBodyFormat('json')->post('api/auth/change-password', $payload);
            $result->assertStatus(400);
        }

        $result = $this->withBodyFormat('json')->post('api/auth/change-password', $payload);
        $result->assertStatus(429);
    }
}