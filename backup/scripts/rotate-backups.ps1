#Requires -Version 5.1
# ============================================================================
# rotate-backups.ps1 - Retention policy cho backup DB
#   - backup/db/daily/   : giữ tối đa 7  file
#   - backup/db/weekly/  : giữ tối đa 4  file
#   - backup/db/monthly/ : giữ tối đa 12 file
#   Gọi từ backup-db.ps1 sau khi backup, hoặc chạy thủ công.
# ============================================================================

[CmdletBinding()]
param(
    [string]$BackupRoot = (Join-Path (Split-Path $PSScriptRoot -Parent) '')
)

$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'common.ps1')

if (-not $BackupRoot) { $BackupRoot = Resolve-BackupRoot }
$RotateLog = Join-Path $BackupRoot 'logs\rotate.log'

# Retention: thư mục -> số bản tối đa giữ lại
$policy = @{
    (Join-Path $BackupRoot 'db\daily')   = 7
    (Join-Path $BackupRoot 'db\weekly')  = 4
    (Join-Path $BackupRoot 'db\monthly') = 12
}

$totalDeleted = 0

foreach ($dir in $policy.Keys) {
    $max = $policy[$dir]
    if (-not (Test-Path -LiteralPath $dir)) {
        Ensure-Directory $dir
        continue
    }
    $files = @(Get-ChildItem -LiteralPath $dir -Filter '*.zip' -File | Sort-Object LastWriteTime -Descending)
    if ($files.Count -gt $max) {
        $toDelete = $files | Select-Object -Skip $max
        foreach ($f in $toDelete) {
            Remove-Item -LiteralPath $f.FullName -Force
            $totalDeleted++
            Write-BackupLog -LogPath $RotateLog -Message "Xóa (quá hạn retention): $($f.FullName)" -Level 'ROTATE'
        }
    }
    Write-BackupLog -LogPath $RotateLog -Message "Kiểm tra $dir : giữ $max bản, hiện có $($files.Count) bản." -Level 'ROTATE'
}

Write-BackupLog -LogPath $RotateLog -Message "Hoàn tất rotation. Tổng số file đã xóa: $totalDeleted" -Level 'ROTATE'
exit 0