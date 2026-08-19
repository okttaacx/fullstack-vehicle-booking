<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImageUrlToVehicles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('vehicles', [
            'image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'name', // opsional, biar kolomnya rapi posisinya
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('vehicles', 'image_url');
    }
}