# ============================================================================
# common.ps1 - Hàm dùng chung cho hệ thống backup WoodShop
# Được dot-source từ các script con trong cùng thư mục backup/scripts/
# Yêu cầu PowerShell 5.1+ (chạy được cả trên Windows PowerShell lẫn pwsh).
# ============================================================================

# ---- Đường dẫn gốc dự án (từ backup/scripts -> lên 2 cấp) ----
function Resolve-ProjectRoot {
    Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
}

# ---- Tạo thư mục nếu chưa có ----
function Ensure-Directory {
    param([string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

# ---- Ghi log chuẩn (kèm màn hình) ----
function Write-BackupLog {
    param(
        [Parameter(Mandatory = $true)][string]$LogPath,
        [Parameter(Mandatory = $true)][string]$Message,
        [string]$Level = 'INFO'
    )
    Ensure-Directory (Split-Path -Parent $LogPath)
    $line = "{0} [{1}] {2}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Level, $Message
    Add-Content -LiteralPath $LogPath -Value $line -Encoding UTF8
    Write-Host $line
}

# ---- Tự phát hiện mysqldump.exe / mysql.exe ----
# Thử theo thứ tự: C:\xampp\mysql\bin -> C:\laragon\bin\mysql\*\bin -> PATH.
# $Tool = 'mysqldump' hoặc 'mysql'
function Find-MySqlBin {
    param([Parameter(Mandatory = $true)][string]$Tool)

    $candidates = @()
    $xampp = "C:\xampp\mysql\bin\$Tool.exe"
    if (Test-Path -LiteralPath $xampp) { $candidates += $xampp }

    $laragon = Get-ChildItem "C:\laragon\bin\mysql\*\bin\$Tool.exe" -ErrorAction SilentlyContinue
    foreach ($m in $laragon) { $candidates += $m.FullName }

    $pathCmd = Get-Command $Tool -ErrorAction SilentlyContinue
    if ($pathCmd) { $candidates += $pathCmd.Source }

    foreach ($c in $candidates) {
        if (Test-Path -LiteralPath $c) { return $c }
    }

    throw "Không tìm thấy $Tool.exe. Đã thử: C:\xampp\mysql\bin, C:\laragon\bin\mysql\*\bin và PATH. " +
          "Cài MySQL/MariaDB (XAMPP hoặc Laragon) trước khi chạy backup."
}

# ---- Đọc file .env (bản mẫu: .env.example) thành hashtable KEY = VALUE ----
#   - Chỉ nhận dòng KEY=VALUE hợp lệ; bỏ qua comment/dòng trống.
#   - Giá trị bọc nháy được bỏ nháy; comment nối tiếp ( dấu # sau khoảng trắng) bị cắt.
function Read-DotEnvFile {
    param([Parameter(Mandatory = $true)][string]$Path)

    $result = @{}
    if (-not (Test-Path -LiteralPath $Path)) { return $result }

    foreach ($line in Get-Content -LiteralPath $Path -Encoding UTF8) {
        $t = "$line".Trim()
        if (-not $t -or $t.StartsWith('#')) { continue }
        if ($t.StartsWith('export ')) { $t = $t.Substring(7).TrimStart() }

        $eq = $t.IndexOf('=')
        if ($eq -lt 1) { continue }

        $key = $t.Substring(0, $eq).Trim()
        if ($key -notmatch '^[A-Za-z_][A-Za-z0-9_]*$') { continue }

        $val = $t.Substring($eq + 1).Trim()
        if ($val.Length -ge 2 -and
            (($val[0] -eq '"' -and $val[$val.Length - 1] -eq '"') -or
             ($val[0] -eq "'" -and $val[$val.Length - 1] -eq "'"))) {
            $val = $val.Substring(1, $val.Length - 2)
        } elseif ($val -match '\s#') {
            $val = ($val -split '\s#', 2)[0].TrimEnd()
        }
        $result[$key] = $val
    }
    return $result
}

# ---- Đọc credential DB từ cấu hình ----
#          Ưu tiên: biến môi trường WOODSHOP_DB_* -> file .env ở gốc dự án -> mặc định XAMPP.
#    Cùng nguồn dữ liệu với config/config.php (file này cũng đọc .env qua EnvLoader),
#    nên backup luôn trỏ đúng DB mà ứng dụng đang dùng.
#    KHÔNG hardcode mật khẩu trong script; nếu cần ghi đè tạm thời thì dùng biến môi trường:
#    + setx WOODSHOP_DB_PASS "matkhau"            (thay cấu hình thật)
function Get-DbConfig {
    $order = @('HOST', 'NAME', 'USER', 'PASS')
    $cfg   = [ordered]@{ host = ''; name = ''; user = ''; pass = ''; port = '' }

    # 1) Đọc từ biến môi trường (nếu có)
    foreach ($k in $order) {
        $envKey = "WOODSHOP_DB_$k"
        if (Test-Path "Env:$envKey") {
            $cfg[$k.ToLower()] = [Environment]::GetEnvironmentVariable($envKey)
        }
    }

    # 2) Bổ sung từ file .env (nếu còn thiếu)
    $envFile = Join-Path (Resolve-ProjectRoot) '.env'
    $vars    = Read-DotEnvFile -Path $envFile
    $map     = @{ HOST = 'DB_HOST'; NAME = 'DB_NAME'; USER = 'DB_USER'; PASS = 'DB_PASS' }
    foreach ($k in $order) {
        if ($cfg[$k.ToLower()]) { continue }
        $key = $map[$k]
        if ($vars.ContainsKey($key) -and $vars[$key] -ne '') {
            $cfg[$k.ToLower()] = $vars[$key]
        }
    }

    # 3) Mặc định (trùng với config/config.php) — chạy được cả khi CHƯA có .env
    if (-not $cfg.host) { $cfg.host = '127.0.0.1' }
    if (-not $cfg.name) { $cfg.name = 'woodcon_shop' }
    if (-not $cfg.user) { $cfg.user = 'root' }
    # $cfg.pass rỗng là hợp lệ (XAMPP: root không mật khẩu)

    # Tách host + port nếu host có dạng "host:port"
    if ($cfg.host -match '^(.*):(\d+)$') {
        $cfg.host = $Matches[1]
        $cfg.port = $Matches[2]
    }

    return $cfg
}

# ---- Kiểm tra hôm nay là Chủ Nhật? (day-of-week = 0) ----
function Test-IsSunday { [int](Get-Date).DayOfWeek -eq 0 }

# ---- Kiểm tra hôm nay là ngày 1 trong tháng? ----
function Test-IsFirstOfMonth { (Get-Date).Day -eq 1 }

# ---- Chỉ lấy đường dẫn thư mục backup của dự án ----
function Resolve-BackupRoot { Join-Path (Resolve-ProjectRoot) 'backup' }

# ============================================================================
# CẤU HÌNH LOCAL (local.config.ps1)
#   backup/scripts/local.config.ps1 KHÔNG được commit (xem .gitignore). File này
#   có thể khai báo $OffsiteRoot = 'đường dẫn backup offsite' (vd OneDrive).
#   Bản mẫu: local.config.sample.ps1 (có commit).
#   Ưu tiên khi chạy:
#     -OffsiteRoot (tham số) > biến MT WOODSHOP_OFFSITE_ROOT > local.config.ps1
# ============================================================================
$Script:LocalConfigFile = Join-Path $PSScriptRoot 'local.config.ps1'
$Script:LocalOffsiteRoot = ''
if (Test-Path -LiteralPath $Script:LocalConfigFile) {
    # Dot-source trong block con để $OffsiteRoot của cấu hình local không làm
    # ghi đè tham số cùng tên của script gọi.
    & {
        . $Script:LocalConfigFile
        if ($OffsiteRoot) { $Script:LocalOffsiteRoot = $OffsiteRoot }
    }
    if (-not $Script:LocalOffsiteRoot) {
        Write-Warning "local.config.ps1 tồn tại nhưng chưa đặt \$OffsiteRoot. Bỏ qua backup offsite."
    }
}

# ---- Giải quyết thư mục offsite theo thứ tự ưu tiên ----
function Resolve-OffsiteRoot {
    param([string]$ParamOffsiteRoot = '')
    if ($ParamOffsiteRoot) { return $ParamOffsiteRoot }
    $envRoot = [Environment]::GetEnvironmentVariable('WOODSHOP_OFFSITE_ROOT')
    if ($envRoot) { return $envRoot }
    if ($Script:LocalOffsiteRoot) { return $Script:LocalOffsiteRoot }
    return ''
}

# ---- Kiểm tra MySQL/MariaDB có phản hồi "SELECT 1" hay không ----
# An toàn với $ErrorActionPreference='Stop' (lỗi native trao đổi không ném ra ngoài).
function Test-MySqlAlive {
    param(
        [string]$MysqlBin,
        [string[]]$ConnArgs
    )
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & $MysqlBin @ConnArgs --batch --skip-column-names -e "SELECT 1;" 2>$null | Out-Null
        $ok = ($LASTEXITCODE -eq 0)
    } catch {
        $ok = $false
    } finally {
        $ErrorActionPreference = $prev
    }
    return $ok
}

# ---- Hỗ trợ khởi động MySQL/MariaDB nếu chưa chạy ----
# Ưu tiên: dịch vụ Windows -> mysqld.exe XAMPP -> FALSE (báo lỗi để script gọi xử lý).
function Start-MySqlService {
    $svc = Get-Service -ErrorAction SilentlyContinue | Where-Object {
        $_.Name -match '^mysql' -or $_.DisplayName -match 'MariaDB|MySQL'
    }
    foreach ($s in $svc) {
        try {
            if ($s.Status -ne 'Running') {
                Start-Service -Name $s.Name -ErrorAction Stop
            }
            return $true
        } catch {
            Write-Warning "Không tự khởi động được dịch vụ $($s.Name): $($_.Exception.Message)"
        }
    }

    $mysqld = 'C:\xampp\mysql\bin\mysqld.exe'
    if (Test-Path -LiteralPath $mysqld) {
        try {
            Start-Process -FilePath $mysqld -WorkingDirectory (Split-Path $mysqld -Parent) -WindowStyle Hidden
            return $true
        } catch {
            Write-Warning "Không tự khởi động được XAMPP mysqld: $($_.Exception.Message)"
        }
    }
    return $false
}