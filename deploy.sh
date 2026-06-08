#!/bin/bash

# ==============================================================================
# SCRIPT DEPLOYMENT LARAVEL (cPanel Shared Hosting)
# ==============================================================================

# Ubah USERNAME dengan username cPanel kamu
# Ubah sekolah-app dengan nama folder project Laravel kamu di hosting
PROJECT_DIR="/home/USERNAME/sekolah-app"
PHP_BIN="php" # Terkadang di cPanel perlu diubah ke spesifik versi misal: ea-php82 atau /usr/local/bin/php

echo "======================================"
echo "Memulai proses deployment Laravel..."
echo "======================================"

# Masuk ke direktori project
cd $PROJECT_DIR || { 
    echo "❌ Direktori $PROJECT_DIR tidak ditemukan!"; 
    echo "Deployment dihentikan.";
    exit 1; 
}

echo "Berada di direktori: $(pwd)"

# Menjalankan git pull untuk menarik pembaruan terbaru dari GitHub
echo "Tarik pembaruan dari Git (git pull)..."
git pull origin main

# Periksa status git pull
if [ $? -ne 0 ]; then
    echo "❌ Git pull gagal! Ada konflik atau masalah koneksi."
    echo "Deployment dihentikan."
    exit 1
fi
echo "✅ Git pull berhasil."

# Install / update composer dependencies jika ada perubahan (opsional tapi disarankan)
# Uncomment jika ingin mengaktifkan
# echo "Install/Update Composer Dependencies..."
# composer install --optimize-autoloader --no-dev

# Menjalankan perintah artisan cache & optimize
echo "Membersihkan dan me-rebuild cache Laravel..."

$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Menjalankan migrasi database otomatis (opsional)
# PENTING: `--force` wajib digunakan di environment production
# Uncomment jika ingin migrasi otomatis saat deploy
# echo "Menjalankan migrasi database..."
# $PHP_BIN artisan migrate --force

echo "======================================"
echo "✅ Deployment selesai dan berhasil!"
echo "======================================"
