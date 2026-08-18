<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseExpiryToDrivers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('drivers', [
            'license_expiry' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'license_number',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('drivers', ['license_expiry']);
    }
}