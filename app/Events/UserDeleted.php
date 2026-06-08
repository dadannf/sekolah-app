<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class UserDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $userName,
        public string $email,
        public string $role
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.deleted';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';

        return [
            'id' => $this->userId,
            'name' => $this->userName,
            'email' => $this->email,
            'role' => $this->role,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Pengguna '{$this->userName}' sebagai {$this->role} telah dihapus dari sistem oleh {$performedBy}",
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'deleted',
        ];
    }
}
