$vps_ip = "157.15.40.58"
$vps_user = "Administrator"
$vps_pass = "Mybba_84"
$remote_path = "C:\laragon\www\sekolah"

Write-Host "========================================="
Write-Host " Memulai Deployment ke VPS $vps_ip"
Write-Host "========================================="
Write-Host ""

# 1. Zip file (Menggunakan git archive agar file yang di-ignore seperti vendor dan node_modules tidak ikut)
Write-Host "Langkah 1: Membuat release.zip dari Git..."
git archive --format zip --output release.zip HEAD

# 2. Upload via SCP
Write-Host ""
Write-Host "Langkah 2: Upload file release.zip ke VPS..."
Write-Host "[INFO] Saat ditanya password, silakan paste: $vps_pass"
Write-Host "Mengeksekusi scp..."
scp release.zip "${vps_user}@${vps_ip}:release.zip"

# 3. Ekstrak dan pindahkan di VPS via SSH
Write-Host ""
Write-Host "Langkah 3: Ekstrak dan Install Dependencies di VPS..."
Write-Host "[INFO] Saat ditanya password, silakan paste: $vps_pass"

# Perintah PowerShell yang akan dijalankan di VPS
$ssh_commands = "powershell -Command `"New-Item -ItemType Directory -Force -Path $remote_path; Move-Item -Path ~\release.zip -Destination $remote_path\release.zip -Force; cd $remote_path; Expand-Archive -Path release.zip -DestinationPath . -Force; Remove-Item release.zip; if (Get-Command composer -ErrorAction SilentlyContinue) { composer install --no-dev --optimize-autoloader } else { Write-Host 'Composer tidak ditemukan di VPS.' }`""

Write-Host "Mengeksekusi remote commands..."
ssh $vps_user@$vps_ip $ssh_commands

# 4. Clean up lokal
Write-Host ""
Write-Host "Membersihkan file lokal..."
Remove-Item release.zip

Write-Host "========================================="
Write-Host " Deployment Selesai!"
Write-Host " Catatan: Jika ini adalah deployment pertama,"
Write-Host " pastikan Anda membuat file .env dari .env.example"
Write-Host " dan mengatur koneksi database di dalam VPS Anda."
Write-Host "========================================="
