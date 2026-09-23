# WoodCon - Shop đồ nội thất gỗ

Website thương mại điện tử bán nội thất gỗ viết bằng **PHP Native (>= 8.0)**, không framework, kèm **Bootstrap 5 + jQuery/AJAX**. Chạy ổn định trên **XAMPP** (Apache + MySQL/MariaDB).

## Yêu cầu hệ thống

- PHP >= 8.0 (khuyến nghị 8.0+ hoặc 8.2)
- MySQL 5.7+ / MariaDB 10.4+
- Apache với `mod_rewrite` (bật sẵn trong XAMPP)
- Composer (chỉ để cài `phpoffice/phpspreadsheet` phục vụ xuất Excel)

> Nếu dự án chưa có thư mục `vendor/`, chạy `composer install` trong thư mục gốc sau khi copy.

## Cài đặt nhanh với XAMPP

1. **Copy dự án** vào thư mục web của XAMPP:
   - `C:\xampp\htdocs\wood-shop` (có thể đặt ở bất kỳ thư mục con nào dưới `htdocs`, `BASE_URL` tự dò)

2. **Khởi động Apache + MySQL** từ XAMPP Control Panel.

3. **Import database**:
   - Mở phpMyAdmin → **New** tạo database mới (đặt tên tuỳ ý — mặc định là `woodcon_shop`),
   - chọn database vừa tạo → **Import** → chọn file `database/schema.sql` (đã có toàn bộ bảng + dữ liệu demo).
   - Hoặc qua dòng lệnh:
     ```
     mysql -u root -p schema < database/schema.sql
     ```
     Nếu dùng PowerShell (không hỗ trợ `<`):
     ```powershell
     Get-Content database/schema.sql -Raw | mysql -u root schema
     ```

4. **Cấu hình môi trường** (secret nằm trong file `.env` — file này bị git ignore,
   bản mẫu được commit là `.env.example`):
   ```powershell
   Copy-Item .env.example .env
   ```
   - Mở `.env`, điều chỉnh `DB_NAME` cho **khớp chính xác** tên database bạn đã tạo
     ở bước 3 (mặc định `woodcon_shop`), và dán các key Google OAuth / reCAPTCHA thật:
   ```
   # Mặc định hợp XAMPP — chỉ sửa khi môi trường của bạn khác đi
   DB_HOST   = 127.0.0.1
   DB_NAME   = woodcon_shop
   DB_USER   = root
   DB_PASS   =

   # Để trống = tắt tính năng; BẮT BUỘC cấu hình khi đưa lên production
   GOOGLE_CLIENT_ID=
   GOOGLE_CLIENT_SECRET=
   RECAPTCHA_SITE_KEY=
   RECAPTCHA_SECRET_KEY=
   ```
   > Tên file `schema.sql` không quyết định tên DB thật — chỉ cần `DB_NAME` trong `.env`
   > trỏ đúng database bạn đã import là kết nối chạy.
   >
   > Chạy **không cần** `.env` vẫn được: cấu hình mặc định đúng chuẩn XAMPP
   > (root, không mật khẩu, `woodcon_shop`). `.env` chỉ dùng để ghi đè + cấp secret.
   > Trong production hãy đặt `APP_DEBUG=0`.

5. **Cấp quyền ghi** cho các thư mục dữ liệu động (Windows thường tự có quyền):
   - `uploads/`   — ảnh sản phẩm/tin tức admin upload
   - `exports/`   — file báo cáo CSV/Excel xuất ra
   - `storage/`   — log hệ thống

6. **Truy cập**:
   - Trang chủ: `http://localhost/wood-shop`
   - Quản trị:  `http://localhost/wood-shop/quan-tri`

> `BASE_URL` được phát hiện tự động theo `DOCUMENT_ROOT`, nên khi copy vào tên thư mục khác (**ví dụ** `htdocs/woodshop`) không cần sửa cấu hình.

## Tài khoản demo (seed `database/schema.sql`)

Dữ liệu seed đã được **làm sạch** (thay dữ liệu thật bằng dữ liệu demo, `@example.com`);
mọi tài khoản demo dùng chung một mật khẩu:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Super Admin | `admin@woodcon.vn` | `demoadmin123` |
| Quản lý kho | `kho@woodcon.vn` | `demoadmin123` |
| Kế toán | `ketoan@woodcon.vn` | `demoadmin123` |
| Nhân viên bán hàng | `nv1@woodcon.vn` | `demoadmin123` |
| Nhân viên CSKH | `cskh@woodcon.vn` | `demoadmin123` |
| Nhân viên giao hàng | `shipper@woodcon.vn` | `demoadmin123` |
| Khách hàng (VIP, trust green) | `an@example.com` | `demoadmin123` |
| Khách hàng (trust yellow) | `bich@example.com` | `demoadmin123` |
| Khách hàng (trust red – cảnh báo COD) | `cuong@example.com` | `demoadmin123` |

> Đây là dữ liệu seed demo cho môi trường phát triển. Khi đưa lên production,
> **bắt buộc đổi mật khẩu** và cấu hình Google OAuth / reCAPTCHA / Goong Maps thật.

## Cấu trúc thư mục

```
wood-shop/
├─ config/         Cấu hình toàn cục (BASE_URL, DB, session) + kết nối PDO
├─ models/         Lớp nghiệp vụ (Product, Order, User, Voucher, Warranty, ...)
├─ controllers/    Điều phối request (front + admin)
├─ web/views/      Template giao diện khách hàng
│  └─ includes/    header/footer dùng chung (chỉ viết 1 nơi)
├─ admin/views/    Template khu vực quản trị
├─ assets/         CSS (theme.css, site.css, admin.css), JS, ảnh
│  └─ images/shop/ Ảnh nội dung sản phẩm/nội thất (lưu local)
├─ uploads/        Ảnh admin upload (relative path)
├─ exports/        File báo cáo xuất ra
├─ storage/        Log hệ thống
├─ database/       Cấu trúc + dữ liệu seed (schema.sql)
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
- **Bảo mật**: PDO prepared statement (chống SQLi), escape `e()` (chống XSS), CSRF token mọi form, session cookie secure/httponly, `.htaccess` chặn truy cập trực tiếp `config|includes|models|controllers|storage|exports`.

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