<?php
/** Danh sách liên hệ / CSKH admin */
declare(strict_types=1);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Liên hệ / CSKH</h1>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo tên, email, SĐT, chủ đề" value="<?= e($f['q'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="new" <?= ($f['status'] ?? '') === 'new' ? 'selected' : '' ?>>Mới</option>
                    <option value="done" <?= ($f['status'] ?? '') === 'done' ? 'selected' : '' ?>>Đã xử lý</option>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Người gửi</th><th>Chủ đề</th><th>Nội dung</th><th class="text-center">Trạng thái</th><th class="text-end">Thời gian</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6"><div class="empty-state"><?= icon('bi-chat-dots') ?>Chưa có yêu cầu liên hệ</div></td></tr>
                <?php else: foreach ($rows as $__c): ?>
                    <tr>
                        <td data-label="Người gửi">
                            <div class="fw-semibold small"><?= e($__c['name']) ?></div>
                            <div class="text-muted small"><?= e($__c['email']) ?><?= $__c['phone'] ? ' · ' . e($__c['phone']) : '' ?></div>
                        </td>
                        <td data-label="Chủ đề" class="small"><?= e($__c['subject'] ?? '—') ?></td>
                        <td data-label="Nội dung" class="small text-muted" style="max-width:320px"><?= e(mb_strimwidth((string)$__c['message'], 0, 90, '…')) ?></td>
                        <td data-label="Trạng thái" class="text-center"><span class="badge <?= $__c['status'] === 'done' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $__c['status'] === 'done' ? 'Đã xử lý' : 'Mới' ?></span></td>
                        <td data-label="Thời gian" class="text-end text-muted small"><?= format_date($__c['created_at'] ?? null) ?></td>
                        <td data-label="Thao tác" class="text-end">
                            <?php if ($__c['status'] !== 'done'): ?>
                                <form method="post" class="d-inline" action="<?= BASE_URL ?>/quan-tri/lien-he/done">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$__c['id'] ?>">
                                    <button class="btn btn-sm btn-success" data-confirm="Đánh dấu đã xử lý?"><?= icon('bi-check2', 'me-1') ?>Xử lý</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>