# Hệ thống Backup — WoodShop

Backup 3 lớp cho dự án WoodShop (PHP Native + Bootstrap 5):

| Lớp | Cái gì | Cơ chế |
|-----|--------|--------|
| 1. Code | Toàn bộ source | Git (commit/push định kỳ) |
| 2. Database | MySQL/MariaDB | mysqldump → zip hằng ngày |
| 3. File tĩnh | `uploads/`, `storage/` | robocopy mirror hằng ngày |

Tất cả bản backup nằm trong thư mục `backup/`. Riêng các phần sau bị `.gitignore`
(dữ liệu/dữ liệu nhạy cảm — KHÔNG commit): `db/`, `uploads/`, `logs/`,
`scripts/local.config.ps1`. Các script + README này được commit để dùng chung cho máy khác.

> **Quan trọng về git:** hiện repo CHƯA có remote. Sau khi có remote Git (vd GitHub),
> push code lên thì lớp code mới thực sự offsite. Các lớp 2 & 3 có thể offsite qua OneDrive
> (xem phần "Offsite" bên dưới).

---

## Cấu trúc thư mục

```
backup/
├── scripts/                 # Script PowerShell
│   ├── common.ps1           # Hàm dùng chung (tìm mysqldump, đọc config DB, log)
│   ├── backup-db.ps1        # Backup DB hằng ngày + tổ chức weekly/monthly
│   ├── backup-uploads.ps1   # Mirror uploads/ + storage/ + snapshot tuần
│   ├── rotate-backups.ps1   # Retention (dọn bản cũ)
│   ├── restore-db.ps1       # Khôi phục DB từ file .zip/.sql
│   └── setup-scheduled-task.ps1 # Tạo Windows Task Scheduler (chạy 1 lần)
├── db/
│   ├── daily/               # Backup hằng ngày (giữ 7 bản)
│   ├── weekly/              # Backup Chủ Nhật (giữ 4 bản)
│   └── monthly/             # Backup ngày 1 hằng tháng (giữ 12 bản)
├── uploads/                 # Mirror file tĩnh
│   ├── snapshots/           # Snapshot nén hằng tuần (Chủ Nhật)
│   ├── uploads/             # Mirror của uploads/
│   └── storage/             # Mirror của storage/
└── logs/                    # Nhật ký chạy (db-backup.log, uploads-backup.log, ...)
```

---

## Cách chạy

### Chạy tay

```powershell
# PowerShell
powershell -ExecutionPolicy Bypass -File backup\scripts\backup-db.ps1
powershell -ExecutionPolicy Bypass -File backup\scripts\backup-uploads.ps1
```

### Tự động (Windows Task Scheduler)

Mở PowerShell **Run as administrator** rồi chạy 1 lần:

```powershell
powershell -ExecutionPolicy Bypass -File backup\scripts\setup-scheduled-task.ps1
```

Script tạo 2 task:

| Task | Giờ | Làm gì |
|------|-----|--------|
| `WoodShop-Backup-DB` | 23:00 | backup DB |
| `WoodShop-Backup-Uploads` | 23:15 | mirror uploads + storage |

> Lưu ý: MySQL/MariaDB phải đang chạy lúc chạy backup — `backup-db.ps1` tự khởi động
> nếu chưa chạy (chờ tối đa 30s). Task dùng `StartWhenAvailable`: máy tắt đúng giờ sẽ
> chạy bù ngay khi máy bật lại.

---

## Offsite (bản sao ra ngoài máy)

Offsite: copy bản backup vào thư mục ngoài máy (vd thư mục con trên OneDrive/Google Drive).
Thư mục offsite được cấu hình theo thứ tự ưu tiên:

1. Tham số `-OffsiteRoot` khi gọi script (ghi đè).
2. Biến môi trường `WOODSHOP_OFFSITE_ROOT`.
3. File `backup/scripts/local.config.ps1` — **cách khuyến nghị**:
   ```powershell
   Copy-Item backup\scripts\local.config.sample.ps1 backup\scripts\local.config.ps1
   # rồi sửa:  $OffsiteRoot = "<user>\OneDrive\WoodShop-Backup"
   ```
   File `local.config.ps1` bị `.gitignore` (không commit); `local.config.sample.ps1` thì có.

