# THIẾT KẾ CHUNG — Bộ khung "Shop" dùng lại được cho nhiều ngành

### Trạng thái đối chiếu code

- Cập nhật: **2026-09-21** · commit **`22087de`** · nhánh `docs/backup-blueprint`
- Phạm vi: đối chiếu tài liệu với mã nguồn thật (models, config, `.htaccess`, `database/schema.sql`, `backup/scripts/*.ps1`).

| Khu vực | Mức đối chiếu |
|---|---|
| §1–§3 Tổng quan / Routing | Có |
| §4 Tầng dữ liệu (`db.php`, `Base`, schema 45 bảng) | Có |
| §5 Bảo mật (`.htaccess` 44 dòng) | Có |
| §6–§7 Vòng đời đơn 9 trạng thái | Có |
| §8 Tính giá & thuế (`Checkout`) | Có |
| §9 Vận chuyển · §10 Voucher | Có |
| §11 Thành viên & điểm | Có |
| §12 Chống COD (`TrustEngine`) | Có |
| §13 Giao diện / admin | Một phần (chỉ phân quyền — không đối chiếu từng màn hình) |
| §14 Checklist khởi tạo | Có |
| §15–§16 Sao lưu (`backup/scripts/*.ps1`) | Có (hạn chế đã biết: §16.7) |

> File tổng hợp một "blueprint" chuẩn để khởi tạo một dự án cửa hàng PHP Native
> mới (quần áo, giày dép, điện tử, nội thất, F&B...) dựa trên dự án mẫu
> **WoodCon / wood-shop**. Chỉ cần thay các chỗ đánh dấu `[THEO NGÀNH]`.

---

## 1. Tổng quan

- **Mô hình**: PHP Native thuần (không framework), MySQL/MariaDB, chạy XAMPP.
- **Giao diện**: Bootstrap 5 + jQuery/AJAX, UI tiếng Việt, tiền tệ VNĐ định dạng `15.900.000 đ`.
- **Hai khu vực tách bạch**:
  - Bên ngoài (khách) — thư mục `web/`, điều phối bởi `index.php`.
  - Bên trong (admin) — thư mục `admin/`, điều phối bởi `admin/index.php`.
- **Kiến trúc lớp**: Controller → Model (`extends Base`, namespace `WoodCon`) → View.
- **Điểm nổi bật đã có sẵn** (dùng lại nguyên khối khi cần):
  - Vòng đời đơn hàng 9 trạng thái kiểu Shopee + side-effect chuẩn (trừ/hoàn kho, doanh thu, điểm, bảo hành).
  - Tách VAT nhiều mức thuế (0/5/8/10%) ngay trong giá niêm yết.
  - Voucher 2 loại (discount / freeship) theo `Voucher::validate`.
  - Vận chuyển 2 loại (shop tự giao lắp đặt / hãng vận chuyển qua API).
  - Hạng thành viên + điểm thưởng + quyền lợi theo hạng.
  - Module chống bùng hàng COD (TrustEngine) — **là module tuỳ chọn**, xem §12.
  - Sao lưu & khôi phục 3 lớp (DB + file tĩnh + offsite) — bộ script có sẵn, xem §16.

---

## 2. Stack & môi trường

| Thành phần | Giá trị |
|---|---|
| PHP | 8.0+ (mẫu chạy 8.2.12) |
| Ngôn ngữ mã nguồn | PHP thuần, PDO prepared statements, `declare(strict_types=1)` |
| DB | MySQL/MariaDB, charset `utf8mb4`, engine InnoDB |
| Giao diện | Bootstrap 5 (CDN hoặc bản lưu cục bộ) + jQuery + AJAX |
| Web server | Apache (XAMPP), rewrite qua `.htaccess` |
| Autoload | Không dùng Composer classloader; nạp thủ công, namespace `WoodCon` |
| Lưu file | Ảnh do người dùng upload vào `uploads/` (có `.htaccess` riêng chặn chạy PHP), file bền vững/đệm vào `storage/`, export ra `exports/` |

### Cấu trúc thư mục chuẩn (copy nguyên bộ)
```
[project-root]/
├── admin/                  # Giao diện quản trị (điều phối qua route quan-tri → AdminController)
├── ajax/                   # Endpoint AJAX nhỏ dùng chung (vd chat hỗ trợ)
├── api/                    # API nội bộ/nghiệp vụ (vd Viettel Post, Goong Maps, Google OAuth)
├── assets/                 # css/, js/, images/, icons/
├── backup/                 # Sao lưu 3 lớp: db/, uploads/ mirror, snapshot config (xem §16)
├── config/                 # config.php, db.php (bản THẬT, bị .gitignore) + ai-provider.php (sạch, có commit)
│                           # + bản MẪU *.sample.php (được commit, chỗ giữ chỗ)
├── controllers/            # BaseController + từng controller theo module
├── database/               # schema.sql duy nhất (schema + data demo đã ẩn danh — KHÔNG export DB thật)
├── docs/                   # Tài liệu nội bộ (bị .htaccess chặn web, xem §5)
├── exports/                # Xuất file (Excel/CSV báo cáo)
├── includes/               # functions.php (e, csrf, format_money, get_setting, write_log...) + views dùng chung
├── models/                 # Base + từng model nghiệp vụ
├── storage/                # File bền vững/đệm (chữ ký...)
├── uploads/                # Ảnh do người dùng upload (có .htaccess riêng chặn chạy PHP)
├── vendor/                 # (nếu có) thư viện lẻ
├── views/                  # (WoodCon) partial dùng chung — dự án mới chuẩn hoá về web/views/
├── web/                    # Khối bên ngoài: views/ (layout + partial khách), includes/ (header/footer)
├── .gitignore              # Chặn secret (.env), backup dữ liệu, uploads, logs
├── .htaccess               # Rewrite + bảo mật (xem §5)
└── index.php               # Front controller bên ngoài
```

