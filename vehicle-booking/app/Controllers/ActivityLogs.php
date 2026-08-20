<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class ActivityLogs extends ResourceController
{
    protected $format = "json";

    public function index()
    {
        $db = \Config\Database::connect();

        $builder = $db->table("activity_logs al")
            ->select("al.*, u.name as user_name, u.username as user_username")
            ->join("users u", "u.id = al.user_id", "left")
            ->orderBy("al.created_at", "DESC");

        $action = $this->request->getGet("action");
        $start  = $this->request->getGet("start");
        $end    = $this->request->getGet("end");

        if (! empty($action)) {
            $builder->where("al.action", $action);
        }
        if (! empty($start)) {
            $builder->where("al.created_at >=", $start . " 00:00:00");
        }
        if (! empty($end)) {
            $builder->where("al.created_at <=", $end . " 23:59:59");
        }

        $logs = $builder->get()->getResultArray();

        return $this->respond([
            "status" => 200,
            "data"   => $logs,
        ]);
    }
}