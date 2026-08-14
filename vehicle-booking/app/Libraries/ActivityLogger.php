<?php

namespace App\Libraries;

use App\Models\ActivityLogsModel;

class ActivityLogger
{
    public static function log($userId, string $action, string $description = "")
    {
        $model = new ActivityLogsModel();
        $model->insert([
            "user_id"     => $userId,
            "action"      => $action,
            "description" => $description,
            "ip_address"  => service("request")->getIPAddress(),
        ]);
    }
}