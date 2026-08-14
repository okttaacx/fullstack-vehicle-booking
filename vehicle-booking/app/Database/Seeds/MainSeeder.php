<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        // ==========================================
        // 1. INSERT DATA USERS
        // ==========================================
        $users = [
            [
                'name'       => 'Admin Pool',
                'username'   => 'admin',
                'password'   => password_hash('password123', PASSWORD_BCRYPT),
                'role'       => 'admin',
                'level'      => null, // Admin tidak butuh level approval
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'SPV Tambang 1 (Approver Lvl 1)',
                'username'   => 'spv_tambang1',
                'password'   => password_hash('password123', PASSWORD_BCRYPT),
                'role'       => 'approver',
                'level'      => 1, // Approver Level 1
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Manager HQ (Approver Lvl 2)',
                'username'   => 'manager_hq',
                'password'   => password_hash('password123', PASSWORD_BCRYPT),
                'role'       => 'approver',
                'level'      => 2, // Approver Level 2
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];
        
        // Memasukkan data ke tabel users
        $this->db->table('users')->insertBatch($users);

        // ==========================================
        // 2. INSERT DATA KENDARAAN (VEHICLES)
        // ==========================================
        $vehicles = [
            [
                'name'             => 'Toyota Hilux Double Cabin',
                'license_plate'    => 'B 1234 TAM',
                'type'             => 'angkutan_orang',
                'ownership'        => 'milik_perusahaan',
                'fuel_consumption' => 10.5, // 10.5 km/liter
                'service_schedule' => '2026-12-01',
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'name'             => 'Mitsubishi Fuso Dump Truck',
                'license_plate'    => 'B 9876 XYZ',
                'type'             => 'angkutan_barang',
                'ownership'        => 'sewa',
                'fuel_consumption' => 5.2, // 5.2 km/liter
                'service_schedule' => '2026-09-15',
                'created_at'       => date('Y-m-d H:i:s'),
            ]
        ];

        // Memasukkan data ke tabel vehicles
        $this->db->table('vehicles')->insertBatch($vehicles);
    }
}