> Ghi chú: WoodCon hiện có **cả** `views/` (partial dùng chung) **lẫn** `web/views/` (view phía
> khách). Khi tạo dự án mới nên **chuẩn hoá về một nơi**, khuyến nghị `web/views/` (như hiện tại)
> và bỏ `views/`.

---

## 3. Routing

### 3.1 Front controller `index.php` (phía khách)
- Đường dẫn lấy từ `$_GET['route']` hoặc tự dò `REQUEST_URI`, cắt phần tiền tố (vd `/wood-shop`).
- **Switch theo segment đầu** (đã-tiếng-Việt-hoá), mỗi case gọi controller tương ứng
  (danh sách dưới đây khớp `index.php` code thật của WoodCon):
  - `''` → `HomeController` (trang chủ)
  - `danh-muc` → `CategoryController`
  - `san-pham` → `ProductController`
  - `tim-kiem` → `SearchController`; `tim-kiem-ket-qua`, `goi-y-tim-kiem` → AJAX trả JSON
  - `gio-hang` → `CartController` (cookie/session khi khách chưa đăng nhập)
  - `thanh-toan` → `CheckoutController`; `hoan-tat` → trang sau khi đặt đơn xong
  - `tra-cuu-don-hang` → `TrackController` (tra cứu đơn theo mã)
  - `tra-cuu-bao-hanh` → `WarrantyController` (nhánh C — có bảo hành)
  - `khuyen-mai` → trang khuyến mãi chung
  - `tin-tuc`, `tin` → `NewsController`
  - `quyen-loi` → `BenefitsController`
  - `lien-he` → `ContactController`
  - `tai-khoan` → `AccountController` (xem §3.2)
  - `dang-nhap`, `dang-ky`, `quen-mat-khau`, `google` (OAuth Google), `dang-xuat` → `AuthController`
  - `quan-tri` → chuyển tiếp `AdminController::dispatch(...)` (phía `admin/`)
  - default → `404.php`.

> Ghi chú: danh sách route trên khớp `index.php` hiện tại — chỉ dùng các route này.
> Không tạo route đơn lẻ `xac-thuc`, `bao-hanh`, `voucher`, `admin` (đã nằm trong các controller).

### 3.2 Phân phối trong từng controller
Mọi controller có `dispatch(array $seg)`: `$seg[0]` = action, `$seg[1]/$seg[2]` = tham số.

`AccountController` (yêu cầu đăng nhập, trừ khi ghi chú):
- `''` → trang đăng nhập (đảo ngược với các action còn lại)
- `don-hang`, `order` (alias đặc biệt: guest vẫn truy cập được trang sau đặt đơn)
- `yeu-thich`, `diem-thuong`, `doi-mat-khau`.

`AdminController` (yêu cầu `$_SESSION['admin_id']`, có phân quyền):
- `san-pham`, `danh-muc`, `don-hang`, `hoa-don`, `duyet`, `danh-gia`, `voucher`,
  `khach-hang`, `banner`, `tin-tuc`, `thuong-hieu`, `bao-cao`, `thue`, `bao-hanh`,
  `lien-he`, `cai-dat`, `van-chuyen`, `nhan-su` (nhân viên), `phan-quyen` (roles),
  `nhat-ky` (audit log), `doanh-thu-ajax`, `don-hang-theo-trang-thai-ajax`, `tim-kiem-nhanh`,
  cùng các trang con của Cài đặt: `ai`, `maps`, `sms`, `smtp`.

### 3.3 Nguyên tắc
- Route/Slug = chuỗi **KHÔNG DẤU** (điều hướng tiếng Việt hoá bằng cách bỏ dấu, thay dấu cách
  bằng dấu gạch ngang) — SEO thân thiện.
- `.htaccess` rewrite: nếu file/thư mục không tồn tại → `index.php?route=$1`.
- Mọi form POST phải xác thực CSRF (`csrf_field()` / `verify_csrf()`).
- Request bất đồng bộ nên là AJAX (`BaseController::isAjax()`) trả JSON.

---

## 4. Tầng dữ liệu

### 4.1 Kết nối (`config/db.php`)
- Lớp `WoodCon\Database` dạng singleton → trả `PDO` (kết nối theo hằng số trong `config/config.php`).
- Snapshot: charset `utf8mb4_unicode_ci`, `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION` (lỗi
  ném exception, không "ẩn lỗi"), `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC`,
  `ATTR_EMULATE_PREPARES => false`, `MYSQL_ATTR_INIT_COMMAND` set utf8mb4 + `time_zone = '+07:00'`.
- `db.php` **không tự chứa credential** — chỉ đọc hằng số `DB_HOST/DB_NAME/DB_USER/DB_PASS`
  từ `config/config.php`; các hằng số đó nhận giá trị từ file `.env` ở gốc dự án
  (bản mẫu `.env.example` có chỗ giữ chỗ; xem `config/EnvLoader.php`).
- Khi kết nối thất bại: bắt `PDOException` → `http_response_code(500)` + thoát với thông báo
  chung "Lỗi kết nối cơ sở dữ liệu..." (không lộ chi tiết lỗi ra ngoài).

### 4.2 Model base (`models/Base.php`)
- Khai báo `protected static string $table`.
- Helper tĩnh: `all()`, `find(id)`, `findBy(col, val)`, `where(...)` — tất cả prepared statement.
- Có `insert()` để ghi nhanh.

### 4.3 Schema chuẩn (bảng lõi — giữ nguyên tên khi tái sử dụng)
Nhóm danh mục & sản phẩm:
- `settings` — cấu hình khoá/giá trị (`get_setting('key', default)`).
- `admins` — tài khoản admin: cột `role` enum (`superadmin/admin/employee`, ghi vào session
  `admin_role`) **và** `role_id` tham chiếu `roles` (RBAC chi tiết qua `role_permissions` → `permissions`).
