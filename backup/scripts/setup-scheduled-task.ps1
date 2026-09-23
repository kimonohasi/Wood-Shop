#Requires -Version 5.1
# ============================================================================
# setup-scheduled-task.ps1 - Tạo Windows Task Scheduler cho backup tự động
#   Chạy 1 lần, nên mở PowerShell với quyền Administrator.
#
#   Cách chạy:
#     powershell -ExecutionPolicy Bypass -File backup\scripts\setup-scheduled-task.ps1
#
#   Tạo 2 task (dùng Register-ScheduledTask):
#     - WoodShop-Backup-DB       : hằng ngày lúc 23:00 (backup-db.ps1)
#     - WoodShop-Backup-Uploads  : hằng ngày lúc 23:15 (backup-uploads.ps1)
#
#   Thiết lập an toàn:
#     - StartWhenAvailable : nếu máy tắt lúc 23h, sẽ chạy ngay khi máy bật lại.
#     - Chạy khi dùng pin, không dừng khi chuyển sang pin.
#     - Giới hạn thời gian chạy 1 giờ (nếu lâu hơn, task tự bị hủy).
#     - MultipleInstances = IgnoreNew (không chạy chồng nếu task cũ vẫn chạy).
# ============================================================================

[CmdletBinding()]
param(
    # Thời gian chạy mỗi task, ví dụ '23:00'
    [string]$TimeDb = '23:00',
    [string]$TimeUploads = '23:15'
)

$ErrorActionPreference = 'Stop'

# ---- Kiểm tra quyền admin ----
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "Cần quyền Administrator để tạo task. Mở lại PowerShell với 'Run as administrator' rồi chạy lại." -ForegroundColor Red
    exit 1
}

# ---- Cấu hình chung ----
$ScriptsDir = $PSScriptRoot
$DbScript      = Join-Path $ScriptsDir 'backup-db.ps1'
$UploadsScript = Join-Path $ScriptsDir 'backup-uploads.ps1'
$PowerShell    = (Get-Command powershell.exe).Source

if (-not (Test-Path -LiteralPath $DbScript) -or -not (Test-Path -LiteralPath $UploadsScript)) {
    Write-Host "Thiếu script backup trong $ScriptsDir" -ForegroundColor Red
    exit 1
}

$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive

$taskDefs = @(
    @{ Name = 'WoodShop-Backup-DB'; File = $DbScript; Time = $TimeDb },
    @{ Name = 'WoodShop-Backup-Uploads'; File = $UploadsScript; Time = $TimeUploads }
)

foreach ($t in $taskDefs) {
    Write-Host "Tạo task $($t.Name) ..." -ForegroundColor Cyan

    $action  = New-ScheduledTaskAction -Execute $PowerShell -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$($t.File)`""
    $trigger = New-ScheduledTaskTrigger -Daily -At $t.Time
    $settings = New-ScheduledTaskSettingsSet `
        -StartWhenAvailable `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -ExecutionTimeLimit (New-TimeSpan -Hours 1) `
        -MultipleInstances IgnoreNew `
        -RestartCount 3 `
        -RestartInterval (New-TimeSpan -Minutes 5)

    try {
        # -Force giúp ghi đè task cũ -> chạy lại script là cập nhật (idempotent)
        Register-ScheduledTask `
            -TaskName $t.Name `
            -Action $action `
            -Trigger $trigger `
            -Settings $settings `
            -Principal $principal `
            -Force | Out-Null
    } catch {
        Write-Host "Lỗi tạo task $($t.Name): $($_.Exception.Message)" -ForegroundColor Red
        exit 1
    }

    Write-Host "  OK: $($t.Name) chạy hằng ngày lúc $($t.Time)" -ForegroundColor Green
}

Write-Host ""
Write-Host "Xong! Kiểm tra: Task Scheduler -> Task Scheduler Library -> WoodShop-Backup-*" -ForegroundColor Green
Write-Host ""
Write-Host "Lưu ý: DB cần MySQL/MariaDB đang chạy. Nếu máy tắt đúng giờ, task tự chạy bù khi bật máy (StartWhenAvailable)." -ForegroundColor Yellow
exit 0