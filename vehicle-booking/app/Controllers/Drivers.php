<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\DriversModel;
use App\Libraries\ActivityLogger;

class Drivers extends ResourceController
{
    protected $format = "json";

    public function index()
    {
        $model = new DriversModel();
        return $this->respond([
            "status" => 200,
            "data"   => $model->orderBy("name", "ASC")->findAll(),
        ]);
    }

    public function create()
    {
        $model = new DriversModel();
        $data  = $this->request->getJSON(true) ?? $this->request->getPost();

        if (empty($data["name"])) {
            return $this->fail("Nama driver wajib diisi", 400);
        }

        $id = $model->insert([
            "name"            => $data["name"],
            "phone"           => $data["phone"] ?? null,
            "license_number"  => $data["license_number"] ?? null,
            "license_expiry"  => $data["license_expiry"] ?? null,
            "status"          => $data["status"] ?? "active",
        ]);

        ActivityLogger::log(null, "create_driver", "Menambahkan driver baru: " . $data["name"]);

        return $this->respond([
            "status"  => 201,
            "message" => "Driver berhasil ditambahkan",
            "data"    => ["id" => $id],
        ], 201); // ✅ Diperbaiki: Menambahkan HTTP status 201
    }

    public function update($id = null)
    {
        $model = new DriversModel();
        $driver = $model->find($id);

        if (! $driver) {
            return $this->failNotFound("Driver tidak ditemukan");
        }

        $data = $this->request->getJSON(true);

        $model->update($id, [
            "name"            => $data["name"] ?? $driver["name"],
            "phone"           => $data["phone"] ?? $driver["phone"],
            "license_number"  => $data["license_number"] ?? $driver["license_number"],
            "license_expiry"  => array_key_exists("license_expiry", $data) ? $data["license_expiry"] : $driver["license_expiry"],
            "status"          => $data["status"] ?? $driver["status"],
        ]);

        ActivityLogger::log(null, "update_driver", "Memperbarui data driver ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "Driver berhasil diperbarui",
        ]);
    }

    public function delete($id = null)
    {
        $model = new DriversModel();
        $driver = $model->find($id);

        if (! $driver) {
            return $this->failNotFound("Driver tidak ditemukan");
        }

        $model->delete($id);

        ActivityLogger::log(null, "delete_driver", "Menghapus driver: " . $driver["name"]);

        return $this->respond([
            "status"  => 200,
            "message" => "Driver berhasil dihapus",
        ]);
    }
}