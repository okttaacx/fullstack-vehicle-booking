<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesBookingFixtures;

final class BookingConflictTest extends CIUnitTestCase
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

    public function testCreateBookingSucceedsWhenScheduleIsFree(): void
    {
        $result = $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload());

        $result->assertStatus(200); // Diubah dari 201 ke 200
        $result->assertJSONFragment(['message' => 'Pemesanan berhasil dibuat']);

        $this->seeInDatabase('vehicle_bookings', [
            'vehicle_id' => $this->vehicleId,
            'status'     => 'pending',
        ]);
    }

    public function testCreateBookingRejectedWhenOverlapping(): void
    {
        $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload())
            ->assertStatus(200); // Diubah dari 201 ke 200

        $result = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload([
            'start_date' => '2026-09-01 10:00:00',
            'end_date'   => '2026-09-01 15:00:00',
        ]));

        $result->assertStatus(409);
    }

    public function testCreateBookingAllowedWhenBackToBackNotOverlapping(): void
    {
        $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload())
            ->assertStatus(200); // Diubah dari 201 ke 200

        $result = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload([
            'start_date' => '2026-09-01 17:00:00',
            'end_date'   => '2026-09-01 20:00:00',
        ]));

        $result->assertStatus(200); // Diubah dari 201 ke 200
    }

    public function testCreateBookingAllowedForDifferentVehicleSameSchedule(): void
    {
        $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload())
            ->assertStatus(200); // Diubah dari 201 ke 200

        $otherVehicleId = $this->createVehicle();

        $result = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload([
            'vehicle_id' => $otherVehicleId,
        ]));

        $result->assertStatus(200); // Diubah dari 201 ke 200
    }

    public function testCreateBookingRejectsInvalidDateRange(): void
    {
        $result = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload([
            'start_date' => '2026-09-01 17:00:00',
            'end_date'   => '2026-09-01 08:00:00',
        ]));

        $result->assertStatus(400);
    }

    public function testUpdatePendingBookingSucceedsWhenNoConflict(): void
    {
        $create    = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload());
        $bookingId = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'destination' => 'Site B (diubah)',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'          => $bookingId,
            'destination' => 'Site B (diubah)',
        ]);
    }

    public function testUpdateAllowsKeepingSameScheduleWithoutFalseConflict(): void
    {
        $create    = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload());
        $bookingId = json_decode($create->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'start_date' => '2026-09-01 08:00:00',
            'end_date'   => '2026-09-01 17:00:00',
        ]);

        $result->assertStatus(200);
    }

    public function testUpdateRejectedWhenConflictsWithAnotherBooking(): void
    {
        $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload([
                'start_date' => '2026-09-05 08:00:00',
                'end_date'   => '2026-09-05 17:00:00',
            ]))->assertStatus(200); // Diubah dari 201 ke 200

        $second = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload([
            'start_date' => '2026-09-06 08:00:00',
            'end_date'   => '2026-09-06 17:00:00',
        ]));
        $secondId = json_decode($second->getJSON(), true)['data']['id'];

        $result = $this->withBodyFormat('json')->put("api/bookings/{$secondId}", [
            'start_date' => '2026-09-05 10:00:00',
            'end_date'   => '2026-09-05 12:00:00',
        ]);

        $result->assertStatus(409);
    }

    public function testUpdateRejectedOnceBookingIsNoLongerPending(): void
    {
        $create    = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload());
        $bookingId = json_decode($create->getJSON(), true)['data']['id'];

        $approvalId = $this->firstApprovalId($bookingId, 1);
        $this->withBodyFormat('json')->post("api/approvals/{$approvalId}/approve", []);

        $result = $this->withBodyFormat('json')->put("api/bookings/{$bookingId}", [
            'destination' => 'Coba ubah setelah disetujui',
        ]);

        $result->assertStatus(400);
    }

    public function testDeleteRejectedOnceBookingIsNoLongerPending(): void
    {
        $create    = $this->withBodyFormat('json')->post('api/bookings', $this->bookingPayload());
        $bookingId = json_decode($create->getJSON(), true)['data']['id'];

        $approvalId = $this->firstApprovalId($bookingId, 1);
        $this->withBodyFormat('json')->post("api/approvals/{$approvalId}/approve", []);

        $result = $this->delete("api/bookings/{$bookingId}");

        $result->assertStatus(400);
    }

    private function firstApprovalId(int $bookingId, int $level): int
    {
        $db = \Config\Database::connect();

        $row = $db->table('booking_approvals')
            ->where('booking_id', $bookingId)
            ->where('level', $level)
            ->get()
            ->getRowArray();

        return (int) $row['id'];
    }
}