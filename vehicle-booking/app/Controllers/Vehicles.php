<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\VehiclesModel;
use App\Libraries\ActivityLogger;

class Vehicles extends ResourceController
{
    protected $format = "json";

    public function index()
    {
        $model = new VehiclesModel();
        return $this->respond([
            "status" => 200,
            "data"   => $model->orderBy("id", "DESC")->findAll(),
        ]);
    }

    public function create()
    {
        $model = new VehiclesModel();
        $data  = $this->request->getJSON(true);

        $required = ["name", "license_plate", "type", "ownership"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        $id = $model->insert([
            "name"              => $data["name"],
            "license_plate"     => $data["license_plate"],
            "type"              => $data["type"],
            "ownership"         => $data["ownership"],
            "fuel_consumption"  => $data["fuel_consumption"] ?? null,
            "service_schedule"  => $data["service_schedule"] ?? null,
            "image_url"         => $data["image_url"] ?? null,
        ]);

        ActivityLogger::log(null, "create_vehicle", "Menambahkan kendaraan baru: " . $data["name"]);

        return $this->respond([
            "status"  => 201,
            "message" => "Kendaraan berhasil ditambahkan",
            "data"    => ["id" => $id],
        ], 201);
    }

    public function update($id = null)
    {
        $model = new VehiclesModel();
        $vehicle = $model->find($id);

        if (! $vehicle) {
            return $this->failNotFound("Kendaraan tidak ditemukan");
        }

        $data = $this->request->getJSON(true);

        $model->update($id, [
            "name"              => $data["name"] ?? $vehicle["name"],
            "license_plate"     => $data["license_plate"] ?? $vehicle["license_plate"],
            "type"              => $data["type"] ?? $vehicle["type"],
            "ownership"         => $data["ownership"] ?? $vehicle["ownership"],
            "fuel_consumption"  => $data["fuel_consumption"] ?? $vehicle["fuel_consumption"],
            "service_schedule"  => $data["service_schedule"] ?? $vehicle["service_schedule"],
            "image_url"         => $data["image_url"] ?? $vehicle["image_url"],
        ]);

        ActivityLogger::log(null, "update_vehicle", "Memperbarui data kendaraan ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "Kendaraan berhasil diperbarui",
        ]);
    }

    public function delete($id = null)
    {
        $model = new VehiclesModel();
        $vehicle = $model->find($id);

        if (! $vehicle) {
            return $this->failNotFound("Kendaraan tidak ditemukan");
        }

        $model->delete($id);

        ActivityLogger::log(null, "delete_vehicle", "Menghapus kendaraan: " . $vehicle["name"]);

        return $this->respond([
            "status"  => 200,
            "message" => "Kendaraan berhasil dihapus",
        ]);
    }
}