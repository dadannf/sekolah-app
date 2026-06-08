<?php
/**
 * Test script untuk notification system
 * Jalankan: php test-notification-system.php
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bind laravel into container
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "=== NOTIFICATION SYSTEM TEST ===\n\n";

// 1. Clear old notifications
echo "1. CLEARING OLD NOTIFICATIONS...\n";
$deleted = DB::table('notifications')->delete();
echo "   ✓ Deleted $deleted old notifications\n\n";

// 2. Check admin users
echo "2. CHECKING ADMIN USERS...\n";
$admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->get();
if ($admins->isEmpty()) {
    echo "   ✗ No admin/kepala_sekolah users found!\n";
    echo "   Creating test admin...\n";
    $admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.local',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);
    echo "   ✓ Created admin: ID {$admin->id}, Email: {$admin->email}\n\n";
} else {
    echo "   ✓ Found " . $admins->count() . " admin users:\n";
    foreach ($admins as $admin) {
        echo "     - ID:{$admin->id}, Name: {$admin->name}, Email: {$admin->email}\n";
    }
    echo "\n";
}

// 3. Check student users
echo "3. CHECKING STUDENT USERS...\n";
$studentUsers = User::where('role', 'siswa')->get();
if ($studentUsers->isEmpty()) {
    echo "   ✗ No student users found!\n";
} else {
    echo "   ✓ Found " . $studentUsers->count() . " student users:\n";
    foreach ($studentUsers->take(3) as $stud) {
        echo "     - ID:{$stud->id}, Name: {$stud->name}\n";
    }
    echo "\n";
}

// 4. Simulate user authentication for event listener
echo "4. SIMULATING USER UPDATE EVENT...\n";
try {
    // Get a student to update
    $student = Student::first();
    if ($student) {
        // Fake authentication as the student
        \Illuminate\Support\Facades\Auth::loginUsingId($student->user_id);
        
        echo "   Authenticating as: " . auth()->user()->name . "\n";
        
        // Update student
        $student->gender = ($student->gender === 'M' ? 'F' : 'M');
        $student->save();
        
        echo "   ✓ Updated student: {$student->name} (gender changed)\n";
        echo "   StudentUpdated event triggered\n\n";
    } else {
        echo "   ✗ No student records found\n\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// 5. Check if notifications were created
echo "5. CHECKING NOTIFICATIONS...\n";
$notifications = Notification::latest('created_at')->get();
if ($notifications->isEmpty()) {
    echo "   ✗ No notifications found in database!\n";
    echo "   This means the listener did NOT fire or failed silently\n";
} else {
    echo "   ✓ Found " . $notifications->count() . " notifications:\n\n";
    foreach ($notifications as $notif) {
        echo "   Notification ID: {$notif->id}\n";
        echo "     User ID: {$notif->user_id}\n";
        echo "     Performed By: {$notif->performed_by_name} (ID: {$notif->performed_by_id})\n";
        echo "   Type: {$notif->type}, Action: {$notif->action}\n";
        echo "     Title: {$notif->title}\n";
        echo "     Message: {$notif->message}\n";
        if ($notif->data) {
            echo "     Data: " . substr(json_encode($notif->data), 0, 50) . "...\n";
        }
        echo "     Created: {$notif->created_at}\n\n";
    }
}

// 6. Test API response
echo "\n6. TESTING API RESPONSE...\n";
if (!$notifications->isEmpty()) {
    $sample = $notifications->first();
    echo "   Sample notification as JSON:\n";
    echo json_encode($sample->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== END TEST ===\n";
