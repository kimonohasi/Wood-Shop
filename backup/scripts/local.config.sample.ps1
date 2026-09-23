# ============================================================================
# local.config.sample.ps1 - BẢN MẪU cấu hình backup trên máy bạn
#
# Cách dùng:
#   1. Copy file này thành local.config.ps1 (cùng thư mục backup/scripts/).
#      File local.config.ps1 bị .gitignore nên KHÔNG được commit lên git.
#   2. Sửa $OffsiteRoot cho đúng đường dẫn thư mục backup offsite của bạn.
#
# Thứ tự ưu tiên khi chạy script backup:
#   tham số -OffsiteRoot  >  biến môi trường WOODSHOP_OFFSITE_ROOT  >  local.config.ps1
# ============================================================================

# Thư mục chứa bản backup offsite (nên là thư mục con của OneDrive / Google Drive).
# Để rỗng "" nếu không dùng backup offsite.
#$OffsiteRoot = "<user>\OneDrive\WoodShop-Backup"
$OffsiteRoot = ""