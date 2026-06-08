<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class StudentDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $studentId,
        public string $studentName,
        public ?string $nisn = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'student.deleted';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';

        return [
            'id' => $this->studentId,
            'nisn' => $this->nisn ?? '',
            'name' => $this->studentName,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Siswa '{$this->studentName}' telah dihapus dari sistem oleh {$performedBy}",
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'deleted',
        ];
    }
}
