<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingApprovalsModel extends Model
{
    protected $table         = "booking_approvals";
    protected $primaryKey    = "id";
    protected $allowedFields = ["booking_id", "approver_id", "level", "status", "notes", "approved_at"];
    protected $useTimestamps = true;
    protected $createdField  = "created_at";
    protected $updatedField  = "";
    protected $returnType    = "array";
}