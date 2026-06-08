<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class UserCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.created';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';

        return [
            'id' => $this->user->id,
            'email' => $this->user->email,
            'name' => $this->user->name,
            'role' => $this->user->role,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Pengguna baru '{$this->user->name}' sebagai {$this->user->role} telah ditambahkan oleh {$performedBy}",
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'created',
        ];
    }
}