- `categories`, `brands`.
- `products` — giá `price` + `sale_price` (giá thực = `effectivePrice`), `quantity`, `weight_kg`, kích thước `dim_*`, `warranty_months`, cờ `status`, `ship_supports_type1/type2`.
- `product_images`, `product_videos`, `product_variants` — biến thể `[THEO NGÀNH]`.
- `vouchers`, `banners`, `reviews`, `comments`, `news`, `contacts`.

Nhóm khách hàng & đơn:
- `users` — kèm `points`, `trust_level`, `success_orders`, `failed_orders`, `trust_score`, `membership_tier_id`.
- `membership_tiers`, `membership_benefits` — hạng: tên, % giảm, ngưỡng điểm, quyền lợi theo hạng.
- `carts` (+ cookie/session khi khách chưa đăng nhập).
- `orders` — snapshot toàn bộ: khách, địa chỉ (`address/ward/district/city/delivery_lat/lng`), từng thành phần tiền, `payment_method/payment_status`, `order_status`, `trust_level_at_order`, `risk_flag`, `otp_verified`, `shipping_type`, `install_*`, `voucher_code`, `points_used`.
- `order_items` — snapshot tên/ảnh/đơn giá/`vat_rate`.
- `invoices`, `invoice_items` — hoá đơn + chi tiết (kèm credit note);
  `order_cancel_requests`, `refunds`, `deleted_order_logs`.
- `warranties`, `warranty_history`.
- `points_transactions` — ghi `earn`/`spend`; `wishlists`.
- `trust_logs`, `otp_verifications`, `notifications`, `logs`, `chat_messages`,
  `ai_error_log`, `audit_logs` (nhật ký hành động admin).

Bảng nghiệp vụ riêng của ngành (WoodCon có, dự án mới tự quyết):
- `province_distances`, `ship_fee_brackets` (Loại 1 — shop tự giao) + `carrier_rates`
  (chỉ giữ dữ liệu cũ để tương thích — Loại 2 gọi Viettel Post API, xem §9).
- `tax_rates` (mức thuế `0/5/8/10` theo NĐ 15/2022/NĐ-CP, mặc định 8).
- `goong_cache`, `vtp_categories` (bản đồ Goong / danh mục Viettel Post của WoodCon).

> Nguyên tắc chung: giá luôn lưu **đã gồm VAT** (VAT bán ra); mọi chiết khấu lưu
> **snapshot %** tại thời điểm đặt (không tính lại khi xem đơn cũ).

---

## 5. Bảo mật (bản sao chuẩn từ `.htaccess`)

1. **Chặn truy cập trực tiếp thư mục nhạy cảm** với `RewriteRule ^(config|includes|models|controllers|storage|exports|web|admin|backup)/ - [F,L]`
   (khu vực admin điều phối qua route `quan-tri` → `AdminController::dispatch` trong `index.php` — thư mục `admin/` chỉ chứa giao diện view, mọi lối vào đều qua front controller; `backup/` không có entry web).
2. **Chặn tải xuống file nhạy cảm theo đuôi** (áp dụng ở mọi thư mục, kể cả `docs/`):
   `FilesMatch "\.(env|env\..+|ini|log|sql|sqlite|sqlite3|sh|json|lock|gitignore|gitattributes|md|yml|yaml|dist|swp|swo|bak|backup)$"` → `Require all denied`.
   → Mọi tài liệu `*.md` (kể cả file thiết kế này) đều bị chặn web; không cần thêm quy tắc.
3. **Chặn tài liệu nội bộ ở gốc** (file không đuôi nguy hiểm): `^(composer\.(json|lock)|package(-lock)?\.json|\.env(\..+)?|README\.md|PLAN_.*|PROMPT_.*|prompt_.*|logic_.*|AGENTS\.md|kilo\.json|\.user\.ini)$`.
4. **File ẩn** `^\..*` → `Require all denied`; `Options -Indexes` + `ServerSignature Off`; uploads có `.htaccess` riêng chặn chạy PHP (phòng shell).
5. **Header bảo mật**: X-Content-Type-Options: nosniff, X-Frame-Options: SAMEORIGIN, Referrer-Policy: strict-origin-when-cross-origin, Permissions-Policy (camera/mic/geolocation off), unset X-Powered-By.
6. **Rewrite mềm**: mọi path (`REQUEST_FILENAME`) không phải file/thư mục tồn tại → `index.php?route=$1`.
7. Mã ứng dụng: CSRF token mọi form, `password_hash`/`password_verify`, prepared statements toàn bộ, không log secret.
8. Secret theo quy tắc **"chỉ commit bản mẫu"**: mọi secret nằm trong file `.env`
   (bị `.gitignore`, nguồn cấp giá trị kết nối cho `config/config.php`) — chỉ commit
   bản mẫu `.env.example` có chỗ giữ chỗ. Dự án mới: copy `.env.example` → `.env`,
   điền secret thật, giữ `.env` ở máy (bị `.gitignore`). Riêng `config/config.php`,
   `config/db.php`, `config/EnvLoader.php`, `config/ai-provider.php` **ĐƯỢC commit**
   (code thuần, không chứa secret — `EnvLoader` nạp `.env`; key AI nằm trong setting
   `ai_api_key`, gửi trực tiếp qua header HTTP). Backup dữ liệu (dump DB, mirror,
   `backup/scripts/local.config.ps1`) chứa secret cũng nằm **ngoài git** (xem §16).
   Secret để TRỐNG trong `.env` = tính năng tắt (reCAPTCHA bỏ qua xác thực, Google
   OAuth ẩn) — bắt buộc cấu hình đủ trước khi đưa lên production.
9. **Lịch sử git cũ vẫn chứa secret và dữ liệu thật.** Kho mẫu này từng có commit chứa
   `config/config.php` thật và file dump DB đầy đủ; đã dọn sạch ở HEAD (thay secret bằng
   chỗ giữ chỗ, xoá dữ liệu thật khỏi `database/schema.sql`) nhưng giá trị cũ **vẫn nằm
   trong lịch sử commit**. Trước khi đặt repo PUBLIC: tạo nhánh `orphan` sạch (bỏ lịch sử)
   hoặc ghi đè history, và **rotate (thay mới) toàn bộ khoá Google OAuth / reCAPTCHA / API**
   từng xuất hiện trong repo.
