<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Notification;

$deleted = Notification::truncate();
echo "✓ Notifications cleared from database\n";

// Verify
$count = Notification::count();
echo "Current notification count: {$count}\n";
