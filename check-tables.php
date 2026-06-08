<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
);

echo "=== Semua Tabel di Database ===\n";
foreach($tables as $t) {
    echo $t->TABLE_NAME . "\n";
}
