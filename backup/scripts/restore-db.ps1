#Requires -Version 5.1
# ============================================================================
# restore-db.ps1 - Khôi phục database từ file backup (.zip hoặc .sql)
#
#   Cách dùng:
#     powershell -ExecutionPolicy Bypass -File backup\scripts\restore-db.ps1 backup\db\daily\wood_shop_20260101_230000.zip
#     powershell -ExecutionPolicy Bypass -File backup\scripts\restore-db.ps1 C:\path\dump.sql -TargetDb wood_shop_restore_test
#
#   Mặc định restore vào DB test "wood_shop_restore_test" (KHÔNG đè DB đang chạy).
#   Muốn đè lên DB đang chạy: thêm flag -Force (có hỏi xác nhận).
# ============================================================================

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$BackupFile,

    # DB đích; không truyền -> dùng wood_shop_restore_test (hoặc DB config nếu có -Force)
    [string]$TargetDb = '',

    # Cho phép restore vào DB đang chạy (từ config). Mặc định bị cấm.
    [switch]$Force,

    # Bỏ qua bước hỏi xác nhận 'yes' (dùng cho automation/test).
    # Cần đi kèm -Force nếu muốn đè DB đang chạy; -Yes không vượt qua được cơ chế chặn đó.
    [switch]$Yes
)

$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'common.ps1')

$ProjectRoot = Resolve-ProjectRoot
$BackupRoot  = Resolve-BackupRoot
$LogFile     = Join-Path $BackupRoot 'logs\restore.log'

if (-not (Test-Path -LiteralPath $BackupFile)) {
    Write-BackupLog -LogPath $LogFile -Message "LỖI: file backup không tồn tại - $BackupFile" -Level 'ERROR'
    Write-Host "File backup không tồn tại: $BackupFile" -ForegroundColor Red
    exit 1
}

# ---- Trích xuất file .sql nếu input là .zip ----
$tempDir = $null
$sqlFile = $BackupFile
if ($BackupFile -like '*.zip') {
    $tempDir = Join-Path $env:TEMP ("wood_restore_" + [guid]::NewGuid().ToString('N'))
    Ensure-Directory $tempDir
    Write-Host "Giải nén $BackupFile ..." -ForegroundColor Cyan
    Expand-Archive -LiteralPath $BackupFile -DestinationPath $tempDir -Force
    $sqlFile = Get-ChildItem -LiteralPath $tempDir -Filter '*.sql' -Recurse | Select-Object -First 1
    if (-not $sqlFile) {
        Write-BackupLog -LogPath $LogFile -Message "LỖI: không tìm thấy file .sql trong zip" -Level 'ERROR'
        Write-Host "Không tìm thấy file .sql trong archive." -ForegroundColor Red
        exit 1
    }
    $sqlFile = $sqlFile.FullName
}
Write-Host "File dump: $sqlFile" -ForegroundColor Cyan

# ---- Xác định DB đích ----
$cfg = Get-DbConfig
$runningDb = $cfg.name
if (-not $TargetDb) {
    $TargetDb = if ($Force) { $runningDb } else { 'wood_shop_restore_test' }
}

if (-not $Force -and $TargetDb -eq $runningDb) {
    Write-BackupLog -LogPath $LogFile -Message "TỪ CHỐI restore vào DB đang chạy '$runningDb' khi chưa có -Force" -Level 'ERROR'
    Write-Host "[Từ chối] Không được restore vào DB đang chạy '$runningDb' trừ khi truyền flag -Force." -ForegroundColor Red
    exit 2
}

# Hỏi xác nhận luôn (an toàn) - trừ khi đã truyền -Yes
if (-not $Yes) {
    Write-Host ""
    Write-Host "===== XÁC NHẬN KHÔI PHỤC =====" -ForegroundColor Yellow
    Write-Host ("  File : {0}" -f $sqlFile)
    Write-Host ("  DB   : {0}  @ {1} (host: {2})" -f $TargetDb, $cfg.host, $cfg.host)
    if ($TargetDb -eq $runningDb) {
        Write-Host "  ⚠  DB đích TRÙNG với DB đang chạy! Toàn bộ dữ liệu hiện tại sẽ bị thay thế." -ForegroundColor Red
    }
    $answer = Read-Host "Nhập 'yes' để tiếp tục, hoặc Enter để huỷ"
    if ($answer -ne 'yes') {
        Write-Host "Huỷ thao tác." -ForegroundColor Yellow
        if ($tempDir) { Remove-Item -LiteralPath $tempDir -Recurse -Force }
        exit 3
    }
} else {
    Write-Host "Đã truyền -Yes, bỏ qua xác nhận. DB đích: $TargetDb" -ForegroundColor Yellow
}

