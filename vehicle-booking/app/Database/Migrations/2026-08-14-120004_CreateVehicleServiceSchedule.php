<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVehicleServiceSchedule extends Migration
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
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'service_date' => ['type' => 'DATE'],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['scheduled', 'done', 'cancelled'],
                'default'    => 'scheduled',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vehicle_service_schedule');
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_service_schedule');
    }
}