<?php
/** Form tạo/sửa sản phẩm admin - bố cục accordion + biến thể chỉnh sửa được */
declare(strict_types=1);
$p = $product;
$__vcount = count($variants);
?>
<div class="d-flex align-items-center justify-content-between mb-3 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0"><?= $p ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/quan-tri/san-pham" class="btn btn-sm btn-light"><?= icon('bi-arrow-left', 'me-1') ?>Quay lại</a>
</div>

<form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/quan-tri/san-pham/luu" id="productForm" novalidate>
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= $p ? (int)$p['id'] : 0 ?>">

    <div class="admin-accordion accordion" id="productAccordion">
        <!-- 1. Thông tin cơ bản -->
        <div class="accordion-item acc-sec" data-sec="acc-basic">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-basic" aria-expanded="false" aria-controls="acc-basic">
                    <?= icon('bi-info-circle', 'me-2') ?>Thông tin cơ bản
                    <span class="acc-check" data-sec-for="acc-basic" data-req="name,sku,category_id" title="Điền đủ thông tin bắt buộc"></span>
                </button>
            </h2>
            <div id="acc-basic" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small text-muted">Tên sản phẩm *</label>
                            <input type="text" class="form-control" name="name" required data-req value="<?= e($p['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">SKU *</label>
                            <input type="text" class="form-control" name="sku" required data-req value="<?= e($p['sku'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Danh mục *</label>
                            <select class="form-select" name="category_id" required data-req>
                                <option value="">-- Chọn danh mục con --</option>
                                <?php $__sel = (int)($p['category_id'] ?? 0);
                                foreach ($categories as $__c):
                                    $__hasChildren = !empty($__c['children']);
                                    if ($__hasChildren): ?>
                                        <optgroup label="<?= e($__c['name']) ?>">
                                        <?php foreach ($__c['children'] as $__cc): ?>
                                            <option value="<?= (int)$__cc['id'] ?>" <?= $__sel === (int)$__cc['id'] ? 'selected' : '' ?>><?= e($__cc['name']) ?></option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                    <?php else: ?>
                                        <option value="<?= (int)$__c['id'] ?>" <?= $__sel === (int)$__c['id'] ? 'selected' : '' ?>><?= e($__c['name']) ?></option>
                                    <?php endif;
                                endforeach; ?>
                            </select>
                            <div class="form-text small">Sản phẩm phải thuộc danh mục con. Tạo danh mục con trước nếu chưa có.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Thương hiệu</label>
                            <select class="form-select" name="brand_id">
                                <option value="0">-- Không --</option>
                                <?php foreach ($brands as $__b): ?>
                                    <option value="<?= (int)$__b['id'] ?>" <?= ($p['brand_id'] ?? 0) == $__b['id'] ? 'selected' : '' ?>><?= e($__b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Chất liệu</label>
                            <input type="text" class="form-control" name="material" value="<?= e($p['material'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Mô tả ngắn</label>
                            <textarea class="form-control" name="summary" rows="2"><?= e($p['summary'] ?? '') ?></textarea>
                            <div class="form-text small">Dùng làm mô tả SEO (meta description) và hiển thị trong thẻ sản phẩm.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Mô tả sản phẩm -->
        <div class="accordion-item acc-sec" data-sec="acc-desc">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-desc" aria-expanded="false" aria-controls="acc-desc">
                    <?= icon('bi-text-paragraph', 'me-2') ?>Mô tả sản phẩm
                </button>
            </h2>
            <div id="acc-desc" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <label class="form-label small text-muted">Mô tả chi tiết</label>
                    <textarea class="form-control" name="description" rows="10"><?= e($p['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 3. Hình ảnh -->
        <div class="accordion-item acc-sec" data-sec="acc-images">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-images" aria-expanded="false" aria-controls="acc-images">
                    <?= icon('bi-images', 'me-2') ?>Hình ảnh (ảnh chính + ảnh phụ)
                </button>
            </h2>
            <div id="acc-images" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="card border">
                                <div class="card-head px-3 pt-3 small fw-semibold">Ảnh đại diện (ảnh chính)</div>
                                <div class="card-body">
                                    <?php if ($p && $p['cover_image']): ?>
                                        <img src="<?= e(image_url($p['cover_image'] ?? '')) ?>" class="img-fluid rounded mb-2" style="max-height:180px;object-fit:cover" alt="">
                                    <?php endif; ?>
                                    <input type="file" class="form-control form-control-sm" name="cover_image" accept="image/*">
                                    <div class="form-text small">1 ảnh duy nhất, hiển thị làm ảnh mặc định. Tải lên ảnh mới để thay thế.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card border">
                                <div class="card-head px-3 pt-3 small fw-semibold">Ảnh phụ (gallery)</div>
                                <div class="card-body">
                                    <?php if (!empty($images)): ?>
                                        <div class="row g-2 mb-3">
                                            <?php foreach ($images as $__img): ?>
                                                <div class="col-4">
                                                    <div class="position-relative border rounded overflow-hidden" style="aspect-ratio:1">
                                                        <img src="<?= e(image_url($__img['image'])) ?>" class="w-100 h-100" style="object-fit:cover" alt="">
                                                        <label class="position-absolute bottom-0 start-0 end-0 m-0 p-1 bg-dark bg-opacity-75 text-white small text-center" style="font-size:.72rem;cursor:pointer">
                                                            <input type="checkbox" name="delete_image[]" value="<?= (int)$__img['id'] ?>"> Xóa
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state py-3 mb-3"><?= icon('bi-images', 'style="font-size:2rem"') ?>Chưa có ảnh phụ</div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control form-control-sm" name="gallery_images[]" multiple accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Biến thể & Ảnh theo màu -->
        <div class="accordion-item acc-sec" data-sec="acc-variants">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-variants" aria-expanded="false" aria-controls="acc-variants">
                    <?= icon('bi-grid-3x2-gap', 'me-2') ?>Biến thể &amp; Ảnh theo màu
                    <span class="acc-check" data-sec-for="acc-variants" data-req="variant-ok" title="Từ 2 biến thể trở lên mỗi biến thể cần ảnh riêng"></span>
                </button>
            </h2>
            <div id="acc-variants" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="alert alert-info small py-2">
                        <?= icon('bi-lightbulb', 'me-1') ?>Mỗi biến thể (VD: màu sắc) có thể có <strong>ảnh riêng, giá chênh lệch và tồn kho riêng</strong>. Khi có từ <strong>2 biến thể trở lên</strong>, bắt buộc mỗi biến thể phải có ảnh.
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="variantTable">
                            <thead>
                                <tr class="small text-muted">
                                    <th style="width:15%">Thuộc tính</th>
                                    <th style="width:18%">Giá trị</th>
                                    <th style="width:16%">Ảnh riêng</th>
                                    <th style="width:12%">Giá chênh (đ)</th>
                                    <th style="width:10%">Tồn kho</th>
                                    <th style="width:12%">SKU</th>
                                    <th style="width:8%"></th>
                                </tr>
                            </thead>
                            <tbody id="variantRows">
                                <?php foreach ($variants as $__v): ?>
                                    <tr class="variant-row">
                                        <td>
                                            <input type="hidden" name="variant_id[]" value="<?= (int)$__v['id'] ?>">
                                            <input type="text" name="variant_name[]" class="form-control form-control-sm" value="<?= e($__v['name'] ?? 'Màu sắc') ?>" placeholder="Màu sắc">
                                        </td>
                                        <td><input type="text" name="variant_value[]" class="form-control form-control-sm" value="<?= e($__v['value'] ?? '') ?>" placeholder="Xanh rêu"></td>
                                        <td>
                                            <?php if (!empty($__v['image_url'])): ?>
                                                <img src="<?= e(image_url($__v['image_url'])) ?>" class="variant-thumb mb-1" alt="">
                                            <?php endif; ?>
                                            <div class="form-control-sm py-1 d-flex align-items-center gap-1">
                                                <?= icon('bi-image') ?>
                                                <label class="mb-0 text-primary small" style="cursor:pointer">Chọn file
                                                    <input type="file" name="variant_image[]" class="form-control form-control-sm d-none" accept="image/*" data-vfile>
                                                </label>
                                                <span class="small text-muted vfile-name"></span>
                                            </div>
                                        </td>
                                        <td><input type="number" name="variant_price_adjust[]" class="form-control form-control-sm" value="<?= e($__v['price_adjust'] ?? '0') ?>" step="1000" placeholder="0"></td>
                                        <td><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="<?= (int)($__v['stock'] ?? 0) ?>" min="0"></td>
                                        <td><input type="text" name="variant_sku[]" class="form-control form-control-sm" value="<?= e($__v['sku'] ?? '') ?>" placeholder="SKU"></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-variant title="Xóa biến thể"><?= icon('bi-trash') ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddVariant"><?= icon('bi-plus-lg', 'me-1') ?>Thêm biến thể</button>
                    <span class="ms-2 small text-muted" id="variantCountText"><?= $__vcount ?> biến thể</span>
                </div>
            </div>
        </div>

        <!-- 5. Giá & Tồn kho -->
        <div class="accordion-item acc-sec" data-sec="acc-price">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-price" aria-expanded="false" aria-controls="acc-price">
                    <?= icon('bi-tags', 'me-2') ?>Giá &amp; Tồn kho
                    <span class="acc-check" data-sec-for="acc-price" data-req="price"></span>
                </button>
            </h2>
            <div id="acc-price" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Giá niêm yết *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" required data-req value="<?= e($p['price'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Giá KM (để trống nếu không KM)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="sale_price" value="<?= e($p['sale_price'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Giá vốn (cost)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="cost" value="<?= e($p['cost'] ?? '0') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Số lượng kho</label>
                            <input type="number" min="0" class="form-control" name="quantity" value="<?= (int)($p['quantity'] ?? 0) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Thuế & Vận chuyển -->
        <div class="accordion-item acc-sec" data-sec="acc-ship">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-ship" aria-expanded="false" aria-controls="acc-ship">
                    <?= icon('bi-truck', 'me-2') ?>Thuế &amp; Vận chuyển
                </button>
            </h2>
            <div id="acc-ship" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Mức thuế (VAT)</label>
                            <select class="form-select" name="tax_rate_id">
                                <option value="0">-- Theo mức mặc định --</option>
                                <?php foreach ($taxRates ?? [] as $__t): ?>
                                    <option value="<?= (int)$__t['id'] ?>" <?= (int)($p['tax_rate_id'] ?? 0) == (int)$__t['id'] ? 'selected' : '' ?>>
                                        <?= e($__t['name']) ?> (<?= rtrim(rtrim((string)$__t['rate'], '0'), '.') ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Phí lắp đặt (đ)</label>
                            <input type="number" step="1000" min="0" class="form-control" name="install_fee" value="<?= e($p['install_fee'] ?? '0') ?>">
                            <div class="form-text small">Để 0 sẽ lấy theo danh mục. Chỉ áp dụng khi khách chọn lắp đặt (Loại 1).</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Loại 1 - Giao &amp; lắp đặt tại nhà</label>
                            <?php $__t1 = $p['ship_supports_type1'] ?? null; $__t1auto = $__t1 === null || $__t1 === ''; ?>
                            <div class="btn-group d-flex w-100" role="group" aria-label="Hỗ trợ Loại 1">
                                <input type="radio" class="btn-check" name="ship_supports_type1" id="st1_auto" value="" <?= $__t1auto ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st1_auto">Theo danh mục</label>
                                <input type="radio" class="btn-check" name="ship_supports_type1" id="st1_yes" value="1" <?= !$__t1auto && (string)$__t1 === '1' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st1_yes">Có</label>
                                <input type="radio" class="btn-check" name="ship_supports_type1" id="st1_no" value="0" <?= !$__t1auto && (string)$__t1 === '0' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st1_no">Không</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Loại 2 - Gửi qua đơn vị vận chuyển</label>
                            <?php $__t2 = $p['ship_supports_type2'] ?? null; $__t2auto = $__t2 === null || $__t2 === ''; ?>
                            <div class="btn-group d-flex w-100" role="group" aria-label="Hỗ trợ Loại 2">
                                <input type="radio" class="btn-check" name="ship_supports_type2" id="st2_auto" value="" <?= $__t2auto ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st2_auto">Theo danh mục</label>
                                <input type="radio" class="btn-check" name="ship_supports_type2" id="st2_yes" value="1" <?= !$__t2auto && (string)$__t2 === '1' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st2_yes">Có</label>
                                <input type="radio" class="btn-check" name="ship_supports_type2" id="st2_no" value="0" <?= !$__t2auto && (string)$__t2 === '0' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm" for="st2_no">Không</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Khối lượng (kg)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="weight_kg" value="<?= e($p['weight_kg'] ?? '1') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Bảo hành (tháng)</label>
                            <input type="number" min="0" class="form-control" name="warranty_months" value="<?= (int)($p['warranty_months'] ?? 12) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Kích thước (mô tả)</label>
                            <input type="text" class="form-control" name="dimension" value="<?= e($p['dimension'] ?? '') ?>">
                        </div>
                        <div class="col-md-2"><label class="form-label small text-muted">Dài (cm)</label><input type="number" step="0.01" class="form-control" name="dim_l" value="<?= e($p['dim_l'] ?? '') ?>"></div>
                        <div class="col-md-2"><label class="form-label small text-muted">Rộng (cm)</label><input type="number" step="0.01" class="form-control" name="dim_w" value="<?= e($p['dim_w'] ?? '') ?>"></div>
                        <div class="col-md-2"><label class="form-label small text-muted">Cao (cm)</label><input type="number" step="0.01" class="form-control" name="dim_h" value="<?= e($p['dim_h'] ?? '') ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. SEO & Trạng thái -->
        <div class="accordion-item acc-sec" data-sec="acc-seo">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc-seo" aria-expanded="false" aria-controls="acc-seo">
                    <?= icon('bi-sliders', 'me-2') ?>SEO &amp; Trạng thái hiển thị
                    <span class="acc-check" data-sec-for="acc-seo" data-req="slug" title="Nhập slug hợp lệ"></span>
                </button>
            </h2>
            <div id="acc-seo" class="accordion-collapse collapse" data-bs-parent="#productAccordion">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">URL thân thiện (slug)</label>
                        <input type="text" class="form-control" name="slug" id="slugInput" value="<?= e($p['slug'] ?? '') ?>" placeholder="Tu dong tao tu ten san pham khi de trong">
                        <div class="form-text small">Để trống sẽ tự sinh từ tên sản phẩm. Nếu trùng, tự động thêm -2, -3... Chỉ gồm chữ thường, số và dấu gạch ngang.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small text-muted">Meta title (tiêu đề SEO)</label>
                            <input type="text" class="form-control" name="meta_title" id="metaTitleInput" maxlength="60" value="<?= e($p['meta_title'] ?? '') ?>" placeholder="VD: <?= e($p['name'] ?? 'Tên sản phẩm') ?>">
                            <div class="d-flex justify-content-between form-text small">
                                <span>Khuyến nghị 50-60 ký tự. Để trống sẽ lấy theo Tên sản phẩm.</span>
                                <span class="fw-semibold" id="metaTitleCount">0/60</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Meta description (mô tả SEO)</label>
                            <textarea class="form-control" name="meta_description" id="metaDescInput" rows="3" maxlength="160" placeholder="VD: <?= e($p['summary'] ?? 'Mô tả ngắn sản phẩm') ?>"><?= e($p['meta_description'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between form-text small">
                                <span>Khuyến nghị 150-160 ký tự. Để trống sẽ lấy theo Mô tả ngắn.</span>
                                <span class="fw-semibold" id="metaDescCount">0/160</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <div class="form-check form-switch toggle-row">
                            <input class="form-check-input" type="checkbox" name="status" <?= ($p['status'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Hoạt động (hiển thị cho khách)</label>
                        </div>
                        <div class="form-check form-switch toggle-row">
                            <input class="form-check-input" type="checkbox" name="is_featured" <?= !empty($p['is_featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Sản phẩm nổi bật</label>
                        </div>
                        <div class="form-check form-switch toggle-row">
                            <input class="form-check-input" type="checkbox" name="is_new" <?= !empty($p['is_new']) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Hàng mới</label>
                        </div>
                        <div class="form-check form-switch toggle-row">
                            <input class="form-check-input" type="checkbox" name="is_best_seller" <?= !empty($p['is_best_seller']) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Bán chạy</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky save bar -->
    <div class="admin-sticky-save">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="small text-muted d-none d-md-block">
                <span id="saveSeenText">Kiểm tra đủ thông tin bắt buộc trước khi lưu.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>/quan-tri/san-pham" class="btn btn-light">Hủy</a>
                <button type="submit" class="btn btn-primary btn-lg" id="btnSave"><?= icon('bi-check-lg', 'me-1') ?>Lưu sản phẩm</button>
            </div>
        </div>
    </div>
</form>

<?php $__variantJson = array_map(function ($__v) {
    return [
        'id'       => (int)$__v['id'],
        'name'     => $__v['name'] ?? 'Màu sắc',
        'value'    => $__v['value'] ?? '',
        'image_url'=> !empty($__v['image_url']) ? image_url($__v['image_url']) : '',
        'price'    => (string)($__v['price_adjust'] ?? '0'),
        'stock'    => (int)($__v['stock'] ?? 0),
        'sku'      => $__v['sku'] ?? '',
    ];
}, $variants); ?>

<script>
(function () {
    const fmtVND = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

    // ---------- Sticky save ----------
    const sticky = document.querySelector('.admin-sticky-save');
    window.addEventListener('scroll', () => {
        const bottom = document.documentElement.scrollHeight - window.innerHeight - window.scrollY;
        sticky.classList.toggle('is-floating', bottom > 60);
    }, { passive: true });
    sticky.classList.add('is-floating');

    // ---------- Check-mark validation per section ----------
    const reqFields = () => document.querySelectorAll('#productForm [data-req]');
    function checkSection(secId) {
        const sec = document.querySelector('.acc-sec[data-sec="' + secId + '"]');
        if (!sec) return;
        const mark = sec.querySelector('.acc-check');
        if (!mark) return;
        const names = (mark.dataset.req || '').split(',');
        let ok = true;
        for (const n of names) {
            if (n === 'variant-ok') {
                // Biến thể: nếu >=2 dòng thì mỗi dòng cần ảnh riêng
                const rows = sec.querySelectorAll('.variant-row');
                if (rows.length >= 2) {
                    rows.forEach(r => {
                        if (!r.querySelector('.variant-thumb') && !r.querySelector('[data-vfile]').files.length) ok = false;
                    });
                }
                continue;
            }
            const el = sec.querySelector('[name="' + n + '"]');
            if (el && !el.value.trim()) ok = false;
        }
        mark.classList.toggle('done', ok);
        return ok;
    }
    reqFields().forEach(el => {
        el.addEventListener('input', () => checkSection(el.closest('.acc-sec').dataset.sec));
        el.addEventListener('change', () => checkSection(el.closest('.acc-sec').dataset.sec));
    });
    // variants always resolve their own
    ['acc-basic', 'acc-images', 'acc-price', 'acc-variants'].forEach(checkSection);

    // ---------- Variant rows: add / remove ----------
    const tbody = document.getElementById('variantRows');
    const countText = document.getElementById('variantCountText');
    function emptyRow() {
        const tr = document.createElement('tr');
        tr.className = 'variant-row';
        tr.innerHTML =
            '<td><input type="hidden" name="variant_id[]" value="0">' +
            '<input type="text" name="variant_name[]" class="form-control form-control-sm" value="Màu sắc" placeholder="Màu sắc"></td>' +
            '<td><input type="text" name="variant_value[]" class="form-control form-control-sm" placeholder="Xanh rêu"></td>' +
            '<td><div class="form-control-sm py-1 d-flex align-items-center gap-1"><?= icon('bi-image') ?>' +
            '<label class="mb-0 text-primary small" style="cursor:pointer">Chọn file<input type="file" name="variant_image[]" class="form-control form-control-sm d-none" accept="image/*" data-vfile></label>' +
            '<span class="small text-muted vfile-name"></span></div></td>' +
            '<td><input type="number" name="variant_price_adjust[]" class="form-control form-control-sm" value="0" step="1000" placeholder="0"></td>' +
            '<td><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="0" min="0"></td>' +
            '<td><input type="text" name="variant_sku[]" class="form-control form-control-sm" placeholder="SKU"></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-variant title="Xóa biến thể"><?= icon('bi-trash') ?></button></td>';
        wireRow(tr);
        return tr;
    }
    function wireRow(tr) {
        tr.querySelector('[data-vfile]').addEventListener('change', function (e) {
            const name = tr.querySelector('.vfile-name');
            name.textContent = e.target.files.length ? e.target.files[0].name : '';
        });
        tr.querySelector('[data-remove-variant]').addEventListener('click', function () {
            tr.remove();
            updateVariantCounter();
            checkSection('acc-variants');
        });
    }
    document.querySelectorAll('.variant-row').forEach(wireRow);
    document.getElementById('btnAddVariant').addEventListener('click', function () {
        tbody.appendChild(emptyRow());
        updateVariantCounter();
        checkSection('acc-variants');
    });
    function updateVariantCounter() {
        const n = tbody.querySelectorAll('.variant-row').length;
        countText.textContent = n + ' biến thể';
    }

    // ---------- Slug tự động từ Tên (giống slugify PHP) + check khối SEO ----------
    const nameInput = document.querySelector('[name="name"]');
    const slugInput = document.getElementById('slugInput');
    let slugManual = !!slugInput && slugInput.value.trim() !== '';
    function vnSlug(s) {
        s = s.toLowerCase();
        const v = ['à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ',
                   'è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ',
                   'ì','í','ỉ','ĩ','ị',
                   'ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ',
                   'ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự',
                   'ỳ','ý','ỷ','ỹ','ỵ','đ'];
        const l = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                   'e','e','e','e','e','e','e','e','e','e','e',
                   'i','i','i','i','i',
                   'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                   'u','u','u','u','u','u','u','u','u','u','u',
                   'y','y','y','y','y','d'];
        let out = '';
        for (const ch of s) { const i = v.indexOf(ch); out += i >= 0 ? l[i] : ch; }
        return out.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || '';
    }
    function syncSlug() {
        if (slugInput && !slugManual) {
            slugInput.value = vnSlug(nameInput ? nameInput.value : '');
            checkSection('acc-seo');
        }
    }
    if (nameInput) nameInput.addEventListener('input', syncSlug);
    if (slugInput) slugInput.addEventListener('input', () => {
        slugManual = slugInput.value.trim() !== '';
        checkSection('acc-seo');
    });
    syncSlug();

    // ---------- Bộ đếm ký tự Meta title / Meta description ----------
    function wireCounter(elId, countId, max, warnAt) {
        const el = document.getElementById(elId);
        const cnt = document.getElementById(countId);
        if (!el || !cnt) return;
        const paint = () => {
            const n = el.value.length;
            cnt.textContent = n + '/' + max;
            cnt.classList.toggle('text-danger', n > warnAt);
        };
        el.addEventListener('input', paint);
        paint();
    }
    wireCounter('metaTitleInput', 'metaTitleCount', 60, 60);
    wireCounter('metaDescInput', 'metaDescCount', 160, 160);

    // ---------- Submit: open failing section + scroll + toast ----------
    document.getElementById('productForm').addEventListener('submit', function (e) {
        const secs = ['acc-basic', 'acc-price'];
        let firstBad = null;
        reqFields().forEach(el => {
            if (el.value.trim() === '' && !firstBad) firstBad = el;
        });
        // variant image rule
        const varSec = document.querySelector('.acc-sec[data-sec="acc-variants"]');
        if (varSec) {
            const rows = varSec.querySelectorAll('.variant-row');
            if (rows.length >= 2) {
                rows.forEach(r => {
                    const has = r.querySelector('.variant-thumb') || r.querySelector('[data-vfile]').files.length;
                    if (!has && !firstBad) firstBad = r.querySelector('[data-vfile]');
                });
            }
        }
        if (firstBad) {
            e.preventDefault();
            const sec = firstBad.closest('.acc-sec');
            const btn = sec.querySelector('.accordion-button');
            if (btn.classList.contains('collapsed')) btn.click();
            requestAnimationFrame(() => {
                firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstBad.focus({ preventScroll: true });
            });
            window.WoodConToast && WoodConToast('Vui lòng hoàn thành thông tin bắt buộc', 'danger');
            return;
        }
        // HTML5 native check
        if (!this.checkValidity()) {
            e.preventDefault();
            this.reportValidity();
        }
    });
})();
</script>
