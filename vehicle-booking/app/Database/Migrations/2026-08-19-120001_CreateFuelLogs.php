<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFuelLogs extends Migration
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
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'odometer_start' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'odometer_end' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'fuel_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('booking_id', 'vehicle_bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fuel_logs');
    }

    public function down()
    {
        $this->forge->dropTable('fuel_logs');
    }
}