10. Pre-commit hook (chặn commit `config` thật, `*.sql` ngoài `database/`, pattern secret)
    nằm trong `.git/hooks/` — **không được clone/copy theo**. Dự án mới: sau `git init`
    phải cài lại hook (xem §14.1).

---

## 6. Logic nghiệp vụ — 4 nhánh chuẩn

Áp dụng **nhánh đời hàng** phù hợp ngành:

- **Nhánh A — Bán lẻ giao hàng (kiểu Shopee / TMĐT)** — áp dụng mặc định cho:
  quần áo, giày dép, điện tử, đồ gia dụng. Vòng đời đơn 9 trạng thái (chi tiết §7).
- **Nhánh B — F&B / bán mang đi:** không bảo hành; trạng thái đơn gọn
  (chờ → đang làm → hoàn thành/đã giao); không áp dụng COD OTP.
- **Nhánh C — Hàng lâu bền + bảo hành (kiểu Phong Vũ / nội thất):** vòng đời nhánh A
  **cộng thêm** module `warranties` (phiếu bảo hành sinh khi `delivered`, trạng thái,
  lịch sử sửa chữa, gia hạn). WoodCon là mẫu chuẩn của nhánh này.
- **Nhánh D — Dịch vụ đặt lịch / đặt bàn:** thêm `appointments` (thời gian, nhân viên,
  trạng thái chờ/đã xác nhận/quá giờ/huỷ), không trừ kho, không ship.

Kết hợp: WoodCon = **A + C** + một phần B (điểm thưởng, freeship theo hạng).

## 7. Vòng đời đơn hàng (Nhánh A/C) — chuẩn 9 trạng thái

```
pending ───────────────► cancelled        (khách tự huỷ khi còn chờ xác nhận)
   │
   ├──► manual_verifying ──► confirmed     (soát thủ công; huỷ được nếu từ chối)
   │
   └──► confirmed ──► preparing ──► shipping ──► delivered ✓
                                   │
                                   └──► delivery_failed ──► returned
                                         │
                                         └──► shipping (giao lại, tối đa `max_delivery_fail` = 3 lần)
```

Bảng nhãn trạng thái (`Order::STATUS_LABEL`) — dùng lại nguyên:

| status | Label | Badge |
|---|---|---|
| `pending` | Chờ xác nhận | warning |
| `manual_verifying` | Chờ xác minh thủ công | danger |
| `confirmed` | Đã xác nhận | info |
| `preparing` | Đang chuẩn bị hàng | secondary |
| `shipping` | Đang giao hàng | primary |
| `delivery_failed` | Giao hàng thất bại | dark |
| `delivered` | Đã giao thành công | success |
| `returned` | Hoàn hàng về shop | danger |
| `cancelled` | Đã hủy | secondary |

Map chuyển trạng thái hợp lệ (`Order::transition`, giữ nguyên khi tái code):

| Từ | Có thể sang |
|---|---|
| `pending` | `manual_verifying`, `confirmed`, `cancelled` |
| `manual_verifying` | `confirmed`, `cancelled` |
| `confirmed` | `preparing`, `cancelled` |
| `preparing` | `shipping`, `cancelled` |
| `shipping` | `delivery_failed`, `delivered`, `returned` |
| `delivery_failed` | `shipping` (giao lại), `returned`, `delivered` |
| `returned`, `cancelled` | (trạng thái cuối) |

> Huỷ chỉ hợp lệ khi đơn còn ở `pending`/`manual_verifying`/`confirmed`/`preparing`.
> Quá `max_delivery_fail` (mặc định 3) lần `delivery_failed` → tự chuyển `returned`.

**Side-effect chuẩn khi chuyển trạng thái** (bắt buộc giữ):
- Trừ kho tại thời điểm **đặt** (trong transaction), hoàn kho khi **huỷ/hoàn**.
- Chỉ tính **doanh thu + tích điểm + sinh phiếu bảo hành** khi `delivered`.
- Đơn đã thanh toán trước mà huỷ/hoàn → phải tạo `refunds` (gợi ý `payment_status='refunded'`).
- Mã đơn: `Order::generateCode()` = tiền tố ngành (WoodCon dùng `WC` + `ymd` + 4 hex ngẫu nhiên) → `[THEO NGÀNH]`, ví dụ quần áo dùng `CL`, giày `SH`.

---

## 8. Tính giá & thuế (chuẩn từ `Checkout::applyVouchers` trong code)

Nguyên tắc: **giá niêm yết ĐÃ GỒM VAT** (hướng dẫn theo NĐ 15/2022/NĐ-CP).

1. `gross_goods` = Σ `unit_price × quantity` (giá bán thực = `Product::effectivePrice`).
2. **Giảm hạng thành viên** (snapshot % tính trên tiền hàng, áp trước voucher).
3. **Voucher discount** (một cơ sở riêng, không chồng vào freeship).
4. Tách VAT **theo từng mức thuế của từng sản phẩm** (`vat_breakdown`):
   `net = gross / (1 + rate/100)`, `vat = gross − net`.
5. **Phí ship sau freeship + phí lắp đặt** = doanh thu dịch vụ đã gồm VAT:
   mỗi phí tách **một dòng riêng** (`vat_shipping`, `vat_install`) với mức thuế cấu hình
   độc lập (`tax_shipping_rate`, `tax_install_rate`, mặc định 5%). Đây là số **để theo dõi/
   thống kê** — không cộng chồng thêm lên tổng (phí đã nằm trong phần gross dịch vụ).