Ví dụ chạy tay:

```powershell
powershell -ExecutionPolicy Bypass -File backup\scripts\backup-db.ps1 -OffsiteRoot "<user>\OneDrive\WoodShop-Backup"
```

Ngoài `db/` và `uploads-mirror/`, thư mục offsite còn có `config/` — bản sao **secret**
(file `.env`) không nằm trong git. Thư mục offsite phải là nơi **riêng tư**
(vd OneDrive cá nhân), tuyệt đối không đưa thư mục này vào git/public.

---

## Khôi phục DB (restore)

Script **an toàn**: mặc định **không** đè DB đang chạy.

```powershell
# 1) Khôi phục vào DB tạm để kiểm tra (mặc định tên wood_shop_restore_test)
powershell -ExecutionPolicy Bypass -File backup\scripts\restore-db.ps1 backup\db\daily\wood_shop_20260920_212431.zip

# 2) Khôi phục vào DB tạm khác tên
powershell -ExecutionPolicy Bypass -File backup\scripts\restore-db.ps1 -BackupFile ... -TargetDb test2

# 3) KHÔI PHỤC THẬT (đè dữ liệu hiện tại — cần -Force, DB phải đang chạy)
powershell -ExecutionPolicy Bypass -File backup\scripts\restore-db.ps1 -BackupFile ... -TargetDb woodcon_shop -Force
```

Behavior:
- Nhận file `.zip` (tự giải nén) hoặc `.sql` trực tiếp.
- Nhắc gõ `yes` để xác nhận (hoặc thêm `-Yes` để bỏ qua bước xác nhận khi tự động hóa).
- Nếu `-TargetDb` trùng DB đang chạy (`woodcon_shop`), bắt buộc có `-Force`.
- Sau restore script in số bảng + dòng của `products`/`orders`/`users` để đối chiếu.

> **Quy trình chuẩn khi sự cố:** thử restore vào DB tạm trước → so sánh số bảng/dòng →
> OK mới restore thật vào `woodcon_shop` với `-Force`.

---

## Retention (tự dọn)

Chính sách lưu giữ (áp dụng cho `backup/db/`):

| Thư mục | Giữ tối đa |
|---------|-----------|
| daily   | 7 bản  |
| weekly  | 4 bản  |
| monthly | 12 bản |

- `rotate-backups.ps1` tự chạy cuối mỗi lần `backup-db.ps1`.
- Snapshot uploads (Chủ Nhật) hiện chưa có retention tự động — dọn tay nếu cần.

---

## Kiểm tra định kỳ (checklist)

Nên làm 1 lần/tháng để chắc hệ thống chạy:

1. Đọc `backup/logs/db-backup.log` — không có dòng `[ERROR]`.
2. Kiểm tra `backup/db/daily/` có file zip mới hôm qua.
3. Restore thử vào DB tạm rồi xác nhận số bảng/dòng khớp.
4. Nhìn Task Scheduler → task `WoodShop-*` trạng thái *Ready* + lần chạy gần nhất *Succeeded*.
5. `git log` xem các commit gần đây có được push chưa.

---

## Ghi chú bảo mật

- Trong các script của `backup/` KHÔNG hardcode mật khẩu. Script đọc credential từ
  file `.env` ở gốc dự án (hoặc biến env `WOODSHOP_DB_*`).
- Secret (mật khẩu DB + key Google OAuth/reCAPTCHA) nằm trong `.env` và **không commit**
  (xem `.gitignore`). Bản mẫu được commit là `.env.example` với chỗ giữ chỗ.
- `config/config.php` + `config/db.php` là **code thuần** (đọc `.env`, không chứa secret)
  nên được commit bình thường — đừng nhét secret vào chúng.
- Truy cập web vào `backup/` đã bị chặn trong `.htaccess`.