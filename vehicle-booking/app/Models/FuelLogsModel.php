<?php

namespace App\Models;

use CodeIgniter\Model;

class FuelLogsModel extends Model
{
    protected $table            = 'fuel_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'booking_id',
        'vehicle_id',
        'odometer_start',
        'odometer_end',
        'fuel_liters',
        'notes',
        'created_at',
    ];

    protected $useTimestamps = false;
}