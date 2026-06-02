<?php 
namespace App\Services;

use App\Models\Staff;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StaffActivityNotification;

class StaffNotificationService
{
    public static function notify(string $type, string $message, array $data = [])
    {
        // Get particular staff members
        $staffMembers = Staff::all();

        if ($staffMembers->isEmpty()) {
            return;
        }

        Notification::send(
            $staffMembers,
            new StaffActivityNotification($type, $message, $data)
        );
    }
}