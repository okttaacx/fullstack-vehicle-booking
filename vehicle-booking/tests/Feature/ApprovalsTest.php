<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesBookingFixtures;

final class ApprovalsTest extends CIUnitTestCase
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

    // ---- index() ----

    public function testIndexFailsWithoutApproverId(): void
    {
        $result = $this->get('api/approvals');
        $result->assertStatus(400);
    }

    public function testIndexReturnsJoinedBookingAndVehicleData(): void
    {
        $result = $this->get("api/approvals?approver_id={$this->approver1Id}");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $this->assertNotEmpty($rows);

        $row = $rows[0];
        $this->assertArrayHasKey('booking_code', $row);
        $this->assertArrayHasKey('vehicle_name', $row);
        $this->assertArrayHasKey('license_plate', $row);
        $this->assertArrayHasKey('actionable', $row);
    }

    public function testIndexOnlyReturnsRowsForRequestedApprover(): void
    {
        $result = $this->get("api/approvals?approver_id={$this->approver1Id}");
        $rows   = json_decode($result->getJSON(), true)['data'];

        foreach ($rows as $row) {
            $this->assertSame($this->approver1Id, (int) $row['approver_id']);
        }
    }

    public function testIndexReturnsEmptyArrayForApproverWithNoAssignments(): void
    {
        $unrelatedApprover = $this->createUser('approver', 1);

        $result = $this->get("api/approvals?approver_id={$unrelatedApprover}");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $this->assertSame([], $rows);
    }

    // ---- approve() ----

    public function testApproveReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->post('api/approvals/999999/approve', []);
        $result->assertStatus(404);
    }

    public function testApproveLevel1LogsActivityWithCorrectApprover(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'approve_booking')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertSame($this->approver1Id, (int) $log['user_id']);
        $this->assertStringContainsString('level 1', $log['description']);
    }

    public function testApproveSetsApprovedAtTimestamp(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $db       = \Config\Database::connect();
        $approval = $db->table('booking_approvals')->where('id', $this->approvalL1Id)->get()->getRowArray();

        $this->assertNotNull($approval['approved_at']);
    }

    // ---- reject() ----

    public function testRejectReturnsNotFoundForUnknownId(): void
    {
        $result = $this->withBodyFormat('json')->post('api/approvals/999999/reject', [
            'notes' => 'Apapun',
        ]);

        $result->assertStatus(404);
    }

    public function testRejectWithoutNotesStoresNullNotes(): void
    {
        $result = $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/reject", []);
        $result->assertStatus(200);

        $this->seeInDatabase('booking_approvals', [
            'id'    => $this->approvalL1Id,
            'notes' => null,
        ]);
    }

    public function testRejectLogsActivityWithCorrectApprover(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/reject", [
            'notes' => 'Kendaraan sedang diperbaiki',
        ]);

        $db  = \Config\Database::connect();
        $log = $db->table('activity_logs')
            ->where('action', 'reject_booking')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertSame($this->approver1Id, (int) $log['user_id']);
    }

    public function testRejectLevel2AfterLevel1ApprovedStillRejectsBooking(): void
    {
        $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL1Id}/approve", []);

        $result = $this->withBodyFormat('json')->post("api/approvals/{$this->approvalL2Id}/reject", [
            'notes' => 'Anggaran tidak mencukupi',
        ]);

        $result->assertStatus(200);
        $this->seeInDatabase('vehicle_bookings', [
            'id'     => $this->bookingId,
            'status' => 'rejected',
        ]);
    }
}