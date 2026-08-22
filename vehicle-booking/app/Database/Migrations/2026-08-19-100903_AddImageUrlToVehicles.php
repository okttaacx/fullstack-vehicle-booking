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
        // Pengecekan agar SQLite3 tidak error saat mencoba dropColumn pada testing
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->forge->dropColumn('vehicles', 'image_url');
        }
    }
}