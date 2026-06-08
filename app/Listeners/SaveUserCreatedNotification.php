<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Models\Notification;
use App\Models\User;

class SaveUserCreatedNotification
{
    public function handle(UserCreated $event): void
    {
        // Get all admin to notify
        $admins = User::where('role', 'admin')->pluck('id')->toArray();

        $data = [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'name' => $event->user->name,
            'role' => $event->user->role,
        ];

        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'user',
                    'action' => 'created',
                    'data->user_id' => $event->user->id,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                    'type' => 'user',
                    'action' => 'created',
                    'title' => 'Pengguna Baru Ditambahkan',
                    'message' => "Pengguna '{$event->user->name}' sebagai {$event->user->role} telah ditambahkan oleh " . (auth()?->user()?->name ?? 'System'),
                    'data' => $data,
                    'changes' => null,
                ]
            );
        }
    }
}
