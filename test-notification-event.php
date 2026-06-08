<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Student;
use App\Models\User;
use App\Models\Notification;

// Create or get admin user for auth
$admin = User::where('role', 'admin')->orWhere('role', 'kepala_sekolah')->first();

if (!$admin) {
    echo "❌ Tidak ada admin user. Silakan buat user admin terlebih dahulu.\n";
    exit(1);
}

echo "✓ Using admin user: {$admin->name} (ID: {$admin->id})\n\n";

// Authenticate
Auth::login($admin);

// Create test student
echo "Creating test student...\n";
try {
    $student = Student::create([
        'nis' => 'NIS' . time(),
        'nisn' => 'NISN' . time(),
        'name' => 'Test Student ' . now()->format('YmdHis'),
        'user_id' => $admin->id,
    ]);
    echo "✓ Student created: {$student->name}\n";
} catch (\Exception $e) {
    echo "❌ Error creating student: " . $e->getMessage() . "\n";
    exit(1);
}

// Check notifications
sleep(1);
echo "\nChecking notifications...\n";
$notificationCount = Notification::count();
echo "Total notifications: {$notificationCount}\n";

if ($notificationCount > 0) {
    echo "\n✓ SUCCESS! Notifications are being created!\n\n";
    $latestNotifications = Notification::latest()->take(5)->get();
    foreach ($latestNotifications as $notif) {
        echo "- {$notif->title}\n";
        echo "  Message: {$notif->message}\n";
        echo "  By: {$notif->performed_by_name}\n";
        echo "  Type: {$notif->type} | Action: {$notif->action}\n\n";
    }
} else {
    echo "❌ No notifications created. Check if listeners are being triggered.\n";
}
