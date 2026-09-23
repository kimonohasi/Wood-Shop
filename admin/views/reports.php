<?php
/** Báo cáo tài chính admin - 3 chế độ: 1=Nghiệp vụ, 2=Kế toán (Hóa đơn), 3=Tất cả */
declare(strict_types=1);
$r = $report;
$rmode = $r['mode'] ?? '1';
$modeTabs = [
    '1' => [
        'label' => 'Đơn đã giao',
        'badge' => null,
    ],
    '2' => [
        'label' => 'Số liệu nộp thuế',
        'badge' => null,
    ],
    '3' => [
        'label' => 'Tất cả',
        'badge' => null,
    ],
];
$curTab = $modeTabs[$rmode] ?? $modeTabs['1'];
$baseFilter = 'from=' . e($r['from']) . '&to=' . e($r['to']) . '&group=' . e($_GET['group'] ?? 'day');
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Báo cáo tài chính</h1>
    </div>
    <div class="dropdown">
        <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item report-export" href="<?= BASE_URL ?>/quan-tri/bao-cao/xuat?<?= $baseFilter ?>&mode=<?= $rmode ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
            <li><a class="dropdown-item report-export" href="<?= BASE_URL ?>/quan-tri/bao-cao/xuat?<?= $baseFilter ?>&mode=<?= $rmode ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
        </ul>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body pb-0">
        <div id="reportTabs" class="seg-tabs wc-mode-tabs mb-3" role="tablist" data-ajax="1">
            <?php foreach ($modeTabs as $__m => $__t): ?>
            <a class="seg-item <?= $rmode === $__m ? 'active' : '' ?>" role="tab" aria-selected="<?= $rmode === $__m ? 'true' : 'false' ?>"
               href="<?= BASE_URL ?>/quan-tri/bao-cao?<?= $baseFilter ?>&mode=<?= $__m ?>">
                    <span><?= e($__t['label']) ?></span>
                    <?php if (!empty($__t['badge'])): ?>
                    <span class="badge rounded-pill ms-1" style="background:<?= $__t['badge'][0] === 'success' ? '#198754' : '#ffc107' ?>;color:#fff;font-size:.65em">
                        <?= icon($__t['badge'][1], 'me-1') ?><?= e($__t['badge'][2]) ?>
                    </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form class="row g-2 mb-3" method="get">
            <input type="hidden" name="route" value="quan-tri/bao-cao">
            <input type="hidden" name="mode" value="<?= e($rmode) ?>">
            <div class="col-md-3">
                <label class="form-label small text-muted">Từ ngày</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= e($r['from']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Đến ngày</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= e($r['to']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Nhóm theo</label>
                <select class="form-select form-select-sm" name="group">
                    <option value="day" <?= ($_GET['group'] ?? 'day') === 'day' ? 'selected' : '' ?>>Ngày</option>
                    <option value="week" <?= ($_GET['group'] ?? 'day') === 'week' ? 'selected' : '' ?>>Tuần</option>
                    <option value="month" <?= ($_GET['group'] ?? 'day') === 'month' ? 'selected' : '' ?>>Tháng</option>
                    <option value="quarter" <?= ($_GET['group'] ?? 'day') === 'quarter' ? 'selected' : '' ?>>Quý</option>
                    <option value="year" <?= ($_GET['group'] ?? 'day') === 'year' ? 'selected' : '' ?>>Năm</option>
                </select>
            </div>
            <div class="col-md-3 d-grid align-items-end"><button class="btn btn-outline-primary btn-sm">Xem báo cáo</button></div>
        </form>
    </div>
</div>

<div id="reportRegion">
<?php require __DIR__ . '/reports_region.php'; ?>
</div>

<script>
/* Chuyển mode Báo cáo không tải lại trang — pill trượt giống Dashboard */
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.getElementById('reportTabs');
    var region = document.getElementById('reportRegion');
    if (!tabs || !region) return;
    var frm = tabs.querySelector('form, form.row.g-2') || document.querySelector('form.row.g-2');

    tabs.querySelectorAll('.seg-item').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            if (a.classList.contains('active')) return;
            var params = new URLSearchParams(a.getAttribute('href').split('?')[1] || '');
            var mode = params.get('mode') || '1';
            var from = (frm && frm.elements.from) ? frm.elements.from.value : params.get('from') || '';
            var to = (frm && frm.elements.to) ? frm.elements.to.value : params.get('to') || '';
            var group = (frm && frm.elements.group) ? frm.elements.group.value : params.get('group') || 'day';
            var qs = new URLSearchParams({ frag: '1', mode: mode, from: from, to: to, group: group });

            fetch(WOODCON_BASE_URL + '/quan-tri/bao-cao?' + qs.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                 .then(function (data) {
                     if (data.ok === true) {
                         region.innerHTML = data.html;
                         if (frm && frm.elements.mode) frm.elements.mode.value = mode;
                     }
                     document.querySelectorAll('.report-export').forEach(function (x) {
                        x.href = x.href.replace(/([?&]mode=)\d+/, '$1' + mode);
                    });
                    history.replaceState(null, '', a.getAttribute('href'));
                })
                .catch(function () { window.location.href = a.getAttribute('href'); });
        });
    });
});
</script>
