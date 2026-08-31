<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesBookingFixtures;

final class BookingsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CreatesBookingFixtures;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $vehicleId;
    private int $requesterId;
    private int $approver1Id;
    private int $approver2Id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requesterId = $this->createUser('admin');
        $this->approver1Id = $this->createUser('approver', 1);
        $this->approver2Id = $this->createUser('approver', 2);
        $this->vehicleId   = $this->createVehicle();
    }

    private function createBooking(array $overrides = []): int
    {
        $result = $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload($overrides));

        return json_decode($result->getJSON(), true)['data']['id'];
    }

    private function approvalIds(int $bookingId): array
    {
        $db = \Config\Database::connect();

        $level1 = (int) $db->table('booking_approvals')
            ->where('booking_id', $bookingId)->where('level', 1)
            ->get()->getRowArray()['id'];

        $level2 = (int) $db->table('booking_approvals')
            ->where('booking_id', $bookingId)->where('level', 2)
            ->get()->getRowArray()['id'];

        return [$level1, $level2];
    }

    // ---- index() ----

    public function testIndexReturnsJoinedVehicleAndRequesterData(): void
    {
        $this->createBooking();

        $result = $this->get('api/bookings');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('vehicle_name', $rows[0]);
        $this->assertArrayHasKey('requester_name', $rows[0]);
        $this->assertArrayHasKey('fuel_log', $rows[0]);
        $this->assertArrayHasKey('rejection_reason', $rows[0]);
    }

    public function testIndexIncludesRejectionReasonAfterReject(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/reject", [
            'notes' => 'Kendaraan rusak',
        ]);

        $result = $this->get('api/bookings');
        $rows   = json_decode($result->getJSON(), true)['data'];

        $row = current(array_filter($rows, fn ($r) => (int) $r['id'] === $bookingId));
        $this->assertSame('Kendaraan rusak', $row['rejection_reason']);
    }

    // ---- show() ----

    public function testShowReturnsNotFoundForUnknownId(): void
    {
        $result = $this->get('api/bookings/999999');
        $result->assertStatus(404);
    }

    public function testShowIncludesApprovalsAndVehicleData(): void
    {
        $bookingId = $this->createBooking();

        $result = $this->get("api/bookings/{$bookingId}");
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true)['data'];
        $this->assertArrayHasKey('vehicle_name', $body);
        $this->assertArrayHasKey('approvals', $body);
        $this->assertCount(2, $body['approvals']);
        $this->assertNull($body['rejection_reason']);
        $this->assertNull($body['fuel_log']);
    }

    public function testShowIncludesFuelLogAfterComplete(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);
        $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => 500,
            'odometer_end'   => 650,
        ]);

        $result = $this->get("api/bookings/{$bookingId}");
        $body   = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($body['fuel_log']);
        $this->assertSame(500, (int) $body['fuel_log']['odometer_start']);
    }

    // ---- lastOdometer() ----

    public function testLastOdometerIsNullWhenNoHistory(): void
    {
        $result = $this->get("api/vehicles/{$this->vehicleId}/last-odometer");
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true)['data'];
        $this->assertNull($body['last_odometer']);
    }

    public function testLastOdometerReturnsLatestValueAfterComplete(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);
        $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => 500,
            'odometer_end'   => 650,
        ]);

        $result = $this->get("api/vehicles/{$this->vehicleId}/last-odometer");
        $body   = json_decode($result->getJSON(), true)['data'];

        $this->assertSame(650, (int) $body['last_odometer']);
    }

    // ---- create() validasi ----

    public function testCreateFailsWhenRequiredFieldMissing(): void
    {
        $payload = $this->bookingPayload();
        unset($payload['vehicle_id']);

        $result = $this->withBodyFormat('json')->post('api/bookings', $payload);
        $result->assertStatus(400);
    }

    public function testCreateFailsWhenEndDateBeforeStartDate(): void
    {
        $payload = $this->bookingPayload([
            'start_date' => '2026-09-01 17:00:00',
            'end_date'   => '2026-09-01 08:00:00',
        ]);

        $result = $this->withBodyFormat('json')->post('api/bookings', $payload);
        $result->assertStatus(400);
    }

    public function testCreateSucceedsAndPersistsTwoApprovalRows(): void
    {
        $bookingId = $this->createBooking();

        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $bookingId,
            'status' => 'pending',
        ]);

        $db    = \Config\Database::connect();
        $count = $db->table('booking_approvals')->where('booking_id', $bookingId)->countAllResults();
        $this->assertSame(2, $count);
    }

    // ---- update() ----

    public function testUpdateSucceedsWhilePending(): void
    {
        $bookingId = $this->createBooking();

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'destination' => 'Site B (diperbarui)',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'          => $bookingId,
            'destination' => 'Site B (diperbarui)',
        ]);
    }

    public function testUpdateFailsAfterBookingIsApproved(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'destination' => 'Percobaan ubah setelah disetujui',
        ]);

        $result->assertStatus(400);
    }

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->put('api/bookings/999999', [
            'destination' => 'Apapun',
        ]);

        $result->assertStatus(404);
    }

    // ---- update() bentrok jadwal ----

    public function testUpdateFailsWhenNewDateConflictsWithAnotherBooking(): void
    {
        // Booking A tetap di jadwal aslinya
        $this->createBooking([
            'start_date' => '2026-10-05 08:00:00',
            'end_date'   => '2026-10-05 17:00:00',
        ]);

        // Booking B awalnya di jadwal lain (tidak bentrok)
        $bookingBId = $this->createBooking([
            'start_date' => '2026-10-10 08:00:00',
            'end_date'   => '2026-10-10 17:00:00',
        ]);

        // Coba pindahkan booking B ke jadwal yang sama persis dengan booking A
        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingBId}", [
            'start_date' => '2026-10-05 08:00:00',
            'end_date'   => '2026-10-05 17:00:00',
        ]);

        $result->assertStatus(409);
    }

    public function testUpdateFailsWhenEndDateBeforeStartDate(): void
    {
        $bookingId = $this->createBooking();

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'start_date' => '2026-11-01 17:00:00',
            'end_date'   => '2026-11-01 08:00:00',
        ]);

        $result->assertStatus(400);
    }

    // ---- complete() ----

    public function testCompleteFailsForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->post('api/bookings/999999/complete', []);
        $result->assertStatus(404);
    }

    public function testCompleteFailsWhenStatusNotApprovedL2(): void
    {
        // Booking baru dibuat, masih status pending, belum lewat approval sama sekali
        $bookingId = $this->createBooking();

        $result = $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => 500,
            'odometer_end'   => 650,
        ]);

        $result->assertStatus(400);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $bookingId,
            'status' => 'pending',
        ]);
    }

    public function testCompleteFailsWhenOdometerEndLessThanStart(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);

        $result = $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => 650,
            'odometer_end'   => 500,
        ]);

        $result->assertStatus(400);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $bookingId,
            'status' => 'approved_l2',
        ]);
    }

    public function testCompleteSucceedsWithoutOptionalFuelData(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);

        // Tidak mengirim odometer_start/odometer_end/fuel_liters/notes sama sekali
        $result = $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", []);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $bookingId,
            'status' => 'completed',
        ]);
        $this->seeInDatabase('fuel_logs', [
            'booking_id'     => $bookingId,
            'odometer_start' => null,
            'odometer_end'   => null,
        ]);
    }

    public function testCompleteLogsActivityWithBookingCode(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);

        $db   = \Config\Database::connect();
        $code = $db->table('vehicle_bookings')->where('id', $bookingId)->get()->getRowArray()['booking_code'];

        $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => 100,
            'odometer_end'   => 200,
        ]);

        $log = $db->table('activity_logs')
            ->where('action', 'complete_booking')
            ->like('description', $code)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($log, 'Activity log untuk complete booking harus tercatat');
    }

    // ---- delete() ----

    public function testDeleteSucceedsWhilePending(): void
    {
        $bookingId = $this->createBooking();

        $result = $this->delete("api/bookings/{$bookingId}");
        $result->assertStatus(200);

        $this->dontSeeInDatabase('vehicle_bookings', ['id' => $bookingId]);
    }

    public function testDeleteFailsAfterBookingIsApproved(): void
    {
        $bookingId = $this->createBooking();
        [$level1Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);

        $result = $this->delete("api/bookings/{$bookingId}");
        $result->assertStatus(400);

        $this->seeInDatabase('vehicle_bookings', ['id' => $bookingId]);
    }

    public function testDeleteReturnsNotFoundForUnknownId(): void
    {
        $result = $this->delete('api/bookings/999999');
        $result->assertStatus(404);
    }
}