6. `total_amount = subtotal(net sp) + vat(sản phẩm) + net_shipping + install_fee`.
7. 1 điểm thưởng = `setting('point_value', 1000)đ`; giới hạn `min(số dư, floor(total/point_value))`.
8. Cảnh báo khuyến mại > 50% giá niêm yết → ghi chú tuân thủ **NĐ 81/2018/NĐ-CP** vào note (bắt buộc, không chặn đơn).

Công thức cốt lõi (nhớ giữ nguyên khi tái code):

```php
$net = $gross / (1 + $rate / 100); // tách VAT ngược
$vat = $gross - $net;
$finalGoods = max(0, $grossGoods - $tierAmount - $discountAmount); // sau chiết khấu
```

---

## 9. Vận chuyển — 2 loại (module dùng lại)

- **Loại 1 — Shop tự giao (giao & lắp đặt tại nhà):** cấu hình kho/trụ sở + bảng
  `province_distances` + `ship_fee_brackets` (bậc km); phí lắp đặt `install_fee` theo
  sản phẩm/danh mục, chỉ tính khi khách chọn lắp đặt.
- **Loại 2 — Hãng vận chuyển qua API:** gọi Viettel Post `/v2/order/getPrice` theo
  địa chỉ + khối lượng (WoodCon có `ViettelPost::calculateForCity`); không còn bảng giá vùng tay.
- Zone Loại 2: `noithanh` / `lientinh` / `lienmien` với `Shipping::detectZone($city)`.
- Ngưỡng free ship: `get_setting('vtp_freeship_threshold')` / giá trị chung; **freeship
  KHÔNG áp dụng chồng khi khách dùng mã freeship voucher** (ưu tiên voucher).
- Mỗi sản phẩm bật/tắt từng loại qua `ship_supports_type1/type2`.
- Dự án mới: thay Viettel Post bằng hãng bất kỳ qua một interface duy nhất
  (giữ tên `CarrierAdapter`), giữ nguyên luồng `checkout`. **Khuyến nghị (chưa có trong
  code hiện tại)** — mã hiện gọi trực tiếp `ViettelPost::calculateForCity`.

---

## 10. Voucher (module dùng lại)

- **2 loại**: `discount` (giảm tiền hàng, min-order/scope) và `freeship` (trừ phí ship).
- `Voucher::validate(string $code, string $type, float $orderValue, float $shippingFee = 0,
  ?int $userId = null, array $items = []): array` (trả danh sách lỗi; `$items` dùng cho scope):
  - Kiểm tra min-value, hạng thành viên tối thiểu, scope `category_id` sản phẩm.
  - `Voucher::incrementUsed()` tăng lượt dùng sau khi đặt thành công.
- Lộn loại (dùng mã discount ở ô freeship) → trả lỗi "mã không hợp lệ" (đã test trong code).

---

## 11. Thành viên & điểm thưởng (module dùng lại)

`User` cung cấp sẵn:
- `register / login(account, password) / createWithGoogle(email, name, googleId, avatar)`.
- `resetPassword(string $account, string $otp, string $new, string $method = 'phone')` — quên mật khẩu qua OTP (SĐT hoặc email).
- `updateProfile`, `wishlist(userId, page, perPage)`, `inWishlist(userId, productId)`, `canReview/hasReviewed/addReview`.
- `pointsHistory` (trang lịch sử), `membershipTier(userId)`, `nextMembershipTier`.
- `membership_tiers` gồm cột: tên hạng, `discount_percent`, ngưỡng điểm, `membership_expired` (per-user).
- Điểm: nhận khi `delivered`; chi trong checkout (`computePoints`), ghi `points_transactions` (earn/spend) — giao dịch tách biệt với đơn.

Trang quyền lợi (`quyen-loi`) hiển thị: bảng hạng, % giảm, ngưỡng, quyền lợi freeship, lịch sử điểm.

---

## 12. Chống bùng hàng COD — **TUỲ CHỌN** ⚠️

> Module này **không phải lúc nào cũng cần**. Theo thoả thuận: chỉ áp dụng cho dự án
> tương lai **khi user yêu cầu**. Nếu bán trả trước 100% hoặc không nhận COD thì **bỏ qua §12 hoàn toàn**.

### 12.1 Nguyên lý
"Ma sát tăng theo rủi ro": khách tốt không bị cản, khách nghi ngờ gặp đủ rào cản
để họ xác nhận thật sự muốn mua. Không chặn doanh thu, chỉ lọc khách ảo.

### 12.2 3 tier (cá nhân theo SĐT):
- **Xanh (green)** — khách quen: COD bình thường, không OTP.
- **Vàng (yellow)** — khách mới/vãng lai: bắt buộc **OTP** xác thực SĐT.
- **Đỏ (red)** — từng từ chối nhận: **cấm COD**, chỉ thanh toán trước (`bank/QR/wallet`).

Ngoại lệ: hạng thành viên **VIP/Diamond còn hiệu lực** được miễn OTP (tuyệt đối không áp cho tier Đỏ).

### 12.3 Tín hiệu rủi ro (`TrustEngine::evaluateSignals`) — ≥2 tín hiệu → `manual_verifying`:
1. ≥ `cod_max_orders_per_hour` (mặc định 3) đơn 1 giờ cho cùng SĐT.
2. SĐT không hợp lệ (regex `^(0|\+84)[0-9]{9,10}$`).
3. Địa chỉ mơ hồ (trống / <10 ký tự / không chứa chữ số).
4. Tên người nhận "fake" (chuỗi ≥12 ký tự toàn chữ theo regex).

### 12.4 Feedback loop (`TrustEngine::onDeliveryResult`):
- `delivered` → `+1 success_orders`; Vàng đạt 2 đơn thành công liên tiếp → lên Xanh; `trust_score +20` (cap 150).
- `refused` → `failed_orders +1`, xuống **Đỏ**; `trust_score −50` (floor −100).
- Mọi sự kiện ghi `trust_logs`.

### 12.5 Bổ trợ mặc định trong `Checkout::placeOrder`:
- Đơn COD **giá trị cao** (`cod_manual_verify_amount`, mặc định 2.000.000đ) với khách Vàng
  → chuyển `manual_verifying` dù đã OTP.
