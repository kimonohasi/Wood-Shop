# WoodCon - Shop đồ nội thất gỗ

Website thương mại điện tử bán nội thất gỗ viết bằng **PHP Native (>= 8.1)**, không framework, kèm **Bootstrap 5 + jQuery/AJAX**. Chạy ổn định trên **XAMPP** (Apache + MySQL/MariaDB).

## Yêu cầu hệ thống

- PHP >= 8.1 (khuyến nghị 8.1 / 8.2+)
- MySQL 5.7+ / MariaDB 10.4+
- Apache với `mod_rewrite` (bật sẵn trong XAMPP)
- Composer — cài 2 thư viện: `phpmailer/phpmailer` (gửi mail qua SMTP) và `phpoffice/phpspreadsheet` (xuất báo cáo Excel)

> Nếu dự án chưa có thư mục `vendor/`, chạy `composer install` trong thư mục gốc sau khi copy.

## Cài đặt nhanh với XAMPP

1. **Copy dự án** vào thư mục web của XAMPP:
   - `<thư mục web>\wood-shop` (vd `htdocs\wood-shop`; có thể đặt ở bất kỳ thư mục con nào dưới `htdocs`, `BASE_URL` tự dò)

2. **Khởi động Apache + MySQL** từ XAMPP Control Panel.

3. **Import database**:
   - Mở phpMyAdmin → **New** tạo database mới (đặt tên tuỳ ý — mặc định là `woodcon_shop`),
   - chọn database vừa tạo → **Import** → chọn file `database/schema.sql` (đã có toàn bộ bảng + dữ liệu demo).
   - Hoặc qua dòng lệnh (thay `<user>`/`<dbname>` theo thông tin MySQL của bạn):
     ```
     mysql -u <user> -p <dbname> < database/schema.sql
     ```
     Nếu dùng PowerShell (không hỗ trợ `<`):
     ```powershell
     Get-Content database/schema.sql -Raw | mysql -u <user> -p <dbname>
     ```

4. **Cấu hình môi trường** (secret nằm trong file `.env` — file này bị git ignore,
   bản mẫu được commit là `.env.example`):
   ```powershell
   Copy-Item .env.example .env
   ```
   - Mở `.env`, điều chỉnh `DB_NAME` cho **khớp chính xác** tên database bạn đã tạo
     ở bước 3 (mặc định `woodcon_shop`), điền user/password MySQL của bạn, và dán các key Google OAuth / reCAPTCHA thật:
   ```
   # Ví dụ khung — file chuẩn đầy đủ kèm chú thích nằm ở `.env.example`
   APP_DEBUG=0

   DB_HOST   = 127.0.0.1
   DB_NAME   = woodcon_shop
   DB_USER   = your_mysql_user
   DB_PASS   = your_mysql_password

   # Để trống = tắt tính năng; BẮT BUỘC cấu hình khi đưa lên production
   GOOGLE_CLIENT_ID=
   GOOGLE_CLIENT_SECRET=
   RECAPTCHA_SITE_KEY=
   RECAPTCHA_SECRET_KEY=

   # (tuỳ chọn) Ghi đè BASE_URL khi Apache tự dò sai; hoặc ghi mật khẩu DB riêng
   # cho script backup → # WOODSHOP_DB_PASS=...
   # BASE_URL=https://domain-cua-ban.vn
   ```
   > Tên file `schema.sql` không quyết định tên DB thật — chỉ cần `DB_NAME` trong `.env`
   > trỏ đúng database bạn đã import là kết nối chạy.
   >
   > Không cần `.env` vẫn chạy được với cấu hình mặc định của XAMPP (database `woodcon_shop`).
   > `.env` dùng để ghi đè thông tin kết nối và khai báo secret.
   > Trong production hãy đặt `APP_DEBUG=0`. Thông tin chi tiết từng biến xem trong `.env.example`.

5. **Cấp quyền ghi** cho các thư mục dữ liệu động (Windows thường tự có quyền):
   - `uploads/`   — ảnh sản phẩm/tin tức admin upload
   - `exports/`   — file báo cáo CSV/Excel xuất ra
   - `storage/`   — log hệ thống

6. **Truy cập**:
   - Trang chủ: `http://localhost/wood-shop`
   - Quản trị:  `http://localhost/wood-shop/quan-tri`

> `BASE_URL` được phát hiện tự động theo `DOCUMENT_ROOT`, nên khi copy vào tên thư mục khác (**ví dụ** `htdocs/woodshop`) không cần sửa cấu hình.

## Tài khoản demo (seed `database/schema.sql`)

