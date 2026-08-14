<?php

namespace App\Models;

use CodeIgniter\Model;

class VehiclesModel extends Model
{
    protected $table            = 'vehicles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    protected $allowedFields    = [
        'name', 
        'license_plate', 
        'type', 
        'ownership', 
        'fuel_consumption', 
        'service_schedule'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}