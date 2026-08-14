<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogsModel extends Model
{
    protected $table         = "activity_logs";
    protected $primaryKey    = "id";
    protected $allowedFields = ["user_id", "action", "description", "ip_address"];
    protected $useTimestamps = true;
    protected $createdField  = "created_at";
    protected $updatedField  = "";
    protected $returnType    = "array";
}