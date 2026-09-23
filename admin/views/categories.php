<?php
/** Quản lý danh mục admin - nút Thêm/Sửa mở Modal + accordion hiện sản phẩm theo danh mục */
declare(strict_types=1);
use WoodCon\Category;
$edit = $edit;
$tree = $tree ?? [];
$parents = $parents ?? [];
$productsByCat = $productsByCat ?? [];
$catStats = $catStats ?? ['total' => 0, 'child' => 0, 'active' => 0, 'inactive' => 0];
$__parents = array_values(array_filter($parents, fn($p) => (int)$p['id'] !== (int)($edit['id'] ?? 0)));
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Danh mục</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" data-mode="create"><?= icon('bi-plus-lg', 'me-1') ?>Thêm danh mục</button>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng danh mục</div>
                    <div class="stat-value mt-1"><?= (int)$catStats['total'] ?></div>
                </div>
                <?= icon('bi-diagram-3', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Danh mục con</div>
                    <div class="stat-value mt-1"><?= (int)$catStats['child'] ?></div>
                </div>
                <?= icon('bi-diagram-2', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hoạt động</div>
                    <div class="stat-value mt-1 text-success"><?= (int)$catStats['active'] ?></div>
                </div>
                <?= icon('bi-check-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Không hoạt động</div>
                    <div class="stat-value mt-1"><?= (int)$catStats['inactive'] ?></div>
                </div>
                <?= icon('bi-eye-slash', 'stat-icon') ?>
            </div>
        </div>
    </div>
</div>

<?php
// ===== Render cây danh mục đệ quy 3 tầng: Cha -> Con -> Sản phẩm =====
// Mỗi node (cha & con) là một accordion expand/collapse độc lập.
// Sản phẩm chỉ lấy theo category_id của riêng node đó (không gộp).
$__nodeCounter = 0;
$__renderCatNode = null;
$__renderCatNode = function (array $cat, int $depth = 0) use (&$__renderCatNode, &$__nodeCounter, $productsByCat): void {
    $id = (int)$cat['id'];
    $children = $cat['children'] ?? [];
    $prods = $productsByCat[$id] ?? [];
    $hasKids = !empty($children) || !empty($prods);
    $accId = 'cat-node-' . (++$__nodeCounter);
    $open = $depth === 0 && $__nodeCounter === 1; // chỉ node đầu (cấp cha) mở sẵn
    $hasExpandable = $hasKids;
    ?>
    <div class="ctree-row" data-depth="<?= $depth ?>" data-name="<?= e(mb_strtolower($cat['name'])) ?>" data-status="<?= (int)$cat['status'] ?>">
        <div class="ctree-cell ctree-node">
            <?php if ($hasExpandable): ?>
                <button type="button" class="ctree-toggle" data-target="#<?= $accId ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>">
                    <?= icon($open ? 'bi-dash-square-fill' : 'bi-plus-square-fill') ?>
                </button>
            <?php else: ?>
                <span class="ctree-toggle ctree-toggle-empty"><?= icon('bi-dot') ?></span>
            <?php endif; ?>
            <span class="ctree-name <?= $depth === 0 ? 'ctree-root' : '' ?>"><?= e($cat['name']) ?></span>
            <?php if ((int)$cat['status'] === 0): ?><span class="badge text-bg-secondary">Ẩn</span><?php endif; ?>
            <?php if ($depth >= 1): ?><span class="ctree-count"><?= count($prods) ?> sản phẩm</span><?php endif; ?>
            <span class="ctree-actions">
                <a href="<?= BASE_URL ?>/quan-tri/danh-muc/sua/<?= $id ?>" class="btn btn-sm btn-light btn-edit-cat" data-bs-toggle="modal" data-bs-target="#categoryModal" data-mode="edit"
                    data-id="<?= $id ?>" data-name="<?= e($cat['name']) ?>" data-slug="<?= e($cat['slug']) ?>" data-parent="<?= (int)($cat['parent_id'] ?? 0) ?>"
                    data-sort="<?= (int)($cat['sort_order'] ?? 0) ?>" data-image="<?= e($cat['image'] ?? '') ?>" data-status="<?= (int)$cat['status'] ?>"
                    data-install="<?= (int)($cat['install_fee'] ?? 0) ?>" data-ship1="<?= ($cat['ship_supports_type1'] ?? null) === null || $cat['ship_supports_type1'] === '' ? '' : (int)$cat['ship_supports_type1'] ?>" data-ship2="<?= ($cat['ship_supports_type2'] ?? null) === null || $cat['ship_supports_type2'] === '' ? '' : (int)$cat['ship_supports_type2'] ?>"><?= icon('bi-pencil') ?></a>
                <a href="<?= BASE_URL ?>/quan-tri/danh-muc/xoa/<?= $id ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa danh mục này?"><?= icon('bi-trash') ?></a>
            </span>
        </div>

        <?php if ($hasExpandable): ?>
            <div class="ctree-children <?= $open ? '' : 'd-none' ?>" id="<?= $accId ?>">
                <?php if (!empty($children)): ?>
                    <?php foreach ($children as $__child): ?>
                        <?php $__renderCatNode($__child, $depth + 1); ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php /* Sản phẩm chỉ hiển thị ở danh mục con (depth>=1), không hiện ở cha */ ?>
                <?php if ($depth >= 1): ?>
                    <?php if (empty($prods)): ?>
                        <div class="ctree-empty">Chưa có sản phẩm</div>
                    <?php else: ?>
                        <?php foreach ($prods as $__p): ?>
                            <?php $__price = (float)($__p['sale_price'] > 0 ? $__p['sale_price'] : $__p['price']); ?>
                            <div class="ctree-row ctree-prod" data-depth="<?= $depth + 1 ?>">
                                <span class="ctree-toggle ctree-toggle-empty"><?= icon('bi-dot') ?></span>
                                <span class="ctree-prod-name"><?= e($__p['name']) ?></span>
                                <span class="ctree-prod-price"><?= format_money((int)$__price) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
};
?>

<div class="admin-card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" id="catSearch" class="form-control form-control-sm" placeholder="Tìm danh mục..." autocomplete="off">
            </div>
            <div class="col-md-3">
                <select id="catStatus" class="form-select form-select-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Hoạt động</option>
                    <option value="0">Ẩn</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="button" id="catApply" class="btn btn-primary btn-sm flex-grow-1">Lọc</button>
                <button type="button" id="catClear" class="btn btn-outline-primary btn-sm" style="display:none"><?= icon('bi-x-lg', 'me-1') ?>Xóa lọc</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-card admin-card-cats mt-3">
    <div class="empty-state d-none" id="catFilterEmpty"><?= icon('bi-search') ?>Không tìm thấy danh mục phù hợp</div>
    <?php if (empty($tree)): ?>
        <div class="empty-state p-4"><?= icon('bi-diagram-3') ?>Chưa có danh mục</div>
    <?php else: foreach ($tree as $__cat): echo $__renderCatNode($__cat, 0); endforeach; endif; ?>
</div>

<!-- ============ MODAL THÊM / SỬA DANH MỤC ============ -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/quan-tri/danh-muc/luu" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="cf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle"><?= icon('bi-diagram-3', 'me-1') ?>Thêm danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tên danh mục *</label>
                        <input type="text" class="form-control" name="name" id="cf_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Loại danh mục</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cat_type" id="cf_type_parent" value="parent">
                                <label class="form-check-label small" for="cf_type_parent">Danh mục cha</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cat_type" id="cf_type_child" value="child">
                                <label class="form-check-label small" for="cf_type_child">Danh mục con</label>
                            </div>
                        </div>
                        <div class="form-text small" id="cf_type_help"></div>
                    </div>
                    <div class="mb-3" id="cf_parent_wrap">
                        <label class="form-label small text-muted">Danh mục cha *</label>
                        <select class="form-select" name="parent_id" id="cf_parent_id" disabled>
                            <option value="">-- Chọn danh mục cha --</option>
                            <?php foreach ($__parents as $__cp): ?>
                                <option value="<?= (int)$__cp['id'] ?>"><?= e($__cp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" id="cf_sort_order" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Phí lắp đặt (đ)</label>
                        <input type="number" step="1000" min="0" class="form-control" name="install_fee" id="cf_install_fee" value="0">
                        <div class="form-text small">Phí lắp đặt mặc định cho sản phẩm thuộc danh mục (chỉ khi khách chọn lắp đặt).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Loại 1 - Giao &amp; lắp đặt tại nhà</label>
                        <div class="btn-group d-flex w-100" role="group" aria-label="Hỗ trợ Loại 1">
                            <input type="radio" class="btn-check" name="ship_supports_type1" id="cst1_auto" value="" checked>
                            <label class="btn btn-outline-secondary btn-sm" for="cst1_auto">Có (mặc định)</label>
                            <input type="radio" class="btn-check" name="ship_supports_type1" id="cst1_no" value="0">
                            <label class="btn btn-outline-secondary btn-sm" for="cst1_no">Không</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Loại 2 - Gửi qua đơn vị vận chuyển</label>
                        <div class="btn-group d-flex w-100" role="group" aria-label="Hỗ trợ Loại 2">
                            <input type="radio" class="btn-check" name="ship_supports_type2" id="cst2_auto" value="" checked>
                            <label class="btn btn-outline-secondary btn-sm" for="cst2_auto">Có (mặc định)</label>
                            <input type="radio" class="btn-check" name="ship_supports_type2" id="cst2_no" value="0">
                            <label class="btn btn-outline-secondary btn-sm" for="cst2_no">Không</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Ảnh đại diện (URL)</label>
                        <input type="text" class="form-control" name="image" id="cf_image" placeholder="https://...">
                        <div class="form-text small">Có thể dán URL hoặc upload ảnh dưới đây.</div>
                        <input type="file" class="form-control mt-2" name="image_upload" accept="image/*">
                    </div>
                    <div class="form-check form-switch toggle-row mb-1">
                        <input class="form-check-input" type="checkbox" name="status" id="cf_status" checked>
                        <label class="form-check-label small" for="cf_status">Hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu danh mục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.admin-card-cats{ padding:0; }
.ctree-row{ padding:.45rem .7rem; }
.ctree-row+.ctree-row{ border-top:1px solid var(--wc-border,#f0f0f0); }
.ctree-node{ display:flex;align-items:center;gap:.55rem; }
.ctree-root{ font-weight:700; }
.ctree-name{ font-weight:600;font-size:.92rem; }
.ctree-toggle{ display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;flex:0 0 1.25rem;
  border:0;background:transparent;color:var(--wc-primary,#8b5a2b);font-size:1.05rem;line-height:1;cursor:pointer;border-radius:.25rem; }
.ctree-toggle:hover{ background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 12%,transparent); }
.ctree-toggle-empty{ cursor:default;color:var(--wc-muted); }
.ctree-toggle-empty:hover{ background:transparent; }
.ctree-count{ margin-left:auto;font-size:.73rem;color:var(--wc-muted);background:color-mix(in srgb,currentColor 10%,transparent);border-radius:999px;padding:.12rem .5rem; }
.ctree-actions{ display:flex;gap:.3rem; }
.ctree-children{ border-left:2px solid var(--wc-border,#ececec);margin:.2rem 0 .2rem .6rem;padding-left:.9rem; }
.ctree-empty{ font-size:.78rem;color:var(--wc-muted);padding:.25rem 0 .4rem .3rem;font-style:italic; }
.ctree-prod{ display:flex;align-items:center;gap:.55rem; }
.ctree-prod-name{ font-size:.86rem; }
.ctree-prod-price{ margin-left:auto;font-size:.84rem;font-weight:600;color:#d63384; }
/* Indentation theo tầng (đệ quy thấm dần theo chiều sâu) */
.ctree-children .ctree-children{ margin-left:.7rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('categoryModal');
  const title = document.getElementById('categoryModalTitle');
  const form = modal.querySelector('form');
  const setVal = (id, v) => { const el = document.getElementById('cf_' + id); if (el) el.value = v ?? ''; };
  const setStatus = v => { const c = document.getElementById('cf_status'); if (c) c.checked = !!Number(v); };

  const typeParent = document.getElementById('cf_type_parent');
  const typeChild = document.getElementById('cf_type_child');
  const parentSelect = document.getElementById('cf_parent_id');
  const typeHelp = document.getElementById('cf_type_help');

  function applyType(type) {
    const useParent = type === 'child';
    parentSelect.disabled = !useParent;
    typeHelp.textContent = useParent
      ? 'Danh mục con sẽ nằm dưới danh mục cha. Sản phẩm được gắn vào danh mục con.'
      : 'Danh mục cha là nhóm cấp 1, chứa các danh mục con bên trong.';
  }
  typeParent.addEventListener('change', () => { if (typeParent.checked) applyType('parent'); });
  typeChild.addEventListener('change', () => { if (typeChild.checked) applyType('child'); });

  modal.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (btn && btn.dataset.mode === 'edit') return;
    title.innerHTML = '<?= icon('bi-diagram-3', 'me-1') ?>Thêm danh mục';
    form.reset();
    setVal('id', '0');
    parentSelect.value = '';
    setVal('sort_order', '0');
    setStatus(1);
    setVal('install_fee', '0');
    document.getElementById('cst1_auto').checked = true;
    document.getElementById('cst1_no').checked = false;
    document.getElementById('cst2_auto').checked = true;
    document.getElementById('cst2_no').checked = false;
    // Mặc định "Danh mục con" — tạo con thường gặp hơn
    typeChild.checked = true;
    typeParent.checked = false;
    applyType('child');
  });

  document.querySelectorAll('.btn-edit-cat').forEach(btn => {
    btn.addEventListener('click', function () {
      title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa danh mục ' + this.dataset.name;
      setVal('id', this.dataset.id);
      setVal('name', this.dataset.name);
      setVal('sort_order', this.dataset.sort);
      setVal('image', this.dataset.image);
      setVal('install_fee', this.dataset.install);
      setStatus(this.dataset.status);
      const s1 = this.dataset.ship1;
      document.getElementById('cst1_auto').checked = (s1 === '' || s1 === '1');
      document.getElementById('cst1_no').checked = (s1 === '0');
      const s2 = this.dataset.ship2;
      document.getElementById('cst2_auto').checked = (s2 === '' || s2 === '1');
      document.getElementById('cst2_no').checked = (s2 === '0');
      const par = parseInt(this.dataset.parent || '0', 10) || 0;
      if (par > 0) {
        typeChild.checked = true; typeParent.checked = false;
        parentSelect.value = String(par);
      } else {
        typeParent.checked = true; typeChild.checked = false;
        parentSelect.value = '';
      }
      applyType(par > 0 ? 'child' : 'parent');
    });
  });

  // Tree: bấm icon +/- để thu gọn / xổ (cha & con đều độc lập)
  document.querySelectorAll('.ctree-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
      const body = document.querySelector(this.dataset.target);
      if (!body) return;
      const icon = this.querySelector('use');
      const open = body.classList.toggle('d-none') === false;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (icon) icon.setAttribute('href', WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + (open ? 'bi-dash-square-fill' : 'bi-plus-square-fill'));
    });
  });

  // Lọc nhanh cây danh mục (tìm tên + lọc trạng thái) không cần reload
  const catSearch = document.getElementById('catSearch');
  const catStatus = document.getElementById('catStatus');
  const catApply = document.getElementById('catApply');
  const catClear = document.getElementById('catClear');
  const catEmpty = document.getElementById('catFilterEmpty');
  const catRows = Array.from(document.querySelectorAll('.ctree-row.ctree-node'));

  function applyCatFilter() {
    const q = (catSearch.value || '').trim().toLowerCase();
    const st = catStatus.value;
    if (catClear) catClear.style.display = (q || st) ? 'inline-flex' : 'none';
    let anyVisible = false;
    for (let i = catRows.length - 1; i >= 0; i--) {
      const row = catRows[i];
      const self = (!q || (row.dataset.name || '').includes(q)) && (!st || row.dataset.status === st);
      let kid = false;
      const body = row.querySelector(':scope > .ctree-children');
      if (body) {
        body.querySelectorAll('.ctree-row.ctree-node').forEach(k => { if (k.dataset.wcf === '1') kid = true; });
      }
      const show = self || kid;
      row.dataset.wcf = show ? '1' : '0';
      row.style.display = show ? '' : 'none';
      if (show && !self && body) {
        body.classList.remove('d-none');
        const tb = row.querySelector(':scope > .ctree-node .ctree-toggle');
        if (tb) {
          tb.setAttribute('aria-expanded', 'true');
          const ic = tb.querySelector('use');
          if (ic) ic.setAttribute('href', WOODCON_BASE_URL + '/assets/icons/sprite.svg#bi-dash-square-fill');
        }
      }
      if (show) anyVisible = true;
    }
    if (catEmpty) catEmpty.style.display = anyVisible ? 'none' : 'block';
  }

  if (catSearch) catSearch.addEventListener('input', applyCatFilter);
  if (catStatus) catStatus.addEventListener('change', applyCatFilter);
  if (catApply) catApply.addEventListener('click', applyCatFilter);
  if (catClear) catClear.addEventListener('click', function () {
    catSearch.value = '';
    catStatus.value = '';
    applyCatFilter();
    catSearch.focus();
  });
});
</script>
