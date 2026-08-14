<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UsersModel;

class Auth extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        $usersModel = new UsersModel();
        
        // Mengambil data yang dikirim dari Angular
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        // Cari user berdasarkan username
        $user = $usersModel->where('username', $username)->first();

        if ($user) {
            // Cek kecocokan password
            if (password_verify($password, $user['password'])) {
                // Jangan kirim password kembali ke frontend
                unset($user['password']); 
                
                return $this->respond([
                    'status'  => 200,
                    'message' => 'Login Berhasil',
                    'data'    => $user
                ]);
            } else {
                return $this->respond([
                    'status'  => 401,
                    'message' => 'Password salah'
                ], 401);
            }
        } else {
            return $this->respond([
                'status'  => 404,
                'message' => 'Username tidak ditemukan'
            ], 404);
        }
    }
}