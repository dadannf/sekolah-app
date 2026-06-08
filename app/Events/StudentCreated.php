<?php

namespace App\Events;

use App\Models\Student;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class StudentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'student.created';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';

        return [
            'id' => $this->student->id,
            'nisn' => $this->student->nisn,
            'name' => $this->student->name,
            'user_id' => $this->student->user_id,
            'grade_id' => $this->student->grade_id,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Siswa baru '{$this->student->name}' telah ditambahkan oleh {$performedBy}",
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'created',
        ];
    }
}
