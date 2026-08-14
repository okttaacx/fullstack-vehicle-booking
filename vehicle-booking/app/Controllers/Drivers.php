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
            "data"   => $model->findAll(),
        ]);
    }

    public function create()
    {
        $model = new DriversModel();
        $data  = $this->request->getJSON(true) ?? $this->request->getPost();

        $id = $model->insert($data);

        ActivityLogger::log($data["created_by"] ?? null, "create_driver", "Menambahkan driver baru: " . ($data["name"] ?? "-"));

        return $this->respond([
            "status"  => 201,
            "message" => "Driver berhasil ditambahkan",
            "data"    => ["id" => $id],
        ]);
    }
}