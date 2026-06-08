<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Student;
use App\Models\User;
use App\Models\Notification;
use App\Events\StudentCreated;
use Illuminate\Support\Facades\Auth;

// Create or get admin user for auth
$admin = User::where('role', 'admin')->orWhere('role', 'kepala_sekolah')->first();

if (!$admin) {
    echo "❌ Tidak ada admin user.\n";
    exit(1);
}

Auth::login($admin);
echo "✓ Authenticated as: {$admin->name}\n\n";

// Create a new test user for the student (different from admin)
echo "Creating test user for student...\n";
$testUser = User::create([
    'name' => 'Test Student User ' . time(),
    'email' => 'teststudent' . time() . '@test.com',
    'password' => bcrypt('password'),
    'role' => 'siswa',
]);
echo "✓ Test user created: {$testUser->name} (ID: {$testUser->id})\n\n";

// Create test student
echo "Creating test student...\n";
$student = Student::create([
    'nis' => 'NIS' . time(),
    'nisn' => 'NISN' . time(),
    'name' => 'Test Student ' . now()->format('YmdHis'),
    'user_id' => $testUser->id,
]);
echo "✓ Student created with ID: {$student->id}, Name: {$student->name}\n";

// Now manually dispatch the event to test listener
echo "\nManually dispatching StudentCreated event...\n";
try {
    StudentCreated::dispatch($student);
    echo "✓ Event dispatched\n";
} catch (\Throwable $e) {
    echo "❌ Error dispatching event: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Check notifications
sleep(1);
echo "\nChecking notifications...\n";
$notifications = Notification::latest()->get();
echo "Total notifications: " . $notifications->count() . "\n";

if ($notifications->count() > 0) {
    echo "\n✓ SUCCESS! Notifications created:\n";
    foreach ($notifications->take(5) as $notif) {
        echo "- {$notif->title}\n";
        echo "  Message: {$notif->message}\n";
        echo "  By: {$notif->performed_by_name}\n";
        echo "  User ID: {$notif->user_id}\n\n";
    }
} else {
    echo "❌ No notifications found\n";
    
    // Check if listeners are even registered
    echo "\n=== Checking Event Service Provider ===\n";
    $eventProvider = new \App\Providers\EventServiceProvider($app);
    echo "Event listener mapping exists!\n";
}