# ---- Import bằng mysql.exe ----
$mysql = Find-MySqlBin -Tool 'mysql'
$logPrefix = "restore $TargetDb"

try {
    # Tạo DB đích (nếu chưa có) - dùng utf8mb4_unicode_ci đồng bộ với schema
    $createSql = "CREATE DATABASE IF NOT EXISTS $TargetDb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $connArgs = @(
        "--host=$($cfg.host)",
        "--user=$($cfg.user)",
        "--password=$($cfg.pass)",
        "--default-character-set=utf8mb4"
    )
    if ($cfg.port) { $connArgs += "--port=$($cfg.port)" }

    & $mysql @connArgs "--execute=$createSql"
    if ($LASTEXITCODE -ne 0) { throw "Tạo database $TargetDb thất bại (exit $LASTEXITCODE)" }
    Write-BackupLog -LogPath $LogFile -Message "$logPrefix : create db OK" -Level 'INFO'

    # Import: redirect file SQL vào stdin của mysql.exe qua cmd.exe.
    # Lý do dùng cmd: PowerShell pipe có thể đổi encoding; redirect nguyên khối qua cmd
    # bảo toàn từng byte của file .sql (từ mysqldump --result-file là UTF-8).
    $mysqlQuoted = '"' + ($mysql -replace '"', '""') + '"'
    $connQuoted  = ($connArgs | ForEach-Object { '"' + ($_ -replace '"', '""') + '"' }) -join ' '
    $dbQuoted    = '"' + ($TargetDb -replace '"', '""') + '"'
    $fileQuoted  = '"' + ($sqlFile -replace '"', '""') + '"'
    $cmdline = "$mysqlQuoted $connQuoted $dbQuoted < $fileQuoted"

    Write-BackupLog -LogPath $LogFile -Message "$logPrefix : bắt đầu import..." -Level 'INFO'
    cmd.exe /d /c $cmdline
    if ($LASTEXITCODE -ne 0) { throw "Import vào $TargetDb thất bại (exit $LASTEXITCODE)" }

    # ---- Xác nhận dữ liệu ----
    $invoke = { param($q) & $mysql @connArgs "--batch" "--skip-column-names" "--execute=$q" }

    $tableCount = (& $invoke "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TargetDb';").Trim()
    $getCount   = { param($t) $c = (& $invoke "SELECT COUNT(*) FROM $TargetDb.$t;" 2>$null); if ($LASTEXITCODE -eq 0) { ("{0}" -f $c).Trim() } else { '?' } }

    Write-Host ""
    Write-Host "===== KẾT QUẢ RESTORE =====" -ForegroundColor Green
    Write-Host ("  DB          : {0}" -f $TargetDb)
    Write-Host ("  Số bảng     : {0}" -f $tableCount)
    Write-Host ("  products    : {0} dòng" -f (& $getCount 'products'))
    Write-Host ("  orders      : {0} dòng" -f (& $getCount 'orders'))
    Write-Host ("  users       : {0} dòng" -f (& $getCount 'users'))
    Write-Host "============================" -ForegroundColor Green

    Write-BackupLog -LogPath $LogFile -Message "$logPrefix : OK ($tableCount bảng, products=$(& $getCount 'products'), orders=$(& $getCount 'orders'), users=$(& $getCount 'users'))" -Level 'OK'

    if ($tempDir) { Remove-Item -LiteralPath $tempDir -Recurse -Force }
    exit 0
} catch {
    Write-BackupLog -LogPath $LogFile -Message "$logPrefix : LỖI $($_.Exception.Message)" -Level 'ERROR'
    Write-Host "LỖI: $($_.Exception.Message)" -ForegroundColor Red
    if ($tempDir) { Remove-Item -LiteralPath $tempDir -Recurse -Force }
    exit 1
}