- Gate `payment_allowed` tính từ tier (Đỏ → không COD).
- Đơn COD với tier không xanh / có OTP → bắt buộc ghi `TrustLog::order_placed`.

### 12.6 Sơ đồ cổng đặt hàng (`Checkout::gate`):
```
tier Đỏ           → payment_allowed = [bank, qr, wallet]        (cấm COD)
risk ≥ 2 tín hiệu → order_status = manual_verifying             (soát thủ công)
tier Vàng         → otp_required = true (trừ VIP/Diamond); status = pending
tier Xanh         → COD thoải mái, không OTP
```

---

## 13. Giao diện & chuẩn trình bày

### Frontend (khách)
- Bootstrap 5 + jQuery; layout: `header` (menu, tìm kiếm, cart, account) + content + `footer` + modal giỏ hàng/đăng nhập.
- Màu sắc: tuỳ chỉnh token CSS đầu file `assets/css/theme.css` (xem `web/includes/header.php` để tìm import).
- Tiền tệ: `format_money()` → `15.900.000 đ` (`number_format(x, 0, ',', '.')` + hậu tố ` đ`).
- Ảnh sản phẩm: cover + bộ ảnh (product_images); ảnh quảng cáo banner.
- SEO: route/slug **không dấu** (vd `san-pham/bang-go-soi`), `<title>/description/meta`, sitemap tuỳ chọn.

### Admin
- Route tiếng Việt hoá, dashboard thống kê theo trạng thái đơn (ajax), tìm kiếm nhanh (phím tắt Ctrl+K + live-search ở topbar), chart doanh thu.
- Phân quyền RBAC: `admins.role` (enum, ghi session) + `admins.role_id` → `roles` /
  `role_permissions` / `permissions` (superadmin bypass mọi quyền; quyền nhạy cảm
  `is_sensitive` chỉ chủ hệ thống); trang quản trị `phan-quyen`; audit log `nhat-ky`.
- Form chuẩn: validate server + gợi ý client; thao tác xoá/xuất bản xác nhận trước.
- Export: báo cáo `doanh thu/bán hàng` xuất ra `exports/`.
- Cảnh báo khuyến mại >50% hiển thị trong luồng duyệt đơn.

---

## 14. Checklist khởi tạo dự án mới

1. Copy cấu trúc thư mục + `.htaccess` (sửa phần tiền tố rewrite + tên project trong BASE_URL auto-detect).
2. Tạo schema: nhân bản `database/schema.sql` → đổi tên project tiêu đề, thêm bảng `[THEO NGÀNH]` theo ngành mới.
3. Đổi tiền tố mã đơn `Order::generateCode()` (vd quần áo `CL`, giày `SH`, điện tử `EL`)
   và đổi namespace `WoodCon` → tên dự án mới (vd `ClothesShop`) ở toàn bộ file (config,
   includes, controllers, models, `index.php`).
4. Bật/tắt module theo nhánh:
   - Nhánh A: giữ voucher, shipping, points; bỏ warranty.
   - Nhánh B (F&B): trạng thái gọn, không warranty, không COD-OTP. (xem §12 — bỏ nếu không cần COD)
   - Nhánh C: giữ nguyên như WoodCon (có warranty).
   - Nhánh D: thêm `appointments` + bỏ kho/ship.
5. Thay sản phẩm nội thất → sản phẩm `[THEO NGÀNH]`:
   `categories/brands/products/product_images/product_variants` (variants tuỳ ngành: size/colour/…).
6. Cấu hình: `settings` (freeship threshold, COD threshold, point_value, tên shop, SĐT, Zalo…), OAuth Google, Viettel Post/Goong keys.
7. Bí mật: copy `.env.example` → `.env`, đặt `DB_NAME` + secret thật; bản THẬT bị
   `.gitignore` — chỉ commit bản MẪU (xem §5.8).
8. Giao diện: đổi logo + màu token trong `assets/css/theme.css`; kiểm tra `format_money` hiển thị đúng.
9. Bảo mật: tài liệu nội bộ `*.md` đã bị `.htaccess` chặn sẵn (xem §5); đổi email/secret;
   key AI nếu dùng đặt trong **setting** `ai_api_key` (bảng `settings`, trang `cai-dat` → AI) —
   `config/ai-provider.php` là file sạch được commit, không cần gitignore.
10. Backup: copy bộ `backup/` (scripts + README sang dự án mới), sửa tên Task Scheduler + tiền tố file log theo project, tạo `local.config.ps1` (offsite), chạy `setup-scheduled-task.ps1` — xem §16.
11. Test: đặt đơn COD (OTP + reload), trừ/hoàn kho, điểm, voucher, warranty, phân quyền admin.
12. Backup: chạy thử `backup-db.ps1` + `backup-uploads.ps1` bằng tay, kiểm tra `backup/logs/`
    không có dòng ERROR; chạy `restore-db.ps1` vào DB tạm để xác nhận backup dùng được; sau
    `setup-scheduled-task.ps1` mở Task Scheduler kiểm tra **Last Run Result = 0x0** và thời điểm
    chạy (23:00 / 23:15).

### 14.1 Khởi tạo Git cho dự án mới
- `git init` repo **MỚI** — **không copy `.git/`** từ kho mẫu (lịch sử cũ có thể chứa secret /
  dữ liệu thật, xem §5.8).
- Sao chép đúng: cấu trúc thư mục, `.htaccess`, `.env.example`, `database/schema.sql`
  (đã ẩn danh, xem §15).
- **KHÔNG copy vào repo mới:** `.env`, `uploads/`, `storage/`, `backup/db/`,
  `backup/uploads/`, `backup/logs/`, `backup/scripts/local.config.ps1` — những thứ này
  được tạo/cấu hình riêng trên máy mới (`config.php`/`db.php` là code thuần, clone đã có sẵn).
