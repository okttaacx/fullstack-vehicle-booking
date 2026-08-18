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
            ->select("b.*, v.name as vehicle_name, v.license_plate, v.type as vehicle_type, d.name as driver_name, u.name as requester_name")
            ->join("vehicles v", "v.id = b.vehicle_id", "left")
            ->join("drivers d", "d.id = b.driver_id", "left")
            ->join("users u", "u.id = b.requested_by", "left")
            ->orderBy("b.created_at", "DESC")
            ->get()
            ->getResultArray();

        foreach ($bookings as &$b) {
            $b["rejection_reason"] = null;
            if ($b["status"] === "rejected") {
                $rejected = $db->table("booking_approvals")
                    ->where("booking_id", $b["id"])
                    ->where("status", "rejected")
                    ->get()
                    ->getRowArray();

                if ($rejected) {
                    $b["rejection_reason"] = $rejected["notes"];
                }
            }
        }

        return $this->respond([
            "status" => 200,
            "data"   => $bookings,
        ]);
    }

    public function show($id = null)
    {
        $bookingModel = new VehicleBookingsModel();

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

    private function findScheduleConflict($vehicleId, $startDate, $endDate, $excludeId = null)
    {
        $db = \Config\Database::connect();

        $builder = $db->table("vehicle_bookings")
            ->where("vehicle_id", $vehicleId)
            ->whereIn("status", ["pending", "approved_l1", "approved_l2"])
            ->where("start_date <", $endDate)
            ->where("end_date >", $startDate);

        if ($excludeId !== null) {
            $builder->where("id !=", $excludeId);
        }

        return $builder->get()->getRowArray();
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        $required = ["requested_by", "vehicle_id", "start_date", "end_date", "approver_level1_id", "approver_level2_id"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        if (strtotime($data["end_date"]) <= strtotime($data["start_date"])) {
            return $this->fail("Tanggal selesai harus setelah tanggal mulai", 400);
        }

        $conflict = $this->findScheduleConflict($data["vehicle_id"], $data["start_date"], $data["end_date"]);
        if ($conflict) {
            return $this->fail(
                "Kendaraan sudah dipesan pada rentang waktu tersebut (bentrok dengan booking {$conflict['booking_code']}, {$conflict['start_date']} s/d {$conflict['end_date']})",
                409
            );
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

    public function update($id = null)
    {
        $bookingModel = new VehicleBookingsModel();
        $booking = $bookingModel->find($id);

        if (! $booking) {
            return $this->failNotFound("Booking tidak ditemukan");
        }

        if ($booking["status"] !== "pending") {
            return $this->fail("Pemesanan yang sudah diproses approver tidak dapat diubah", 400);
        }

        $data = $this->request->getJSON(true);

        $newVehicleId = $data["vehicle_id"] ?? $booking["vehicle_id"];
        $newStart     = $data["start_date"] ?? $booking["start_date"];
        $newEnd       = $data["end_date"] ?? $booking["end_date"];

        if (strtotime($newEnd) <= strtotime($newStart)) {
            return $this->fail("Tanggal selesai harus setelah tanggal mulai", 400);
        }

        $conflict = $this->findScheduleConflict($newVehicleId, $newStart, $newEnd, $id);
        if ($conflict) {
            return $this->fail(
                "Kendaraan sudah dipesan pada rentang waktu tersebut (bentrok dengan booking {$conflict['booking_code']}, {$conflict['start_date']} s/d {$conflict['end_date']})",
                409
            );
        }

        $bookingModel->update($id, [
            "vehicle_id"  => $newVehicleId,
            "driver_id"   => array_key_exists("driver_id", $data) ? $data["driver_id"] : $booking["driver_id"],
            "purpose"     => $data["purpose"] ?? $booking["purpose"],
            "destination" => $data["destination"] ?? $booking["destination"],
            "start_date"  => $newStart,
            "end_date"    => $newEnd,
        ]);

        ActivityLogger::log(null, "update_booking", "Memperbarui pemesanan ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "Pemesanan berhasil diperbarui",
        ]);
    }

    public function delete($id = null)
    {
        $bookingModel = new VehicleBookingsModel();
        $booking = $bookingModel->find($id);

        if (! $booking) {
            return $this->failNotFound("Booking tidak ditemukan");
        }

        if ($booking["status"] !== "pending") {
            return $this->fail("Pemesanan yang sudah diproses approver tidak dapat dihapus", 400);
        }

        $bookingModel->delete($id);

        ActivityLogger::log(null, "delete_booking", "Menghapus pemesanan: " . $booking["booking_code"]);

        return $this->respond([
            "status"  => 200,
            "message" => "Pemesanan berhasil dihapus",
        ]);
    }
}