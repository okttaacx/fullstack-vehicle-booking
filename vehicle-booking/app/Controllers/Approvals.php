<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\BookingApprovalsModel;
use App\Models\VehicleBookingsModel;
use App\Libraries\ActivityLogger;

class Approvals extends ResourceController
{
    protected $format = "json";

    // GET /api/approvals?approver_id=3
    public function index()
    {
        $approverId = $this->request->getGet("approver_id");
        if (empty($approverId)) {
            return $this->fail("Parameter approver_id wajib diisi", 400);
        }

        $db = \Config\Database::connect();
        $rows = $db->table("booking_approvals ba")
            ->select("ba.*, b.booking_code, b.purpose, b.destination, b.start_date, b.end_date, v.name as vehicle_name, v.license_plate")
            ->join("vehicle_bookings b", "b.id = ba.booking_id", "left")
            ->join("vehicles v", "v.id = b.vehicle_id", "left")
            ->where("ba.approver_id", $approverId)
            ->where("ba.status", "pending")
            ->orderBy("ba.id", "ASC")
            ->get()
            ->getResultArray();

        // Filter: level 2 hanya boleh muncul kalau level 1 booking yang sama sudah approved
        $filtered = [];
        foreach ($rows as $row) {
            if ((int) $row["level"] === 1) {
                $filtered[] = $row;
                continue;
            }

            $level1 = $db->table("booking_approvals")
                ->where("booking_id", $row["booking_id"])
                ->where("level", 1)
                ->get()
                ->getRowArray();

            if ($level1 && $level1["status"] === "approved") {
                $filtered[] = $row;
            }
        }

        return $this->respond([
            "status" => 200,
            "data"   => $filtered,
        ]);
    }

    public function approve($id = null)
    {
        $approvalModel = new BookingApprovalsModel();
        $bookingModel  = new VehicleBookingsModel();

        $approval = $approvalModel->find($id);
        if (! $approval) {
            return $this->failNotFound("Data approval tidak ditemukan");
        }

        // Validasi berjenjang: level 2 hanya boleh approve setelah level 1 approved
        if ((int) $approval["level"] === 2) {
            $level1 = $approvalModel
                ->where("booking_id", $approval["booking_id"])
                ->where("level", 1)
                ->first();

            if (! $level1 || $level1["status"] !== "approved") {
                return $this->fail("Approval level 1 belum disetujui", 400);
            }
        }

        $approvalModel->update($id, [
            "status"      => "approved",
            "approved_at" => date("Y-m-d H:i:s"),
        ]);

        $newBookingStatus = (int) $approval["level"] === 1 ? "approved_l1" : "approved_l2";
        $bookingModel->update($approval["booking_id"], ["status" => $newBookingStatus]);

        ActivityLogger::log($approval["approver_id"], "approve_booking", "Menyetujui booking ID {$approval["booking_id"]} level {$approval["level"]}");

        return $this->respond([
            "status"  => 200,
            "message" => "Approval berhasil disetujui",
        ]);
    }

    public function reject($id = null)
    {
        $approvalModel = new BookingApprovalsModel();
        $bookingModel  = new VehicleBookingsModel();
        $data          = $this->request->getJSON(true);

        $approval = $approvalModel->find($id);
        if (! $approval) {
            return $this->failNotFound("Data approval tidak ditemukan");
        }

        $approvalModel->update($id, [
            "status" => "rejected",
            "notes"  => $data["notes"] ?? null,
        ]);

        $bookingModel->update($approval["booking_id"], ["status" => "rejected"]);

        ActivityLogger::log($approval["approver_id"], "reject_booking", "Menolak booking ID {$approval["booking_id"]}");

        return $this->respond([
            "status"  => 200,
            "message" => "Booking berhasil ditolak",
        ]);
    }
}