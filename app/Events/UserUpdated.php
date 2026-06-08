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

class UserUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public array $changes = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel("user.{$this->user->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.updated';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';
        $changedFields = array_keys($this->changes);

        return [
            'id' => $this->user->id,
            'email' => $this->user->email,
            'name' => $this->user->name,
            'role' => $this->user->role,
            'changes' => $this->changes,
            'changed_fields' => implode(', ', $changedFields),
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Data pengguna '{$this->user->name}' telah diperbarui oleh {$performedBy}. Perubahan: " . implode(', ', $changedFields),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'updated',
        ];
    }
}
