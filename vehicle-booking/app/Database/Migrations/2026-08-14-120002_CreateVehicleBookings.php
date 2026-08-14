<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVehicleBookings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'requested_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'driver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'purpose' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'destination' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'start_date' => ['type' => 'DATETIME'],
            'end_date'   => ['type' => 'DATETIME'],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved_l1', 'approved_l2', 'rejected', 'completed'],
                'default'    => 'pending',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('requested_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('driver_id', 'drivers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('vehicle_bookings');
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_bookings');
    }
}