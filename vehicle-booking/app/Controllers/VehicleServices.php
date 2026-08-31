<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\VehicleServiceScheduleModel;
use App\Models\VehiclesModel;
use App\Libraries\ActivityLogger;

class VehicleServices extends ResourceController
{
    protected $format = "json";

    // GET /api/vehicles/{vehicleId}/services
    public function index($vehicleId = null)
    {
        $model = new VehicleServiceScheduleModel();

        $records = $model->where("vehicle_id", $vehicleId)
            ->orderBy("service_date", "DESC")
            ->findAll();

        return $this->respond([
            "status" => 200,
            "data"   => $records,
        ]);
    }

    // GET /api/vehicle-services/upcoming
    public function upcoming()
    {
        $db = \Config\Database::connect();

        $records = $db->table("vehicle_service_schedule vs")
            ->select("vs.*, v.name as vehicle_name, v.license_plate")
            ->join("vehicles v", "v.id = vs.vehicle_id", "left")
            ->where("vs.status", "scheduled")
            ->orderBy("vs.service_date", "ASC")
            ->get()
            ->getResultArray();

        return $this->respond([
            "status" => 200,
            "data"   => $records,
        ]);
    }

    // POST /api/vehicle-services
    public function create()
    {
        $data = $this->request->getJSON(true);

        $required = ["vehicle_id", "service_date"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        $vehicleModel = new VehiclesModel();
        $vehicle = $vehicleModel->find($data["vehicle_id"]);
        if (! $vehicle) {
            return $this->failNotFound("Kendaraan tidak ditemukan");
        }

        $model = new VehicleServiceScheduleModel();

        $id = $model->insert([
            "vehicle_id"   => $data["vehicle_id"],
            "service_date" => $data["service_date"],
            "description"  => $data["description"] ?? null,
            "status"       => $data["status"] ?? "scheduled",
            "created_at"   => date("Y-m-d H:i:s"),
        ]);

        ActivityLogger::log(null, "create_service_log", "Menambahkan catatan service untuk kendaraan: " . $vehicle["name"]);

        return $this->respond([
            "status"  => 201,
            "message" => "Catatan service berhasil ditambahkan",
            "data"    => ["id" => $id],
        ], 201);
    }

    // PUT /api/vehicle-services/{id}
    // Dipakai juga untuk mengubah status (mis. tandai "done" saat service selesai dikerjakan)
    public function update($id = null)
    {
        $model = new VehicleServiceScheduleModel();
        $record = $model->find($id);

        if (! $record) {
            return $this->failNotFound("Catatan service tidak ditemukan");
        }

        $data = $this->request->getJSON(true);

        if (isset($data["status"]) && ! in_array($data["status"], ["scheduled", "done", "cancelled"], true)) {
            return $this->fail("Status tidak valid", 400);
        }

        $model->update($id, [
            "service_date" => $data["service_date"] ?? $record["service_date"],
            "description"  => $data["description"] ?? $record["description"],
            "status"       => $data["status"] ?? $record["status"],
        ]);

        ActivityLogger::log(null, "update_service_log", "Memperbarui catatan service ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "Catatan service berhasil diperbarui",
        ]);
    }

    // DELETE /api/vehicle-services/{id}
    public function delete($id = null)
    {
        $model = new VehicleServiceScheduleModel();
        $record = $model->find($id);

        if (! $record) {
            return $this->failNotFound("Catatan service tidak ditemukan");
        }

        $model->delete($id);

        ActivityLogger::log(null, "delete_service_log", "Menghapus catatan service ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "Catatan service berhasil dihapus",
        ]);
    }
}