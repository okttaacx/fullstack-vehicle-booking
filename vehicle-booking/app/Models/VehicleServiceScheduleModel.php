<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleServiceScheduleModel extends Model
{
    protected $table            = 'vehicle_service_schedule';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'vehicle_id',
        'service_date',
        'description',
        'status',
        'created_at',
    ];

    protected $useTimestamps = false;
}