- Cài lại pre-commit hook sau `git init` (hook nằm ngoài git — không đi kèm khi clone/copy).
- Commit đầu tiên chỉ sau khi cấu hình base xong và đã tạo `.gitignore` chặn secret (bảng kiểm
  `git status` không xuất hiện file nhạy cảm).

---

## 15. Phụ lục — Toàn bộ nội dung đã được tổng hợp vào file này

> File này là **bản tổng hợp duy nhất** thay thế toàn bộ tài liệu gốc trong `docs/`
> (PLAN_WOODCON, PROMPT_MASTER/ADMIN_UI/LOGIC, prompt dung chung, logic COD, quy tắc
> tính giá, tình hình dự án, token màu, logic trang quyền lợi, redesign cũ) — các file
> đó đã được gộp/xoá để repo gọn gàng.

Nguồn tham chiếu **code mẫu** khi triển khai dự án mới:

| Khu vực | File mẫu |
|---|---|
| Front controller | `index.php` |
| Cấu hình & kết nối | `config/config.php`, `config/db.php`, `config/EnvLoader.php` (+ bản mẫu secret `.env.example`) |
| Model lõi | `models/Base.php` |
| Nghiệp vụ đơn/giá/vận chuyển | `models/Order.php`, `Checkout.php`, `Shipping.php`, `Voucher.php`, `TaxRate.php` |
| Chống bùng COD (tuỳ chọn) | `models/TrustEngine.php`, `models/TrustLog.php` |
| Thành viên | `models/User.php` |
| Controller khách/admin | `controllers/AccountController.php`, `controllers/AdminController.php` |
| Bảo mật | `.htaccess` (44 dòng — xem §5) |
| Quy tắc ignore | `.gitignore` (secret thật, backup dữ liệu, uploads, logs — xem §5.8, §16) |
| Sao lưu & khôi phục | `backup/README.md`, `backup/scripts/*.ps1` (xem §16) |
| Schema chuẩn | `database/schema.sql` (45 bảng, schema + data, tái tạo = import 1 file) |

> **Quy ước tên DB:** file SQL trong repo **luôn tên `schema.sql`** (chuẩn chung, không đổi
> theo dự án/ngành). Tên DATABASE thật trong phpMyAdmin thì **đặt theo dự án** — ví dụ dự án
> gỗ dùng `woodcon_shop`, dự án quần áo dùng `clothes_shop`, ... PHP kết nối theo `DB_NAME` trong
> file `.env`; chỉ cần trỏ đúng tên DB thật đã tạo là chạy, tên file không liên
> quan tới tên DB. Quy trình tạo mới: phpMyAdmin → New database (đặt tên theo dự án)
> → chọn DB đó → Import `database/schema.sql` → sửa `DB_NAME` trong `.env` cho khớp.
>
> **`schema.sql` luôn là bản ẩn danh** (data demo, SĐT/mail giả, không có secret/PII của
> người thật). **Không bao giờ export DB thật để commit** — export thật chứa dữ liệu + giá trị
> nhạy cảm; nếu cần backup thì giữ ngoài git (xem §16).

Luồng đặt đơn tham khảo: `Checkout` (`buildTotals` → `applyVouchers` → `gate` → `placeOrder`).

---

## 16. Sao lưu & khôi phục (backup) — chuẩn 3 lớp

> Áp dụng cho **mọi dự án** bắt nguồn từ bộ khung này. Bộ script mẫu sống trong `backup/`
> của repo mẫu (WoodCon), cách dùng chi tiết xem `backup/README.md`.
> Mục này là **nguyên tắc chung** để tái lập backup trên bất kỳ dự án mới nào.

### 16.1 Ba lớp bảo vệ

| Lớp | Nội dung | Công cụ |
|---|---|---|
| 1. Code | Toàn bộ source (kể cả script backup) | Git — commit thường xuyên |
| 2. Database | `mysqldump` hằng ngày → nén `.zip` | `backup/scripts/backup-db.ps1` |
| 3. File tĩnh | `uploads/` + `storage/` (ảnh, chữ ký…) | `backup/scripts/backup-uploads.ps1` (robocopy mirror) |

> Lớp 1 = git (nhưng xem §5.8: lịch sử cũ có thể chứa secret). Lớp 2, 3 ở `backup/`
> (gitignore). **Offsite là bản sao chéo** của Lớp 2 + 3 + bộ cấu hình, đặt trên máy/ổ
> đám mây khác để mất máy chính vẫn khôi phục được (chi tiết §16.3).

### 16.2 Cấu trúc `backup/`

```
backup/
├── README.md               # Cách dùng bộ sao lưu
├── scripts/                # Script PowerShell (được commit lên git)
│   ├── common.ps1               # Hàm dùng chung (dot-source)
│   ├── backup-db.ps1            # Lớp 2: dump DB hàng ngày
│   ├── backup-uploads.ps1       # Lớp 3: mirror file tĩnh + snapshot zip
│   ├── rotate-backups.ps1       # Dọn theo retention (daily 7 / weekly 4 / monthly 12)
│   ├── restore-db.ps1           # Khôi phục DB (an toàn, ghi đè khi -Force)
│   ├── setup-scheduled-task.ps1 # Tạo Task Scheduler chạy hằng ngày
│   ├── local.config.sample.ps1  # BẢN MẪU cấu hình máy (được commit)
│   └── local.config.ps1         # Tạo từ mẫu — bị .gitignore, KHÔNG commit
├── db/                      # Dữ liệu backup — gitignore
│   ├── daily/               # <ten_project>_yyyyMMdd_HHmmss.zip
│   ├── weekly/              # copy Chủ Nhật hằng tuần
│   └── monthly/             # copy ngày 1 hằng tháng
├── uploads/                 # Mirror file tĩnh — gitignore
│   ├── snapshots/           # zip nén định kỳ
│   ├── uploads/             # mirror thư mục upload
│   └── storage/             # mirror thư mục storage
└── logs/                    # *.log chạy backup — gitignore
```

