# 🧹 Project Cleanup Script
# Clean up test, debug, documentation and temporary files
# Creates backup before deletion for safety

$ErrorActionPreference = "Continue"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "cleanup_backup_$timestamp"

Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║          PROJECT CLEANUP - SEKOLAH MANAGEMENT              ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Create backup directory
Write-Host "📁 Creating backup directory: $backupDir" -ForegroundColor Yellow
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\root" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\database" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\ocr-service" -Force | Out-Null

$totalFiles = 0
$totalSize = 0

# Function to move file with logging
function Move-FileWithLog {
    param($source, $destination)
    if (Test-Path $source) {
        $file = Get-Item $source
        $script:totalSize += $file.Length
        $script:totalFiles++
        Write-Host "  ✓ Moving: $($file.Name)" -ForegroundColor Green
        Move-Item -Path $source -Destination $destination -Force
        return $true
    }
    return $false
}

Write-Host "`n🗑️  CLEANING ROOT DIRECTORY..." -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

# Test/Debug PHP Files
Write-Host "`n📝 Test & Debug PHP Files:" -ForegroundColor Yellow
$testFiles = @(
    "check-and-fix-table.php",
    "check-duplicate-email.php",
    "check-login.php",
    "check-payment-locations.php",
    "check-payments.php",
    "check-table-structure.php",
    "check-tables.php",
    "check-tariffs.php",
    "check-upload-config.php",
    "test-file-storage.php",
    "test-image-upload.php",
    "test-payment-insert.php",
    "test-payment-transfer.php",
    "verify-financial-data.php",
    "fix-duplicate-emails.php",
    "fix-payments-simple.php",
    "fix-payments-table.php",
    "create-payments-table.php",
    "generate-invoices.php"
)

foreach ($file in $testFiles) {
    Move-FileWithLog $file "$backupDir\root\$file"
}

# Temporary SQL/Migration Files
Write-Host "`n📝 Temporary SQL Files:" -ForegroundColor Yellow
$sqlFiles = @(
    "add-image-column.sql",
    "add_place_paid_column.sql",
    "check-user.sql"
)

foreach ($file in $sqlFiles) {
    Move-FileWithLog $file "$backupDir\root\$file"
}

# Temporary PHP Scripts
Write-Host "`n📝 Temporary PHP Scripts:" -ForegroundColor Yellow
$tempScripts = @(
    "add-place-paid-column.php",
    "create-student-user.php"
)

foreach ($file in $tempScripts) {
    Move-FileWithLog $file "$backupDir\root\$file"
}

# Documentation Files (keep only README.md)
Write-Host "`n📚 Documentation Files:" -ForegroundColor Yellow
$docFiles = @(
    "AUTO_PAYMENT_INFO.md",
    "BACK_BUTTON_FEATURE.md",
    "DASHBOARD_REALTIME_FEATURE.md",
    "FIX_DUPLICATE_EMAIL.md",
    "FIX_PAYMENT_ERROR.html",
    "FIX_PLACE_PAID_ERROR.md",
    "FIX_UPLOAD_ERROR_SUMMARY.md",
    "INFORMATION_IMAGE_UPLOAD.md",
    "INFORMATION_MODAL_FEATURE.md",
    "KEUANGAN_README.md",
    "MIGRATION_TO_NEW_STRUCTURE.md",
    "OCR_PAYMENT_ER_DIAGRAM.md",
    "OCR_PAYMENT_README.md",
    "OCR_PAYMENT_SYSTEM_DOCUMENTATION.md",
    "PAYMENT_FLOW_DOCUMENTATION.md",
    "PAYMENT_FORM_MAPPING.md",
    "STUDENT_LOGIN_SYSTEM.md",
    "STUDENT_ROLE_DOCUMENTATION.md",
    "STUDENT_UI_IMPROVEMENTS.md",
    "TABLE_NAME_INFO.md",
    "TROUBLESHOOT_IMAGE_UPLOAD.md",
    "CLEANUP_PLAN.md"
)

foreach ($file in $docFiles) {
    Move-FileWithLog $file "$backupDir\root\$file"
}

# Utility Batch Files
Write-Host "`n⚙️  Utility Batch/PS Files:" -ForegroundColor Yellow
$batchFiles = @(
    "clear-cache.bat",
    "create-ocr-tables.bat",
    "generate-payments.bat",
    "generate-payments.ps1",
    "run-add-column.bat",
    "run-add-major-column.bat",
    "run-fix-simple.bat",
    "setup-storage.bat",
    "storage-link.bat",
    "view-logs.bat"
)