Dữ liệu seed đã được **làm sạch** (thay dữ liệu thật bằng demo `@example.com` / `@woodcon.vn` demo);
**mọi tài khoản demo dùng chung một mật khẩu: `demoadmin123`**

**Khu vực quản trị** (`/quan-tri`):

| Vai trò | Email |
|---|---|
| Super Admin | `admin@woodcon.vn` |
| Quản lý kho | `kho@woodcon.vn` |
| Kế toán | `ketoan@woodcon.vn` |
| Trợ lý Admin | `troly@woodcon.vn` |
| Nhân viên bán hàng 1 | `nv1@woodcon.vn` |
| Nhân viên bán hàng 2 | `nv2@woodcon.vn` |
| Nhân viên bán hàng 3 | `nv3@woodcon.vn` |
| Nhân viên CSKH | `cskh@woodcon.vn` |
| Nhân viên giao hàng | `shipper@woodcon.vn` |
| Nhân viên kho 2 | `kho2@woodcon.vn` |

**Khách hàng** (frontend store):

| Khách hàng | Email | Ghi chú |
|---|---|---|
| Nguyễn Văn An | `an@example.com` | trust **green** |
| Trần Thị Bích | `bich@example.com` | trust **yellow** |
| Lê Hoàng Cương | `cuong@example.com` | trust **red** (cảnh báo COD) |
| Phạm Minh Đức | `duc@example.com` | trust **green** |
| Võ Thị Em | `em@example.com` | trust **yellow** |
| Phan Tuấn Anh | `khach.tuananh@example.com` | trust **yellow** |

> Đây là dữ liệu seed demo cho môi trường phát triển. Khi đưa lên production,
> **bắt buộc đổi mật khẩu** và cấu hình Google OAuth / reCAPTCHA / Goong Maps thật.

## Cấu trúc thư mục

```
wood-shop/
├─ includes/       Hàm dùng chung (functions.php, Mailer.php, ...)
├─ config/         Cấu hình toàn cục (BASE_URL, DB, session) + kết nối PDO
├─ models/         Lớp nghiệp vụ (Product, Order, User, Voucher, Warranty, GoongService, ...)
├─ controllers/    Điều phối request (front + admin)
├─ web/views/      Template giao diện khách hàng
│  └─ includes/    header/footer dùng chung (chỉ viết 1 nơi)
├─ admin/views/    Template khu vực quản trị
├─ views/partials/ Một số partial render dùng chung
├─ ajax/           Endpoint AJAX tách riêng (gọi theo action)
├─ assets/         CSS (theme.css, site.css, admin.css), JS, ảnh
│  └─ images/shop/ Ảnh nội dung sản phẩm/nội thất (lưu local)
├─ uploads/        Ảnh admin upload (relative path)
├─ exports/        File báo cáo xuất ra
├─ storage/        Log hệ thống
├─ backup/         Script sao lưu DB + hướng dẫn (xem mục "Sao lưu dữ liệu")
├─ docs/           Tài liệu thiết kế chung (nội bộ)
├─ database/       Cấu trúc + dữ liệu seed (schema.sql)
├─ .env.example    Bản mẫu biến môi trường (commit); `.env` thật bị git ignore
└─ index.php       Front controller duy nhất (.htaccess rewrite về đây)
```

Tất cả ảnh hiển thị đều qua helper `image_url()` trong `includes/functions.php`:
đường dẫn rỗng → ảnh mặc định, `http(s)://` → giữ nguyên, còn lại → gắn `BASE_URL`. Ảnh DB lưu **relative path** (`assets/images/shop/xxx.jpg`, `products/yyy.jpg`) nên không phụ thuộc domain.

## Tính năng chính

