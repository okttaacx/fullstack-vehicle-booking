<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UsersModel;
use App\Libraries\ActivityLogger;

class Auth extends ResourceController
{
    protected $format = "json";

    public function login()
    {
        $throttler = service("throttler");

        $ip = str_replace(":", "_", $this->request->getIPAddress());
        if ($throttler->check("login-{$ip}", 5, MINUTE) === false) {
            return $this->fail(
                "Terlalu banyak percobaan login. Coba lagi dalam {$throttler->getTokenTime()} detik.",
                429
            );
        }

        $data = $this->request->getJSON(true);

        $username = $data["username"] ?? null;
        $password = $data["password"] ?? null;

        if (empty($username) || empty($password)) {
            return $this->fail("Username dan password wajib diisi", 400);
        }

        $model = new UsersModel();
        $user  = $model->where("username", $username)->first();

        if (! $user || ! password_verify($password, $user["password"])) {
            ActivityLogger::log(null, "login_failed", "Percobaan login gagal untuk username: {$username}");
            return $this->failUnauthorized("Username atau password salah");
        }

        unset($user["password"]);

        ActivityLogger::log($user["id"], "login_success", "User {$username} berhasil login");

        return $this->respond([
            "status"  => 200,
            "message" => "Login Berhasil",
            "data"    => $user,
        ]);
    }

    public function logout()
    {
        $userId = $this->request->getJSON(true)["user_id"] ?? null;
        ActivityLogger::log($userId, "logout", "User logout");

        return $this->respond([
            "status"  => 200,
            "message" => "Logout berhasil",
        ]);
    }

    // POST /api/auth/change-password
    // Body: user_id, old_password, new_password
    public function changePassword()
    {
        $throttler = service("throttler");

        $ip = str_replace(":", "_", $this->request->getIPAddress());
        if ($throttler->check("change-password-{$ip}", 5, MINUTE) === false) {
            return $this->fail(
                "Terlalu banyak percobaan. Coba lagi dalam {$throttler->getTokenTime()} detik.",
                429
            );
        }

        $data = $this->request->getJSON(true);

        $required = ["user_id", "old_password", "new_password"];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("Field '{$field}' wajib diisi", 400);
            }
        }

        if (strlen($data["new_password"]) < 6) {
            return $this->fail("Password baru minimal 6 karakter", 400);
        }

        $model = new UsersModel();
        $user  = $model->find($data["user_id"]);

        if (! $user) {
            return $this->failNotFound("User tidak ditemukan");
        }

        if (! password_verify($data["old_password"], $user["password"])) {
            return $this->fail("Password lama tidak sesuai", 400);
        }

        $model->update($data["user_id"], [
            "password" => password_hash($data["new_password"], PASSWORD_DEFAULT),
        ]);

        ActivityLogger::log($data["user_id"], "change_password", "User mengubah password sendiri");

        return $this->respond([
            "status"  => 200,
            "message" => "Password berhasil diubah",
        ]);
    }
}