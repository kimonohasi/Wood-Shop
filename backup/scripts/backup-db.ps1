#Requires -Version 5.1
# ============================================================================
# backup-db.ps1 - Backup cơ sở dữ liệu WoodShop (mysqldump + zip)
#   - Tự phát hiện đường dẫn mysqldump.exe (XAMPP -> Laragon -> PATH)
#   - Đọc credential DB từ file .env ở gốc dự án (hoặc biến môi trường WOODSHOP_DB_*)
#   - Dump -> nén .zip -> xóa .sql gốc
#   - Chủ Nhật: copy thêm 1 bản sang weekly/ ; Ngày 1: copy thêm sang monthly/
#   - Ghi log vào backup/logs/db-backup.log ; cuối cùng gọi rotate-backups.ps1
#   - Offsite: xem local.config.ps1 (hoặc biến MT WOODSHOP_OFFSITE_ROOT / tham số -OffsiteRoot).
#   - MySQL chưa chạy sẽ tự khởi động (dịch vụ Windows hoặc XAMPP mysqld) và chờ tối đa 30s.
# Cách chạy thủ công:
#   powershell -ExecutionPolicy Bypass -File backup\scripts\backup-db.ps1
# ============================================================================

[CmdletBinding()]
param(
    # Thư mục offsite (vd thư mục con OneDrive/Google Drive) - ghi đè local.config.ps1.
    # Để trống ("") = dùng biến MT WOODSHOP_OFFSITE_ROOT hoặc local.config.ps1; còn rỗng = tắt.
    [string]$OffsiteRoot = ""
)

$ErrorActionPreference = 'Stop'

# Nạp hàm dùng chung
. (Join-Path $PSScriptRoot 'common.ps1')

$ProjectRoot = Resolve-ProjectRoot
$BackupRoot  = Resolve-BackupRoot
$DailyDir    = Join-Path $BackupRoot 'db\daily'
$WeeklyDir   = Join-Path $BackupRoot 'db\weekly'
$MonthlyDir  = Join-Path $BackupRoot 'db\monthly'
$LogFile     = Join-Path $BackupRoot 'logs\db-backup.log'

foreach ($d in @($DailyDir, $WeeklyDir, $MonthlyDir)) { Ensure-Directory $d }

$stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

