# AGENTS — chỉ dẫn cho agent làm việc trong dự án

## Việc còn treo: nhánh `cleanup/prune-unused`

Nhánh `cleanup/prune-unused` hiện chứa các commit công việc dọn dẹp + tài liệu
thiết kế chung, **chưa merge vào `main`** (chủ ý).

Thời điểm được phép xử lý (merge/resolve nhánh này) — chỉ khi hội tụ **một trong**:

1. Dự án (WoodShop / WoodCon) đã **hoàn thiện xong** hoặc **gần xong**; hoặc
2. **User yêu cầu** merge nhánh cleanup.

Khi đó agent cần:
- Đọc các commit của nhánh `cleanup/prune-unused` (trạng thái diff so với `main`).
- Merge về `main` (hoặc rebase nếu `main` đã tiến thêm), giải quyết xung đột nếu có.
- Báo user kết quả; commit message ghi rõ nội dung.

**Trước thời điểm trên: KHÔNG merge nhánh này**, chỉ tiếp tục phát triển bình thường
trên nhánh làm việc hiện tại.

## Lưu ý chung
- `.htaccess` đã chặn truy cập web tới `AGENTS.md` và các tài liệu nội bộ khác — file này an toàn.
- Kiến trúc chuẩn + bản tổng hợp nghiệp vụ nằm trong `docs/THIET_KE_CHUNG.md`
  (blueprint dùng lại được cho mọi ngành).
- **File database trong repo LUÔN tên `schema.sql`** (chuẩn chung, không đổi theo từng
  dự án/ngành). Tên DATABASE thật thì tùy dự án đặt (vd `schema`, `woodcon_shop`, ...) —
  chỉ cần `DB_NAME` trong file `.env` khớp với DB thật đã tạo trong phpMyAdmin
  là kết nối chạy, tên file sql không liên quan tới tên DB.
- Mọi thay đổi code: chạy check trước khi commit (nếu có lệnh check trong repo), giữ
  đúng chuẩn PHP Native + Bootstrap 5 + jQuery/AJAX, UI tiếng Việt.