<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UsersModel;
use App\Libraries\ActivityLogger;

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

        $users = $model->orderBy("role", "ASC")->orderBy("level", "ASC")->findAll();

        foreach ($users as &$u) {
            unset($u["password"]);
        }

        return $this->respond([
            "status" => 200,
            "data"   => $users,
        ]);
    }

    public function create()
    {
        $model = new UsersModel();
        $data  = $this->request->getJSON(true);

        $required = ["name", "username", "password", "role"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        $existing = $model->where("username", $data["username"])->first();
        if ($existing) {
            return $this->fail("Username sudah digunakan", 409);
        }

        $id = $model->insert([
            "name"     => $data["name"],
            "username" => $data["username"],
            "password" => password_hash($data["password"], PASSWORD_DEFAULT),
            "role"     => $data["role"],
            "level"    => $data["role"] === "approver" ? ($data["level"] ?? null) : null,
        ]);

        ActivityLogger::log(null, "create_user", "Menambahkan user baru: " . $data["username"]);

        return $this->respond([
            "status"  => 201,
            "message" => "User berhasil ditambahkan",
            "data"    => ["id" => $id],
        ], 201); // ✅ Diperbaiki: Menambahkan HTTP status 201
    }

    public function update($id = null)
    {
        $model = new UsersModel();
        $user  = $model->find($id);

        if (! $user) {
            return $this->failNotFound("User tidak ditemukan");
        }

        $data = $this->request->getJSON(true);

        if (! empty($data["username"]) && $data["username"] !== $user["username"]) {
            $existing = $model->where("username", $data["username"])->first();
            if ($existing) {
                return $this->fail("Username sudah digunakan", 409);
            }
        }

        $payload = [
            "name"     => $data["name"] ?? $user["name"],
            "username" => $data["username"] ?? $user["username"],
            "role"     => $data["role"] ?? $user["role"],
            "level"    => ($data["role"] ?? $user["role"]) === "approver" ? ($data["level"] ?? $user["level"]) : null,
        ];

        // Password hanya diubah kalau diisi (tidak dikosongkan begitu saja)
        if (! empty($data["password"])) {
            $payload["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
        }

        $model->update($id, $payload);

        ActivityLogger::log(null, "update_user", "Memperbarui data user ID {$id}");

        return $this->respond([
            "status"  => 200,
            "message" => "User berhasil diperbarui",
        ]);
    }

    public function delete($id = null)
    {
        $model = new UsersModel();
        $user  = $model->find($id);

        if (! $user) {
            return $this->failNotFound("User tidak ditemukan");
        }

        if ($user["role"] === "admin") {
            $adminCount = $model->where("role", "admin")->countAllResults();
            if ($adminCount <= 1) {
                return $this->fail("Tidak dapat menghapus satu-satunya akun admin", 400);
            }
        }

        $model->delete($id);

        ActivityLogger::log(null, "delete_user", "Menghapus user: " . $user["username"]);

        return $this->respond([
            "status"  => 200,
            "message" => "User berhasil dihapus",
        ]);
    }
}