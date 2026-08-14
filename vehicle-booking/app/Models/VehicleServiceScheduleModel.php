<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleServiceScheduleModel extends Model
{
    protected $table         = "vehicle_service_schedule";
    protected $primaryKey    = "id";
    protected $allowedFields = ["vehicle_id", "service_date", "description", "status"];
    protected $useTimestamps = true;
    protected $createdField  = "created_at";
    protected $updatedField  = "";
    protected $returnType    = "array";
}