foreach ($file in $batchFiles) {
    Move-FileWithLog $file "$backupDir\root\$file"
}

# Database Directory Cleanup
Write-Host "`n🗑️  CLEANING DATABASE DIRECTORY..." -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

Write-Host "`n📝 Temporary SQL Files:" -ForegroundColor Yellow
$dbSqlFiles = @(
    "database\add_major_column.sql",
    "database\add_manual_approval_status.sql",
    "database\fix-student-password.sql",
    "database\fix-student-user.sql",
    "database\ocr_example_queries.sql",
    "database\sample_student_user.sql",
    "database\update_existing_student_users.sql"
)

foreach ($file in $dbSqlFiles) {
    Move-FileWithLog $file "$backupDir\$file"
}

# OCR Service Cleanup
Write-Host "`n🗑️  CLEANING OCR-SERVICE DIRECTORY..." -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

Write-Host "`n📚 Documentation Files:" -ForegroundColor Yellow
$ocrDocFiles = @(
    "ocr-service\DATASET_DOWNLOADER_README.md",
    "ocr-service\NEXT_STEPS.md",
    "ocr-service\PIPELINE_IMPLEMENTATION.md",
    "ocr-service\PREVIEW_STARTUP.txt",
    "ocr-service\QUICK_IMPLEMENTATION.md",
    "ocr-service\STARTUP_BANNER.md",
    "ocr-service\TRAINING_GUIDE.md",
    "ocr-service\WORKFLOW_GUIDE.md"
)

foreach ($file in $ocrDocFiles) {
    Move-FileWithLog $file "$backupDir\$file"
}

Write-Host "`n📝 Test Files:" -ForegroundColor Yellow
$ocrTestFiles = @(
    "ocr-service\test_accuracy.py",
    "ocr-service\test_ocr.py"
)

foreach ($file in $ocrTestFiles) {
    Move-FileWithLog $file "$backupDir\$file"
}

Write-Host "`n📝 Temporary Data Files:" -ForegroundColor Yellow
$ocrTempFiles = @(
    "ocr-service\comparison_result_20260107_023419.json",
    "ocr-service\labeling_output.txt",
    "ocr-service\image_urls.txt"
)

foreach ($file in $ocrTempFiles) {
    Move-FileWithLog $file "$backupDir\$file"
}

# Keep only essential SQL in database
Write-Host "`n📌 KEEPING ESSENTIAL FILES:" -ForegroundColor Green
Write-Host "  ✓ database\create_ocr_tables.sql (OCR schema)" -ForegroundColor Gray
Write-Host "  ✓ database\dbsims.sql (main schema)" -ForegroundColor Gray
Write-Host "  ✓ README.md (main documentation)" -ForegroundColor Gray
Write-Host "  ✓ ocr-service\README.md (OCR documentation)" -ForegroundColor Gray

# Summary
Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                      CLEANUP SUMMARY                       ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Write-Host "📊 Total Files Moved: $totalFiles" -ForegroundColor Green
Write-Host "💾 Total Size: $([math]::Round($totalSize/1KB, 2)) KB" -ForegroundColor Green
Write-Host "📁 Backup Location: $backupDir" -ForegroundColor Yellow
Write-Host "`n✅ Cleanup completed successfully!" -ForegroundColor Green
Write-Host "`n⚠️  IMPORTANT:" -ForegroundColor Yellow
Write-Host "   - All files are backed up in '$backupDir'" -ForegroundColor Gray
Write-Host "   - If everything works fine after testing, you can delete the backup" -ForegroundColor Gray
Write-Host "   - To restore: move files back from backup directory`n" -ForegroundColor Gray

# Verification
Write-Host "🔍 VERIFYING PROJECT STRUCTURE..." -ForegroundColor Cyan
Write-Host "`nRoot Directory Files:" -ForegroundColor Yellow
Get-ChildItem -File | Where-Object { $_.Extension -in '.php','.sql','.md','.bat','.ps1','.html' } | Select-Object Name | Format-Table -AutoSize

Write-Host "`nDatabase SQL Files:" -ForegroundColor Yellow
Get-ChildItem database\*.sql | Select-Object Name | Format-Table -AutoSize

Write-Host "`nOCR Service Files:" -ForegroundColor Yellow
Get-ChildItem ocr-service\*.py,ocr-service\*.md,ocr-service\*.txt -Exclude requirements.txt | Select-Object Name | Format-Table -AutoSize

Write-Host "`n✨ Project is now clean and organized!`n" -ForegroundColor Green
