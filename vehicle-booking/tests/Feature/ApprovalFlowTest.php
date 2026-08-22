<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesBookingFixtures;

final class ApprovalFlowTest extends CIUnitTestCase
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
    private int $bookingId;
    private int $approvalL1Id;
    private int $approvalL2Id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requesterId = $this->createUser('admin');
        $this->approver1Id = $this->createUser('approver', 1);
        $this->approver2Id = $this->createUser('approver', 2);
        $this->vehicleId   = $this->createVehicle();

        $create = $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload());

        $this->bookingId = json_decode($create->getJSON(), true)['data']['id'];

        $db = \Config\Database::connect();

        $this->approvalL1Id = (int) $db->table('booking_approvals')
            ->where('booking_id', $this->bookingId)->where('level', 1)
            ->get()->getRowArray()['id'];

        $this->approvalL2Id = (int) $db->table('booking_approvals')
            ->where('booking_id', $this->bookingId)->where('level', 2)
            ->get()->getRowArray()['id'];
    }

    public function testLevel1IsActionableImmediately(): void
    {
        $result = $this->get("api/approvals?approver_id={$this->approver1Id}");
        $rows   = json_decode($result->getJSON(), true)['data'];

        $row = $this->findApprovalRow($rows, $this->approvalL1Id);
        $this->assertTrue($row['actionable']);
    }

    public function testLevel2IsNotActionableBeforeLevel1Approved(): void
    {
        $result = $this->get("api/approvals?approver_id={$this->approver2Id}");
        $rows   = json_decode($result->getJSON(), true)['data'];

        $row = $this->findApprovalRow($rows, $this->approvalL2Id);
        $this->assertFalse($row['actionable']);
    }

    public function testApprovingLevel2BeforeLevel1IsRejected(): void
    {
        $result = $this->withBodyFormat('json')
            ->post("api/approvals/{$this->approvalL2Id}/approve", []);

        $result->assertStatus(400);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'pending',
        ]);
    }

    public function testApprovingLevel1MovesBookingToApprovedL1(): void
    {
        $result = $this->withBodyFormat('json')
            ->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'approved_l1',
        ]);
        $this->seeInDatabase('booking_approvals', [
            'id'     => $this->approvalL1Id,
            'status' => 'approved',
        ]);
    }

    public function testLevel2BecomesActionableAfterLevel1Approved(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $result = $this->get("api/approvals?approver_id={$this->approver2Id}");
        $rows   = json_decode($result->getJSON(), true)['data'];

        $row = $this->findApprovalRow($rows, $this->approvalL2Id);
        $this->assertTrue($row['actionable']);
    }

    public function testApprovingLevel2AfterLevel1MovesBookingToApprovedL2(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $result = $this->withBodyFormat('json')
            ->post("api/approvals/{$this->approvalL2Id}/approve", []);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'approved_l2',
        ]);
    }

    public function testRejectingLevel1StopsTheFlow(): void
    {
        $result = $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/reject", [
            'notes' => 'Kendaraan sedang diperbaiki',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'rejected',
        ]);
        $this->seeInDatabase('booking_approvals', [
            'id'     => $this->approvalL1Id,
            'status' => 'rejected',
            'notes'  => 'Kendaraan sedang diperbaiki',
        ]);
    }

    public function testCompleteFailsBeforeFullyApproved(): void
    {
        $result = $this->withBodyFormat('json')->post("api/bookings/{$this->bookingId}/complete", []);

        $result->assertStatus(400);
    }

    public function testCompleteSucceedsAfterApprovedL2(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL2Id}/approve", []);

        $result = $this->withBodyFormat('json')->post("api/bookings/{$this->bookingId}/complete", [
            'odometer_start' => 1000,
            'odometer_end'   => 1250,
            'fuel_liters'    => 40,
            'notes'          => 'Kondisi baik',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'completed',
        ]);
        $this->seeInDatabase('fuel_logs', [
            'booking_id'     => $this->bookingId,
            'odometer_start' => 1000,
            'odometer_end'   => 1250,
        ]);
    }

    public function testCompleteRejectsOdometerEndLessThanStart(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL2Id}/approve", []);

        $result = $this->withBodyFormat('json')->post("api/bookings/{$this->bookingId}/complete", [
            'odometer_start' => 1000,
            'odometer_end'   => 900,
        ]);

        $result->assertStatus(400);
    }

    private function findApprovalRow(array $rows, int $approvalId): array
    {
        foreach ($rows as $row) {
            if ((int) $row['id'] === $approvalId) {
                return $row;
            }
        }

        $this->fail("Approval ID {$approvalId} tidak ditemukan di response");
    }
}