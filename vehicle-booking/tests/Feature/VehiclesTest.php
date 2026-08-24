<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class VehiclesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'name'              => 'Truck Test ' . uniqid(),
            'license_plate'     => 'B ' . random_int(1000, 9999) . ' TEST',
            'type'              => 'angkutan_barang',
            'ownership'         => 'milik_perusahaan',
            'fuel_consumption'  => 8.5,
            'service_schedule'  => '2026-12-01',
            'image_url'         => 'https://example.com/truck.jpg',
        ], $overrides);
    }

    // ---- index() ----

    public function testIndexReturnsEmptyArrayWhenNoVehicles(): void
    {
        $result = $this->get('api/vehicles');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame([], $body['data']);
    }

    public function testIndexReturnsVehiclesOrderedByNewestFirst(): void
    {
        $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload(['name' => 'Kendaraan A']));
        $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload(['name' => 'Kendaraan B']));

        $result = $this->get('api/vehicles');
        $rows   = json_decode($result->getJSON(), true)['data'];

        $this->assertCount(2, $rows);
        $this->assertSame('Kendaraan B', $rows[0]['name']);
    }

    // ---- create() ----

    public function testCreateFailsWhenNameMissing(): void
    {
        $payload = $this->vehiclePayload();
        unset($payload['name']);

        $result = $this->withBodyFormat('json')->post('api/vehicles', $payload);
        $result->assertStatus(400);
    }

    public function testCreateFailsWhenLicensePlateMissing(): void
    {
        $payload = $this->vehiclePayload();
        unset($payload['license_plate']);

        $result = $this->withBodyFormat('json')->post('api/vehicles', $payload);
        $result->assertStatus(400);
    }

    public function testCreateFailsWhenTypeMissing(): void
    {
        $payload = $this->vehiclePayload();
        unset($payload['type']);

        $result = $this->withBodyFormat('json')->post('api/vehicles', $payload);
        $result->assertStatus(400);
    }

    public function testCreateFailsWhenOwnershipMissing(): void
    {
        $payload = $this->vehiclePayload();
        unset($payload['ownership']);

        $result = $this->withBodyFormat('json')->post('api/vehicles', $payload);
        $result->assertStatus(400);
    }

    public function testCreateSucceedsWithAllFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload([
            'name' => 'Toyota Hilux Test',
        ]));

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('vehicles', [
            'id'   => $id,
            'name' => 'Toyota Hilux Test',
        ]);
    }

    public function testCreateSucceedsWithoutOptionalFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/vehicles', [
            'name'          => 'Kendaraan Minimal',
            'license_plate' => 'B 1 MINIMAL',
            'type'          => 'angkutan_orang',
            'ownership'     => 'sewa',
        ]);

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('vehicles', [
            'id'               => $id,
            'fuel_consumption' => null,
            'service_schedule' => null,
            'image_url'        => null,
        ]);
    }

    public function testCreateLogsActivity(): void
    {
        $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload(['name' => 'Kendaraan Log Test']));

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'create_vehicle')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertStringContainsString('Kendaraan Log Test', $log['description']);
    }

    // ---- update() ----

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->put('api/vehicles/999999', [
            'name' => 'Apapun',
        ]);

        $result->assertStatus(404);
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $create = $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload([
            'name'             => 'Nama Asli',
            'fuel_consumption' => 10.0,
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/vehicles/{$id}", [
            'name' => 'Nama Sudah Diubah',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicles', [
            'id'               => $id,
            'name'             => 'Nama Sudah Diubah',
            'fuel_consumption' => 10.0,
        ]);
    }

    public function testUpdateCanChangeImageUrl(): void
    {
        $create = $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload([
            'image_url' => 'https://example.com/lama.jpg',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/vehicles/{$id}", [
            'image_url' => 'https://example.com/baru.jpg',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicles', [
            'id'        => $id,
            'image_url' => 'https://example.com/baru.jpg',
        ]);
    }

    // ---- delete() ----

    public function testDeleteReturnsNotFoundForUnknownId(): void
    {
        $result = $this->delete('api/vehicles/999999');
        $result->assertStatus(404);
    }

    public function testDeleteRemovesVehicle(): void
    {
        $create = $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload([
            'name' => 'Kendaraan Akan Dihapus',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->delete("api/vehicles/{$id}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('vehicles', ['id' => $id]);
    }

    public function testDeleteLogsActivityWithVehicleName(): void
    {
        $create = $this->withBodyFormat('json')->post('api/vehicles', $this->vehiclePayload([
            'name' => 'Kendaraan Untuk Log Hapus',
        ]));
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $this->delete("api/vehicles/{$id}");

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'delete_vehicle')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertStringContainsString('Kendaraan Untuk Log Hapus', $log['description']);
    }
}