### 16.3 Nguyên tắc bảo mật khi sao lưu

- **Script không hardcode mật khẩu.** Credential DB lấy theo thứ tự ưu tiên:
  biến môi trường `WOODSHOP_DB_HOST/USER/PASS/NAME` → đọc **duy nhất** file `.env` ở gốc
  dự án (nguồn cấp giá trị cho `define('DB_*')` trong `config/config.php`, xem
  `common.ps1` → `Read-DotEnvFile`/`Get-DbConfig`). KHÔNG đọc `config/db.php` — lớp
  `Database` chỉ đọc hằng số từ config, không phải nguồn credential.
- Commit lên git **chỉ**: script + README + bản mẫu. **Không commit**: file dump, bản
  mirror, log, `local.config.ps1` — `.gitignore` đã chặn sẵn (xem §5).
- **Offsite (tùy chọn):** script backup copy bản zip + mirror **+ secret `.env`** sang
  OneDrive / Google Drive / ổ rời; đặt
  đường dẫn trong biến môi trường `WOODSHOP_OFFSITE_ROOT` hoặc `local.config.ps1`.
  Thứ tự ưu tiên: `-OffsiteRoot` (tham số) → biến môi trường → `local.config.ps1` → tắt.
  ⚠️ Bản cấu hình offsite chứa secret thật → chỉ đổ vào nơi bạn kiểm soát (ổ rời / OneDrive
  cá nhân); nếu phải đưa lên đám mây dùng chung, mã hoá trước.

### 16.4 Khôi phục (restore)

- `restore-db.ps1` mặc định **không đè** DB đang chạy: khôi phục vào DB tạm để kiểm tra,
  có cờ `-Force` mới ghi đè DB thật.
- Lớp 3: đồng bộ ngược từ mirror `backup/uploads/` về đúng `uploads/` + `storage/`
  (làm thủ công — chi tiết và giới hạn xem §16.7).

### 16.5 Tự động hoá & vận hành

- `setup-scheduled-task.ps1` (chạy **một lần**, cần quyền Quản trị viên Windows) tạo
  2 task chạy mỗi ngày lúc **23:00** (`WoodShop-Backup-DB`) và **23:15**
  (`WoodShop-Backup-Uploads`), bật `StartWhenAvailable`, cho phép khi dùng pin,
  `ExecutionTimeLimit` 1 giờ, không chạy trùng lệnh, tự nghỉ 3 lần.
  ⚠️ Task đăng ký kiểu **Interactive — chỉ chạy khi user đã đăng nhập Windows**. Muốn
  "chạy bất kể máy có đăng nhập" phải đổi `-LogonType` của `New-ScheduledTaskPrincipal`
  sang kiểu có lưu mật khẩu/S4U.
- Backup tự khởi động MySQL/MariaDB nếu đang tắt (dịch vụ Windows → XAMPP `mysqld`) và chờ tối đa 30s.
- Retention: `rotate-backups.ps1` tự xoá bản quá hạn → chỉ giữ daily 7 / weekly 4 / monthly 12
  (chỉ áp cho `backup/db/`; snapshot `backup/uploads/snapshots/` chưa có vòng dọn — xem §16.7).
- Theo dõi: log ở `backup/logs/*.log` ghi mức `START/INFO/OK/WARN/END/ERROR` (backup) và
  `ROTATE` (rotate); kiểm tra định kỳ không có dòng ERROR; **mỗi tháng thử restore 1 lần**
  vào DB tạm để chắc backup dùng được.

### 16.6 Ghi chú kỹ thuật cho script PowerShell

- Yêu cầu PowerShell **5.1+**; lưu file **UTF-8 có BOM** (không BOM thì Windows PowerShell
  5.1 đọc sai ký tự tiếng Việt).
- Dùng `$ErrorActionPreference = 'Stop'`; lệnh native (`mysqldump`, `mysql`, robocopy)
  phải kiểm tra `$LASTEXITCODE`; hàm thử phản hồi DB (`Test-MySqlAlive`) tạm đổi EAP sang
  `Continue` + bọc try/catch — xem `common.ps1`.
- Dump DB dùng `--single-transaction --routines --triggers --default-character-set=utf8mb4`
  và `--result-file=` (không redirect stdout để tránh lỗi encoding); nén bằng `Compress-Archive`.
- Tên file backup dạng `<ten_project>_yyyyMMdd_HHmmss.zip` để tránh trùng lặp khi chạy nhiều lần cùng ngày.

### 16.7 Hạn chế đã biết (nắm trước khi bàn giao)
- **Task Scheduler đăng ký thủ công 1 lần** (script cần quyền admin). Cài lại máy / clone
  máy mới phải chạy lại `setup-scheduled-task.ps1` — script không tự đăng ký khi deploy.
- **Offsite chỉ chạy khi script backup chạy** và có cấu hình `WOODSHOP_OFFSITE_ROOT` /
  `local.config.ps1`. Nếu offsite trỏ vào folder đồng bộ đám mây (OneDrive/Google Drive)
  thì giới hạn dung lượng và cơ chế retry là của dịch vụ đó.
- **Snapshot `backup/uploads/snapshots/` chưa có retention.** `rotate-backups.ps1` chỉ dọn
  `backup/db/*`; muốn giới hạn phải thêm bước xoá snapshot quá tuổi.
- **Chưa có script restore uploads** từ mirror về đúng vị trí — chỉ có `restore-db.ps1`.
  Khôi phục Lớp 3 hiện đang làm thủ công.
- `robocopy /MIR` phản ánh cả việc **xoá file ở nguồn** → mirror luôn = trạng thái hiện tại;
  mốc thời gian cũ dựa vào **snapshot zip hàng tuần** (đảm bảo luôn có ít nhất 1 snapshot).
- Script giả định MySQL/MariaDB chạy theo đường dẫn XAMPP mặc định (`mysqld`); dùng MySQL
  riêng phải sửa biến đường dẫn trong `common.ps1`.