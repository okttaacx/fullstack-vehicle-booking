<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\VehicleBookingsModel;
use App\Models\BookingApprovalsModel;
use App\Libraries\ActivityLogger;

class Bookings extends ResourceController
{
    protected $format = "json";

    public function index()
    {
        $db = \Config\Database::connect();

        $bookings = $db->table("vehicle_bookings b")
            ->select("b.*, v.name as vehicle_name, v.license_plate, d.name as driver_name, u.name as requester_name")
            ->join("vehicles v", "v.id = b.vehicle_id", "left")
            ->join("drivers d", "d.id = b.driver_id", "left")
            ->join("users u", "u.id = b.requested_by", "left")
            ->orderBy("b.created_at", "DESC")
            ->get()
            ->getResultArray();

        return $this->respond([
            "status" => 200,
            "data"   => $bookings,
        ]);
    }

    public function show($id = null)
    {
        $bookingModel  = new VehicleBookingsModel();
        $approvalModel = new BookingApprovalsModel();

        $booking = $bookingModel->find($id);
        if (! $booking) {
            return $this->failNotFound("Booking tidak ditemukan");
        }

        $db = \Config\Database::connect();
        $approvals = $db->table("booking_approvals ba")
            ->select("ba.*, u.name as approver_name")
            ->join("users u", "u.id = ba.approver_id", "left")
            ->where("ba.booking_id", $id)
            ->orderBy("ba.level", "ASC")
            ->get()
            ->getResultArray();

        $booking["approvals"] = $approvals;

        return $this->respond([
            "status" => 200,
            "data"   => $booking,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        // Validasi minimal
        $required = ["requested_by", "vehicle_id", "start_date", "end_date", "approver_level1_id", "approver_level2_id"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        $bookingModel  = new VehicleBookingsModel();
        $approvalModel = new BookingApprovalsModel();

        $bookingCode = "BK-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));

        $bookingId = $bookingModel->insert([
            "booking_code" => $bookingCode,
            "requested_by" => $data["requested_by"],
            "vehicle_id"   => $data["vehicle_id"],
            "driver_id"    => $data["driver_id"] ?? null,
            "purpose"      => $data["purpose"] ?? null,
            "destination"  => $data["destination"] ?? null,
            "start_date"   => $data["start_date"],
            "end_date"     => $data["end_date"],
            "status"       => "pending",
        ]);

        // Buat 2 baris approval berjenjang
        $approvalModel->insert([
            "booking_id"  => $bookingId,
            "approver_id" => $data["approver_level1_id"],
            "level"       => 1,
            "status"      => "pending",
        ]);
        $approvalModel->insert([
            "booking_id"  => $bookingId,
            "approver_id" => $data["approver_level2_id"],
            "level"       => 2,
            "status"      => "pending",
        ]);

        ActivityLogger::log($data["requested_by"], "create_booking", "Membuat pemesanan kendaraan: {$bookingCode}");

        return $this->respond([
            "status"  => 201,
            "message" => "Pemesanan berhasil dibuat",
            "data"    => ["id" => $bookingId, "booking_code" => $bookingCode],
        ]);
    }
}