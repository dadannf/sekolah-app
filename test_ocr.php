<?php
require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$text = "mandiri
TANGGAL WAKTU TERMINAL
05/12/20 17:02 S1AW1DR9
LOKASI SMR CB ARGENTINA 10
NO. RECORD 3487
===== TRANSFER DARI =====
BANK: MANDIRI
NAMA: LIONEL MESSI
NO. REK: 16204872XXXXX
=========== KE ============
BANK: BANK BRI
NAMA: INDOSCAN
NO. REK: 089529716557
NO. REF:
JUMLAH: RP. 7,946,000.00
HARAP SIMPAN RESI INI
SEBAGAI BUKTI TRANSFER";

$mapper = new \App\Services\OcrFieldMapperService();
$res = $mapper->mapFields($text);
print_r($res);
