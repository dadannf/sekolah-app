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

class StudentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student,
        public array $changes = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel("student.{$this->student->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'student.updated';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';
        $changedFields = array_keys($this->changes);
        
        return [
            'id' => $this->student->id,
            'nisn' => $this->student->nisn,
            'name' => $this->student->name,
            'user_id' => $this->student->user_id,
            'grade_id' => $this->student->grade_id,
            'changes' => $this->changes,
            'changed_fields' => implode(', ', $changedFields),
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Data siswa '{$this->student->name}' telah diperbarui oleh {$performedBy}. Perubahan: " . implode(', ', $changedFields),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'updated',
        ];
    }
}
