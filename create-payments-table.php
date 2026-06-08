<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREATING PAYMENTS TABLE ===\n\n";

// Check if table exists
$exists = DB::select("SHOW TABLES LIKE 'payments'");
if (count($exists) > 0) {
    echo "⚠️  Table 'payments' already exists, skipping...\n";
    exit;
}

// Create payments table
DB::statement("
    CREATE TABLE payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        invoice_id BIGINT UNSIGNED NOT NULL,
        paid_at DATETIME NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        method VARCHAR(50) NOT NULL DEFAULT 'cash' COMMENT 'cash, transfer, qris',
        status VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, rejected',
        reference_no VARCHAR(100) NULL,
        bank_name VARCHAR(100) NULL,
        proof_path VARCHAR(255) NULL,
        notes TEXT NULL,
        received_by BIGINT UNSIGNED NULL,
        verified_at DATETIME NULL,
        verified_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (invoice_id) REFERENCES spp_invoices(id) ON DELETE CASCADE,
        FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_invoice (invoice_id),
        INDEX idx_status (status),
        INDEX idx_paid_at (paid_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Table 'payments' created successfully!\n\n";

// Show table structure
$columns = DB::select("DESCRIBE payments");
echo "Table structure:\n\n";
foreach ($columns as $col) {
    echo "  • {$col->Field} ({$col->Type})\n";
}

echo "\n✅ DONE!\n";
