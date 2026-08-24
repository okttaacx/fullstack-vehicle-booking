<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InitTables extends Migration
{
    public function up()
    {
        // 1. TABEL USERS
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'       => ['type' => 'ENUM', 'constraint' => ['admin', 'approver']],
            'level'      => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');

        // 2. TABEL VEHICLES
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'license_plate'    => ['type' => 'VARCHAR', 'constraint' => 20, 'unique' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['angkutan_orang', 'angkutan_barang']],
            'ownership'        => ['type' => 'ENUM', 'constraint' => ['milik_perusahaan', 'sewa']],
            
            // PERBAIKAN: Ditambahkan 'null' => true di sini
            'fuel_consumption' => ['type' => 'FLOAT', 'null' => true], 
            
            'service_schedule' => ['type' => 'DATE', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('vehicles');

        // 3. TABEL RESERVATIONS
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'admin_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'vehicle_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'driver_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'approver_1_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'approver_2_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_date'    => ['type' => 'DATE'],
            'end_date'      => ['type' => 'DATE'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending_1', 'pending_2', 'approved', 'rejected', 'done'], 'default' => 'pending_1'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        
        // Relasi
        $this->forge->addForeignKey('admin_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('approver_1_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('approver_2_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reservations');

        // 4. TABEL APPROVALS
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'reservation_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        
        // Relasi
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('approvals');
    }

    public function down()
    {
        $this->forge->dropTable('approvals');
        $this->forge->dropTable('reservations');
        $this->forge->dropTable('vehicles');
        $this->forge->dropTable('users');
    }
}