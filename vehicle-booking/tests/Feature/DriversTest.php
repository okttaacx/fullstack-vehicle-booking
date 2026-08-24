<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class DriversTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private function driverPayload(array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Driver Test ' . uniqid(),
            'phone'          => '08123456789',
            'license_number' => 'SIM-' . random_int(100000, 999999),
            'license_expiry' => '2027-12-31',
            'status'         => 'active',
        ], $overrides);
    }

    // ---- index() ----

    public function testIndexReturnsEmptyArrayWhenNoDrivers(): void
    {
        $result = $this->get('api/drivers');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame([], $body['data']);
    }

    public function testIndexReturnsDriversOrderedByName(): void
    {
        $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload(['name' => 'Zaki']));
        $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload(['name' => 'Agus']));

        $result = $this->get('api/drivers');
        $rows   = json_decode($result->getJSON(), true)['data'];

        $this->assertCount(2, $rows);
        $this->assertSame('Agus', $rows[0]['name']);
    }

    // ---- create() ----

    public function testCreateFailsWhenNameMissing(): void
    {
        $payload = $this->driverPayload();
        unset($payload['name']);

        $result = $this->withBodyFormat('json')->post('api/drivers', $payload);
        $result->assertStatus(400);
    }

    public function testCreateSucceedsWithAllFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload([
            'name' => 'Budi Santoso',
        ]));

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('drivers', [
            'id'   => $id,
            'name' => 'Budi Santoso',
        ]);
    }

    public function testCreateSucceedsWithOnlyName(): void
    {
        $result = $this->withBodyFormat('json')->post('api/drivers', [
            'name' => 'Driver Minimal',
        ]);

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('drivers', [
            'id'             => $id,
            'phone'          => null,
            'license_number' => null,
            'license_expiry' => null,
            'status'         => 'active',
        ]);
    }

    public function testCreateLogsActivity(): void
    {
        $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload(['name' => 'Driver Log Test']));

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'create_driver')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertStringContainsString('Driver Log Test', $log['description']);
    }

    // ---- update() ----

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->put('api/drivers/999999', [
            'name' => 'Apapun',
        ]);

        $result->assertStatus(404);
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $create = $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload([
            'name'  => 'Nama Asli',
            'phone' => '08111111111',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/drivers/{$id}", [
            'name' => 'Nama Diubah',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('drivers', [
            'id'    => $id,
            'name'  => 'Nama Diubah',
            'phone' => '08111111111',
        ]);
    }

    public function testUpdateCanClearLicenseExpiryExplicitly(): void
    {
        $create = $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload([
            'license_expiry' => '2027-01-01',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        // Kirim license_expiry = null secara eksplisit (bukan sekadar tidak mengirim field-nya)
        $result = $this->withBodyFormat('json')->put("api/drivers/{$id}", [
            'license_expiry' => null,
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('drivers', [
            'id'             => $id,
            'license_expiry' => null,
        ]);
    }

    // ---- delete() ----

    public function testDeleteReturnsNotFoundForUnknownId(): void
    {
        $result = $this->delete('api/drivers/999999');
        $result->assertStatus(404);
    }

    public function testDeleteRemovesDriver(): void
    {
        $create = $this->withBodyFormat('json')->post('api/drivers', $this->driverPayload([
            'name' => 'Driver Akan Dihapus',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->delete("api/drivers/{$id}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('drivers', ['id' => $id]);
    }
}