try {
    Write-BackupLog -LogPath $LogFile -Message '===== BẮT ĐẦU backup database =====' -Level 'START'

    # 1) Tìm mysqldump + đọc config DB
    $mysqldump = Find-MySqlBin -Tool 'mysqldump'
    $mysql     = Find-MySqlBin -Tool 'mysql'
    $cfg       = Get-DbConfig
    $offsite   = Resolve-OffsiteRoot -ParamOffsiteRoot $OffsiteRoot

    $connArgs = @(
        "--host=$($cfg.host)",
        "--user=$($cfg.user)",
        "--password=$($cfg.pass)"
    )
    if ($cfg.port) { $connArgs += "--port=$($cfg.port)" }

    Write-BackupLog -LogPath $LogFile -Message "mysqldump: $mysqldump | DB: $($cfg.name) @ $($cfg.host)" -Level 'INFO'
    if ($offsite) {
        Write-BackupLog -LogPath $LogFile -Message "Offsite: $offsite" -Level 'INFO'
    } else {
        Write-BackupLog -LogPath $LogFile -Message 'Offsite: KHÔNG cấu hình (bỏ qua bản offsite)' -Level 'INFO'
    }

    # 1.5) Kiểm tra MySQL/MariaDB đang chạy; nếu chưa thì tự khởi động và chờ tối đa 30s
    if (-not (Test-MySqlAlive -MysqlBin $mysql -ConnArgs $connArgs)) {
        Write-BackupLog -LogPath $LogFile -Message "MySQL/MariaDB chưa phản hồi, thử khởi động..." -Level 'WARN'
        Start-MySqlService | Out-Null
        $dbUp = $false
        for ($i = 0; $i -lt 30; $i++) {
            Start-Sleep -Seconds 1
            if (Test-MySqlAlive -MysqlBin $mysql -ConnArgs $connArgs) { $dbUp = $true; break }
        }
        if (-not $dbUp) {
            throw 'Không kết nối được MySQL/MariaDB sau 30s (đã thử tự khởi động). Hãy mở XAMPP bật MySQL rồi chạy lại backup.'
        }
        Write-BackupLog -LogPath $LogFile -Message 'MySQL đã chạy - tiếp tục dump.' -Level 'INFO'
    }

    # 2) Dump sql (dùng --result-file để tránh lỗi encoding khi redirect stdout của mysqldump)
    $stamp    = Get-Date -Format 'yyyyMMdd_HHmmss'
    $sqlFile  = Join-Path $DailyDir "wood_shop_${stamp}.sql"
    $zipFile  = Join-Path $DailyDir "wood_shop_${stamp}.zip"

    $args = @(
        '--single-transaction',
        '--routines',
        '--triggers',
        '--default-character-set=utf8mb4',
        "--host=$($cfg.host)",
        "--user=$($cfg.user)",
        "--password=$($cfg.pass)"
    )
    if ($cfg.port) { $args += "--port=$($cfg.port)" }
    $args += "--result-file=$sqlFile"
    $args += $cfg.name

    Write-BackupLog -LogPath $LogFile -Message "Bắt đầu mysqldump -> $sqlFile" -Level 'INFO'
    & $mysqldump @args 2>&1 | ForEach-Object {
        if ($_ -match 'Warning') { Write-Warning $_ }
    }
    if ($LASTEXITCODE -ne 0) {
        throw "mysqldump thất bại (exit code $LASTEXITCODE)"
    }
    if (-not (Test-Path -LiteralPath $sqlFile)) {
        throw "Không thấy file dump được tạo: $sqlFile"
    }

    # 3) Nén zip rồi xóa .sql gốc
    Compress-Archive -LiteralPath $sqlFile -DestinationPath $zipFile -CompressionLevel Optimal -Force
    if (-not (Test-Path -LiteralPath $zipFile)) {
        throw "Nén zip thất bại: $zipFile"
    }
    Remove-Item -LiteralPath $sqlFile -Force

    $stopwatch.Stop()
    $sizeMB = [math]::Round((Get-Item -LiteralPath $zipFile).Length / 1MB, 2)
    $secs   = [math]::Round($stopwatch.Elapsed.TotalSeconds, 1)
    Write-BackupLog -LogPath $LogFile -Message "OK: $zipFile | ${sizeMB} MB | ${secs}s" -Level 'OK'

    # 4) Bản sao theo chu kỳ
    if (Test-IsSunday) {
        Copy-Item -LiteralPath $zipFile -Destination (Join-Path $WeeklyDir (Split-Path $zipFile -Leaf)) -Force
        Write-BackupLog -LogPath $LogFile -Message "Chủ Nhật: copy sang weekly/" -Level 'INFO'
    }
    if (Test-IsFirstOfMonth) {
        Copy-Item -LiteralPath $zipFile -Destination (Join-Path $MonthlyDir (Split-Path $zipFile -Leaf)) -Force
        Write-BackupLog -LogPath $LogFile -Message "Ngày 1: copy sang monthly/" -Level 'INFO'
    }

    # 5) Offsite (nếu đã cấu hình)
    if ($offsite) {
        try {
            $offDir = Join-Path $offsite 'db'
            Ensure-Directory $offDir
            Copy-Item -LiteralPath $zipFile -Destination (Join-Path $offDir (Split-Path $zipFile -Leaf)) -Force
            Write-BackupLog -LogPath $LogFile -Message "Offsite: $offDir" -Level 'OK'
        } catch {
            Write-BackupLog -LogPath $LogFile -Message "Offsite thất bại: $($_.Exception.Message)" -Level 'WARN'
        }
    }

    Write-BackupLog -LogPath $LogFile -Message '===== KẾT THÚC backup database =====' -Level 'END'

    # 6) Dọn theo retention
    & (Join-Path $PSScriptRoot 'rotate-backups.ps1') -BackupRoot $BackupRoot

    exit 0
} catch {
    # Dọn file dump dở nếu có (mysqldump thất bại có thể để lại .sql rỗng)
    if ($sqlFile -and (Test-Path -LiteralPath $sqlFile)) {
        Remove-Item -LiteralPath $sqlFile -Force -ErrorAction SilentlyContinue
    }
    Write-BackupLog -LogPath $LogFile -Message "LỖI: $($_.Exception.Message)" -Level 'ERROR'
    exit 1
}