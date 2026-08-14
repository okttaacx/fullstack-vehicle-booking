<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleBookingsModel extends Model
{
    protected $table         = "vehicle_bookings";
    protected $primaryKey    = "id";
    protected $allowedFields = [
        "booking_code", "requested_by", "vehicle_id", "driver_id",
        "purpose", "destination", "start_date", "end_date", "status",
    ];
    protected $useTimestamps = true;
    protected $returnType    = "array";
}