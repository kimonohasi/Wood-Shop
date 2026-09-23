#Requires -Version 5.1
# ============================================================================
# backup-uploads.ps1 - Backup file tĩnh uploads/ (robocopy mirror)
#   - Mirror thư mục uploads/ (và storage/ nếu có file người dùng) sang backup/uploads/
#   - robocopy /MIR chỉ copy phần thay đổi nên chạy hàng ngày rất nhẹ
#   - Chủ Nhật: nén toàn bộ backup/uploads/ thành 1 zip (mốc khôi phục theo tuần)
#   - Log: backup/logs/uploads-backup.log (robocopy ghi log + dòng tóm tắt)
#   - Offsite: xem local.config.ps1 (hoặc biến MT WOODSHOP_OFFSITE_ROOT / tham số -OffsiteRoot).
# Cách chạy thủ công:
#   powershell -ExecutionPolicy Bypass -File backup\scripts\backup-uploads.ps1
# ============================================================================

[CmdletBinding()]
param(
    # Thư mục offsite - ghi đè local.config.ps1. Để trống = dùng WOODSHOP_OFFSITE_ROOT / local.config.ps1.
    [string]$OffsiteRoot = ""
)

$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'common.ps1')

$ProjectRoot = Resolve-ProjectRoot
$BackupRoot  = Resolve-BackupRoot
$UploadsSrc  = Join-Path $ProjectRoot 'uploads'
$StorageSrc  = Join-Path $ProjectRoot 'storage'
$UploadsDst  = Join-Path $BackupRoot 'uploads'
$SnapshotsDir= Join-Path $BackupRoot 'uploads\snapshots'
$ShareLog    = Join-Path $BackupRoot 'logs\uploads-backup.log'
$offsite     = Resolve-OffsiteRoot -ParamOffsiteRoot $OffsiteRoot

foreach ($d in @($UploadsDst, $SnapshotsDir)) { Ensure-Directory $d }

$stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

try {
    Write-BackupLog -LogPath $ShareLog -Message '===== BẮT ĐẦU backup uploads =====' -Level 'START'
    if ($offsite) {
        Write-BackupLog -LogPath $ShareLog -Message "Offsite: $offsite" -Level 'INFO'
    } else {
        Write-BackupLog -LogPath $ShareLog -Message 'Offsite: KHÔNG cấu hình (bỏ qua bản offsite)' -Level 'INFO'
    }

    # Danh sách thư mục nguồn cần mirror (uploads luôn có; storage chỉ khi tồn tại)
    $sources = @($UploadsSrc)
    if (Test-Path -LiteralPath $StorageSrc) { $sources += $StorageSrc }

    foreach ($src in $sources) {
        $rel     = Split-Path $src -Leaf
        $dest    = Join-Path $UploadsDst $rel
        Ensure-Directory $dest
        Write-BackupLog -LogPath $ShareLog -Message "Mirror $src -> $dest" -Level 'INFO'

        # /MIR = mirror; /R:2 /W:5 = retry; /NFL /NDL = log ngắn gọn; /NP = không % progress
        robocopy $src $dest /MIR /R:2 /W:5 /NFL /NDL /NP /LOG+:$ShareLog
        $rc = $LASTEXITCODE
        if ($rc -ge 8) {
            throw "robocopy thất bại (exit code $rc) - $src"
        }
        # Note: robocopy /MIR sẽ xóa sạch $dest rồi copy lại toàn bộ? Không -
        # /MIR đồng bộ chính xác: xóa file trong đích không còn ở nguồn, chỉ copy phần thay đổi.
    }

    # ---- Chủ Nhật: tạo mốc khôi phục (snapshot zip) ----
    if (Test-IsSunday) {
        $stamp   = Get-Date -Format 'yyyyMMdd_HHmmss'
        $snapZip = Join-Path $SnapshotsDir "uploads_weekly_${stamp}.zip"
        Write-BackupLog -LogPath $ShareLog -Message "Chủ Nhật: nén snapshot -> $snapZip" -Level 'INFO'
        Compress-Archive -Path (Join-Path $UploadsDst '*') -DestinationPath $snapZip -CompressionLevel Optimal -Force
        if (-not (Test-Path -LiteralPath $snapZip)) {
            throw "Nén snapshot uploads thất bại: $snapZip"
        }
        Write-BackupLog -LogPath $ShareLog -Message "OK snapshot: $snapZip" -Level 'OK'
        if ($offsite) {
            try {
                $offDir = Join-Path $offsite 'uploads'
                Ensure-Directory $offDir
                Copy-Item -LiteralPath $snapZip -Destination (Join-Path $offDir (Split-Path $snapZip -Leaf)) -Force
                Write-BackupLog -LogPath $ShareLog -Message "Offsite snapshot: $offDir" -Level 'OK'
            } catch {
                Write-BackupLog -LogPath $ShareLog -Message "Offsite snapshot thất bại: $($_.Exception.Message)" -Level 'WARN'
            }
        }
    }

    # ---- Offsite bản mirror (đồng bộ thư mục người dùng) ----
    if ($offsite) {
        try {
            $offMirror = Join-Path $offsite 'uploads-mirror'
            Ensure-Directory $offMirror
            robocopy $UploadsDst $offMirror /MIR /R:2 /W:5 /NFL /NDL /NP
            $rc = $LASTEXITCODE
            if ($rc -lt 8) {
                Write-BackupLog -LogPath $ShareLog -Message "Offsite mirror: $offMirror" -Level 'OK'
            } else {
                Write-BackupLog -LogPath $ShareLog -Message "Offsite mirror thất bại (exit $rc)" -Level 'WARN'
            }
        } catch {
            Write-BackupLog -LogPath $ShareLog -Message "Offsite mirror thất bại: $($_.Exception.Message)" -Level 'WARN'
        }
    }

    # ---- Offsite: sao lưu .env (secret — KHÔNG nằm trong git) ----
    # config/config.php + config/db.php giờ là code thuần (không secret) nên đã commit;
    # secret (DB, Google OAuth, reCAPTCHA) nằm trong file .env ở gốc dự án, bị .gitignore.
    # Chỉ lưu trong thư mục offsite riêng tư (vd OneDrive) — không commit vào git.
    if ($offsite) {
        try {
            $cfgSrc   = $ProjectRoot
            $cfgFiles = @('.env')
            $cfgDst   = Join-Path $offsite 'config'
            Ensure-Directory $cfgDst
            foreach ($name in $cfgFiles) {
                $src = Join-Path $cfgSrc $name
                if (Test-Path -LiteralPath $src) {
                    Copy-Item -LiteralPath $src -Destination (Join-Path $cfgDst $name) -Force
                } else {
                    Write-BackupLog -LogPath $ShareLog -Message "Thiếu file $name (bỏ qua)" -Level 'WARN'
                }
            }
            Write-BackupLog -LogPath $ShareLog -Message "Config backup -> $cfgDst" -Level 'OK'
        } catch {
            Write-BackupLog -LogPath $ShareLog -Message "Config backup thất bại: $($_.Exception.Message)" -Level 'WARN'
        }
    }

    $stopwatch.Stop()
    $secs = [math]::Round($stopwatch.Elapsed.TotalSeconds, 1)
    Write-BackupLog -LogPath $ShareLog -Message "Hoàn tất backup uploads trong ${secs}s" -Level 'END'
    exit 0
} catch {
    Write-BackupLog -LogPath $ShareLog -Message "LỖI: $($_.Exception.Message)" -Level 'ERROR'
    exit 1
}