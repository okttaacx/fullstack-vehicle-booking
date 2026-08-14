<?php

namespace App\Models;

use CodeIgniter\Model;

class DriversModel extends Model
{
    protected $table         = "drivers";
    protected $primaryKey    = "id";
    protected $allowedFields = ["name", "phone", "license_number", "status"];
    protected $useTimestamps = true;
    protected $returnType    = "array";
}