- **Bán hàng**: danh mục phân cấp, lọc giá, tìm kiếm, giỏ hàng AJAX, thanh toán COD/bank/QR, mã giảm giá + mã freeship, trang hoàn tất đơn.
- **Chống bom hàng COD**: điểm tin cậy `trust_level` (green/yellow/red), giới hạn đơn COD giá trị cao, OTP xác thực số điện thoại, cơ chế huỷ đơn & phạt điểm.
- **Giá & VAT**: tách rõ Tạm tính → VAT → Phí ship → Tổng cộng; `% VAT` cấu hình tại **Cài đặt website** (không hardcode), lưu cột `vat_amount` riêng cho báo cáo.
- **Hạng thành viên & điểm thưởng**: Thành viên/VIP/Diamond (giảm giá + nhân điểm), tích điểm theo đơn, đổi điểm tại checkout.
- **Bảo hành**: đăng ký bảo hành theo tem/serial, tra cứu, đổi trả trong 30 ngày, khiếu nại chất lượng 48h.
- **Quản trị**: dashboard, sản phẩm/tồn kho, đơn hàng 8 trạng thái + duyệt huỷ, khách hàng, voucher, banner, tin tức, đánh giá, báo cáo tài chính, cài đặt.
- **Xuất báo cáo**: CSV + Excel (.xlsx) ở Đơn hàng, Báo cáo tài chính, Sản phẩm, Khách hàng.
- **Xác thực & tích hợp** (cấu hình tại **Quản trị → Cài đặt → Tích hợp**, key chỉ nằm phía server): đăng nhập **Google OAuth**, **reCAPTCHA v2** bảo vệ login/register/quên mật khẩu, **Goong Maps** gợi ý địa chỉ + geocode ngược ở checkout (có cache tiết kiệm quota), **eSMS** gửi OTP/ảo số điện thoại, **Gemini AI** chatbot, **SMTP** gửi email qua PHPMailer.
- **Bảo mật**: PDO prepared statement (chống SQLi), escape `e()` (chống XSS), CSRF token mọi form, session cookie secure/httponly, `.htaccess` chặn truy cập trực tiếp `config|includes|models|controllers|storage|exports`.

## Sao lưu dữ liệu

Có sẵn script PowerShell trong `backup/` (đọc `backup/README.md` để biết thêm):
- `scripts/backup-db.ps1` — dump database + thư mục dữ liệu, giữ lịch sử vài ngày, có thể push bản copy ra ngoài máy (offsite).
- Không đưa thư mục `backup/` hoặc bản dump vào git/public.

## Ghi công ảnh & icon

- Các icon liên hệ dùng icon thương hiệu **Icons8** (`assets/images/icons/icons8-*.png` — Zalo, Phone, Messenger). Theo giấy phép miễn phí của Icons8, khi sử dụng cần ghi công; thương hiệu icon thuộc về chủ sở hữu tương ứng.
- Hình ảnh sản phẩm/nội thất trong `assets/images/shop/` chỉ mang tính **minh hoạ demo**, không kèm giấy phép thương mại — cần thay bằng ảnh của bạn khi kinh doanh thật.
- Tài liệu thiết kế nội bộ nằm ở `docs/` (kèm trong repo). Nếu không muốn công khai, thêm `docs/` vào `.gitignore` trước khi push.

## ⚠️ Giới hạn pháp lý về hóa đơn / VAT

Hóa đơn tạo ra trong hệ thống này là **"hóa đơn bán hàng nội bộ"** mang tính **tham khảo/quản lý nội bộ**, **KHÔNG phải là hóa đơn điện tử hợp lệ về mặt pháp lý** theo quy định hiện hành (hóa đơn điện tử khởi tạo từ máy tính tiền phải kết nối và gửi dữ liệu tới cơ quan thuế).

- Mức thuế VAT cấu hình mặc định **8%** (có thể thay đổi tại **Cài đặt website**); thuế suất thực tế tại Việt Nam có thể ở các mức 0%, 5%, 8%, 10% tuỳ loại hàng hoá/dịch vụ và thời kỳ — admin tự cập nhật % khi có thay đổi từ cơ quan thuế.
- Nếu việc kinh doanh đạt ngưỡng doanh thu phải xuất hóa đơn điện tử theo quy định, chủ website cần tích hợp thêm một nhà cung cấp hóa đơn điện tử được cơ quan thuế công nhận (ví dụ Viettel, MISA, VNPT...). Việc này nằm ngoài phạm vi source PHP thuần của dự án.

## Khắc phục sự cố thường gặp

| Sự cố | Cách xử lý |
|---|---|
| Trắng trang / `500` ở mọi route | Chưa import DB hoặc sai creds — kiểm tra `DB_*` trong file `.env`, import lại `database/schema.sql` |
| `404` (Apache hiển thị, không phải trang 404 của app) | Chưa bật `mod_rewrite` hoặc file `.htaccess` mất |
| Cài ở thư mục khác vẫn ra link cũ | Dự án tự dò `BASE_URL`; nếu host ảo cấu hình `DocumentRoot` lệch, đặt `BASE_URL` trong file `.env` |
| Không xuất được Excel | Đảm bảo đã `composer install` (thư mục `vendor/`) |

## Thông tin liên hệ / nguồn

- Dự án demo — dữ liệu, thông tin công ty (`site_phone`, `site_address`, tài khoản ngân hàng...) là giả định phục vụ mục đích minh hoạ, có thể sửa tại **Quản trị → Cài đặt website**.