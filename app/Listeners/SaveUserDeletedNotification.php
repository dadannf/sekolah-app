<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use App\Models\Notification;
use App\Models\User;

class SaveUserDeletedNotification
{
    public function handle(UserDeleted $event): void
    {
        // Get all admin to notify
        $admins = User::where('role', 'admin')->pluck('id')->toArray();

        $data = [
            'user_id' => $event->userId,
            'email' => $event->email,
            'name' => $event->userName,
            'role' => $event->role,
        ];

        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'user',
                    'action' => 'deleted',
                    'data->user_id' => $event->userId,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                    'type' => 'user',
                    'action' => 'deleted',
                    'title' => 'Pengguna Dihapus',
                    'message' => "Pengguna '{$event->userName}' sebagai {$event->role} telah dihapus dari sistem oleh " . (auth()?->user()?->name ?? 'System'),
                    'data' => $data,
                    'changes' => null,
                ]
            );
        }
    }
}
