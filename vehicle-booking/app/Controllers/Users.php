<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UsersModel;

class Users extends ResourceController
{
    protected $format = "json";

    // GET /api/users?role=approver
    public function index()
    {
        $role  = $this->request->getGet("role");
        $model = new UsersModel();

        if (! empty($role)) {
            $model->where("role", $role);
        }

        $users = $model->orderBy("level", "ASC")->findAll();

        // Jangan kirim password ke frontend
        foreach ($users as &$u) {
            unset($u["password"]);
        }

        return $this->respond([
            "status" => 200,
            "data"   => $users,
        ]);
    }
}