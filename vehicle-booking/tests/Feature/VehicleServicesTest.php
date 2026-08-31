<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class VehicleServicesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    // ---- helpers ----

    private function seedVehicle(array $overrides = []): int
    {
        $db = \Config\Database::connect();

        $data = array_merge([
            'name'              => 'Vehicle Test ' . uniqid(),
            'license_plate'     => 'B ' . random_int(1000, 9999) . ' TEST',
            'type'              => 'angkutan_barang',
            'ownership'         => 'milik_perusahaan',
            'fuel_consumption'  => 8.5,
            'service_schedule'  => null,
            'image_url'         => null,
        ], $overrides);

        $db->table('vehicles')->insert($data);

        return (int) $db->insertID();
    }

    private function seedService(int $vehicleId, array $overrides = []): int
    {
        $db = \Config\Database::connect();

        $data = array_merge([
            'vehicle_id'   => $vehicleId,
            'service_date' => '2026-01-01',
            'description'  => 'Service Test ' . uniqid(),
            'status'       => 'scheduled',
            'created_at'   => date('Y-m-d H:i:s'),
        ], $overrides);

        $db->table('vehicle_service_schedule')->insert($data);

        return (int) $db->insertID();
    }

    private function findByDescription(array $rows, string $description): ?array
    {
        foreach ($rows as $row) {
            if ($row['description'] === $description) {
                return $row;
            }
        }

        return null;
    }

    // ---- index($vehicleId) ----

    public function testIndexReturnsOnlyServicesForGivenVehicle(): void
    {
        $marker    = uniqid();
        $vehicleA  = $this->seedVehicle();
        $vehicleB  = $this->seedVehicle();

        $this->seedService($vehicleA, ['description' => "Punya A {$marker}"]);
        $this->seedService($vehicleB, ['description' => "Punya B {$marker}"]);

        $result = $this->get("api/vehicles/{$vehicleA}/services");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Punya A {$marker}"), 'Service milik vehicle A harus muncul');
        $this->assertNull($this->findByDescription($rows, "Punya B {$marker}"), 'Service milik vehicle B tidak boleh ikut muncul');
    }

    public function testIndexReturnsEmptyArrayForVehicleWithNoServices(): void
    {
        $vehicleId = $this->seedVehicle();

        $result = $this->get("api/vehicles/{$vehicleId}/services");
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame([], $body['data']);
    }

    public function testIndexOrdersByServiceDateDescending(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();

        $this->seedService($vehicleId, ['description' => "Lama {$marker}", 'service_date' => '2026-01-01']);
        $this->seedService($vehicleId, ['description' => "Baru {$marker}", 'service_date' => '2026-03-01']);

        $result = $this->get("api/vehicles/{$vehicleId}/services");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertCount(2, $rows);
        $this->assertSame("Baru {$marker}", $rows[0]['description']);
        $this->assertSame("Lama {$marker}", $rows[1]['description']);
    }

    // ---- upcoming() ----

    public function testUpcomingOnlyReturnsScheduledStatus(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();

        $this->seedService($vehicleId, ['description' => "Terjadwal {$marker}", 'status' => 'scheduled']);
        $this->seedService($vehicleId, ['description' => "Selesai {$marker}", 'status' => 'done']);
        $this->seedService($vehicleId, ['description' => "Batal {$marker}", 'status' => 'cancelled']);

        $result = $this->get('api/vehicle-services/upcoming');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Terjadwal {$marker}"), 'Service berstatus scheduled harus muncul');
        $this->assertNull($this->findByDescription($rows, "Selesai {$marker}"), 'Service berstatus done tidak boleh muncul');
        $this->assertNull($this->findByDescription($rows, "Batal {$marker}"), 'Service berstatus cancelled tidak boleh muncul');
    }

    public function testUpcomingOrdersByServiceDateAscending(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();

        $this->seedService($vehicleId, ['description' => "Nanti {$marker}", 'status' => 'scheduled', 'service_date' => '2026-06-01']);
        $this->seedService($vehicleId, ['description' => "Duluan {$marker}", 'status' => 'scheduled', 'service_date' => '2026-05-01']);

        $result = $this->get('api/vehicle-services/upcoming');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $descriptions = array_column($rows, 'description');
        $posDuluan = array_search("Duluan {$marker}", $descriptions, true);
        $posNanti  = array_search("Nanti {$marker}", $descriptions, true);

        $this->assertNotFalse($posDuluan);
        $this->assertNotFalse($posNanti);
        $this->assertLessThan($posNanti, $posDuluan, 'Service dengan tanggal lebih awal harus muncul lebih dulu');
    }

    public function testUpcomingIncludesVehicleNameAndLicensePlate(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle([
            'name'          => "Truk Upcoming {$marker}",
            'license_plate' => "B 9 UPC{$marker}",
        ]);

        $this->seedService($vehicleId, [
            'description' => "Cek join {$marker}",
            'status'      => 'scheduled',
        ]);

        $result = $this->get('api/vehicle-services/upcoming');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $row  = $this->findByDescription($rows, "Cek join {$marker}");

        $this->assertNotNull($row);
        $this->assertSame("Truk Upcoming {$marker}", $row['vehicle_name']);
        $this->assertSame("B 9 UPC{$marker}", $row['license_plate']);
    }

    // ---- create() ----

    public function testCreateFailsWhenVehicleIdMissing(): void
    {
        $result = $this->withBodyFormat('json')->post('api/vehicle-services', [
            'service_date' => '2026-01-01',
        ]);

        $result->assertStatus(400);
    }

    public function testCreateFailsWhenServiceDateMissing(): void
    {
        $vehicleId = $this->seedVehicle();

        $result = $this->withBodyFormat('json')->post('api/vehicle-services', [
            'vehicle_id' => $vehicleId,
        ]);

        $result->assertStatus(400);
    }

    public function testCreateFailsWhenVehicleNotFound(): void
    {
        $result = $this->withBodyFormat('json')->post('api/vehicle-services', [
            'vehicle_id'   => 9999999,
            'service_date' => '2026-01-01',
        ]);

        $result->assertStatus(404);
    }

    public function testCreateSucceedsWithRequiredFieldsOnly(): void
    {
        $vehicleId = $this->seedVehicle();

        $result = $this->withBodyFormat('json')->post('api/vehicle-services', [
            'vehicle_id'   => $vehicleId,
            'service_date' => '2026-04-01',
        ]);

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('vehicle_service_schedule', [
            'id'           => $id,
            'vehicle_id'   => $vehicleId,
            'service_date' => '2026-04-01',
            'status'       => 'scheduled',
            'description'  => null,
        ]);
    }

    public function testCreateSucceedsWithAllFields(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();

        $result = $this->withBodyFormat('json')->post('api/vehicle-services', [
            'vehicle_id'   => $vehicleId,
            'service_date' => '2026-04-15',
            'description'  => "Ganti oli {$marker}",
            'status'       => 'done',
        ]);

        $result->assertStatus(201);

        $id = json_decode($result->getJSON(), true)['data']['id'];
        $this->seeInDatabase('vehicle_service_schedule', [
            'id'          => $id,
            'description' => "Ganti oli {$marker}",
            'status'      => 'done',
        ]);
    }

    public function testCreateLogsActivityWithVehicleName(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle(['name' => "Truk Log Service {$marker}"]);

        $this->withBodyFormat('json')->post('api/vehicle-services', [
            'vehicle_id'   => $vehicleId,
            'service_date' => '2026-04-01',
        ]);

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'create_service_log')
            ->like('description', "Truk Log Service {$marker}")
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($log, 'Activity log untuk create service harus tercatat');
    }

    // ---- update($id) ----

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->put('api/vehicle-services/9999999', [
            'status' => 'done',
        ]);

        $result->assertStatus(404);
    }

    public function testUpdateFailsWhenStatusInvalid(): void
    {
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId);

        $result = $this->withBodyFormat('json')->put("api/vehicle-services/{$serviceId}", [
            'status' => 'status_ngasal',
        ]);

        $result->assertStatus(400);
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId, [
            'description'  => "Deskripsi asli {$marker}",
            'service_date' => '2026-01-01',
            'status'       => 'scheduled',
        ]);

        $result = $this->withBodyFormat('json')->put("api/vehicle-services/{$serviceId}", [
            'status' => 'done',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_service_schedule', [
            'id'           => $serviceId,
            'status'       => 'done',
            'description'  => "Deskripsi asli {$marker}",
            'service_date' => '2026-01-01',
        ]);
    }

    public function testUpdateCanChangeServiceDateAndDescription(): void
    {
        $marker    = uniqid();
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId);

        $result = $this->withBodyFormat('json')->put("api/vehicle-services/{$serviceId}", [
            'service_date' => '2026-07-01',
            'description'  => "Deskripsi baru {$marker}",
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_service_schedule', [
            'id'           => $serviceId,
            'service_date' => '2026-07-01',
            'description'  => "Deskripsi baru {$marker}",
        ]);
    }

    public function testUpdateLogsActivity(): void
    {
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId);

        $this->withBodyFormat('json')->put("api/vehicle-services/{$serviceId}", [
            'status' => 'done',
        ]);

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'update_service_log')
            ->like('description', "ID {$serviceId}")
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($log, 'Activity log untuk update service harus tercatat');
    }

    // ---- delete($id) ----

    public function testDeleteReturnsNotFoundForUnknownId(): void
    {
        $result = $this->delete('api/vehicle-services/9999999');
        $result->assertStatus(404);
    }

    public function testDeleteRemovesRecord(): void
    {
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId);

        $result = $this->delete("api/vehicle-services/{$serviceId}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('vehicle_service_schedule', ['id' => $serviceId]);
    }

    public function testDeleteLogsActivity(): void
    {
        $vehicleId = $this->seedVehicle();
        $serviceId = $this->seedService($vehicleId);

        $this->delete("api/vehicle-services/{$serviceId}");

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'delete_service_log')
            ->like('description', "ID {$serviceId}")
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($log, 'Activity log untuk delete service harus tercatat');
    }
}