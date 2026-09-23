<?php
/** Dashboard admin — Bento Grid; biểu đồ doanh thu Chart.js (vendor local); widget trạng thái đơn
    lưới auto-fit + popup tra cứu đơn theo trạng thái (chỉ đóng bằng nút X, không đóng khi bấm overlay) */
declare(strict_types=1);
use WoodCon\Order;

$sum = $data['sum'];
$orderDist = $data['orderDist'];
$revChart = $data['revChart'];
$bestSellers = $data['bestSellers'];
$recent = $data['recent'];
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title">Dashboard</h1>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary admin-refresh" type="button"><?= icon('bi-arrow-clockwise', 'me-1') ?>Làm mới</button>
        <a href="<?= BASE_URL ?>/quan-tri/bao-cao" class="btn btn-sm btn-primary"><?= icon('bi-bar-chart', 'me-1') ?>Xem báo cáo</a>
    </div>
</div>

<!-- ===== STAT CARDS (top) ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label" title="Doanh thu = tổng giá trị các đơn trạng thái &quot;Đã giao thành công&quot; giao xong hôm nay (tính theo ngày giao, kể cả đơn COD chưa thu tiền).">Doanh thu hôm nay</div>
                    <div class="stat-value mt-1"><?= format_money((int)$sum['today_revenue']) ?></div>
                </div>
                <?= icon('bi-cash-stack', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label" title="Tổng giá trị TÍCH LŨY mọi đơn trạng thái &quot;Đã giao thành công&quot; (kể cả COD chưa thu tiền, mọi thời điểm).">Tổng doanh thu</div>
                    <div class="stat-value mt-1"><?= format_money((int)$sum['revenue']) ?></div>
                </div>
                <?= icon('bi-graph-up-arrow', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hàng sắp hết</div>
                    <div class="stat-value mt-1 <?= (int)$sum['low_stock'] ? 'text-danger' : '' ?>"><?= (int)$sum['low_stock'] ?></div>
                </div>
                <?= icon('bi-exclamation-triangle', 'stat-icon', 'style="color:var(--wc-danger)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đơn chờ xác nhận</div>
                    <div class="stat-value mt-1 text-warning"><?= (int)$sum['pending_orders'] ?></div>
                </div>
                <?= icon('bi-hourglass-split', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== 4 thẻ KPI ưu tiên (Phần A — prompt v10): Tổng thuế / Tổng sản phẩm / Lợi nhuận ròng / Tổng người dùng.
     Cùng class admin-stat, cùng cột (col-6 col-xl-3) với 4 thẻ hàng đầu → kích thước & khoảng cách khớp tuyệt đối. ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label" title="Tổng VAT (sản phẩm + vận chuyển + lắp đặt, đã tách ngược) của những đơn ĐÃ GIAO và CÓ HÓA ĐƠN bán — không tính đơn chờ xử lý/đã hủy. Tích lũy, khớp cách tính &quot;Tổng doanh thu&quot;.">Tổng thuế</div>
                    <div class="stat-value mt-1"><?= format_money((float)$sum['total_vat']) ?></div>
                </div>
                <?= icon('bi-receipt-cutoff', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng sản phẩm</div>
                    <div class="stat-value mt-1"><?= (int)$sum['active_products'] ?></div>
                </div>
                <?= icon('bi-box-seam', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label" title="Lợi nhuận ròng = Doanh thu đã giao − (Giá vốn sản phẩm đã bán + Phí vận chuyển + VAT đã nộp). Dùng field giá vốn products.cost có sẵn. Âm = lỗ.">Lợi nhuận ròng</div>
                    <div class="stat-value mt-1 <?= (float)$sum['net_profit'] < 0 ? 'text-danger' : '' ?>"><?= format_money((float)$sum['net_profit']) ?></div>
                </div>
                <?= icon('bi-piggy-bank', 'stat-icon', 'style="color:' . ((float)$sum['net_profit'] < 0 ? 'var(--wc-danger)' : '') . '"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng người dùng</div>
                    <div class="stat-value mt-1"><?= (int)$sum['customer_users'] ?></div>
                </div>
                <?= icon('bi-people', 'stat-icon') ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== THẺ KPI BỔ SUNG ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng nhân sự</div>
                    <div class="stat-value mt-1"><?= (int)$sum['total_staff'] ?></div>
                </div>
                <?= icon('bi-people-fill', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Công nợ thu</div>
                    <div class="stat-value mt-1"><?= format_money((float)$sum['receivable_amount']) ?></div>
                </div>
                <?= icon('bi-cash-coin', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng đơn hàng</div>
                    <div class="stat-value mt-1"><?= (int)$sum['total_orders'] ?></div>
                </div>
                <?= icon('bi-basket', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đơn hoàn thành</div>
                    <div class="stat-value mt-1 text-success"><?= (int)$sum['delivered_orders'] ?></div>
                </div>
                <?= icon('bi-check2-circle', 'stat-icon') ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== BENTO GRID ===== -->
<div class="bento">

    <!-- Doanh thu: con số lớn = ĐÚNG 1 kỳ hiện tại (Hôm nay/Tuần này/Tháng này/Năm nay); biểu đồ LINE = xu hướng lịch sử -->
    <div class="col-8s">
        <div class="admin-card h-100">
            <div class="card-head d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-none d-md-inline">Doanh thu</span>
                        <span class="small text-muted fw-normal d-none d-md-inline" id="revWindowLabel">Biểu đồ 7 ngày gần nhất</span>
                    </div>
                <div class="seg-tabs rev-tabs" role="group" aria-label="Khoảng doanh thu">
                    <button type="button" class="seg-item rev-tab active" data-period="day">Ngày</button>
                    <button type="button" class="seg-item rev-tab" data-period="week">Tuần</button>
                    <button type="button" class="seg-item rev-tab" data-period="month">Tháng</button>
                    <button type="button" class="seg-item rev-tab" data-period="year">Năm</button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end flex-wrap gap-2 mb-2">
                    <div>
                        <div class="small text-muted" title="Doanh thu ĐÚNG 1 kỳ đang chọn (Hôm nay / Tuần này / Tháng này / Năm nay): đơn trạng thái &quot;Đã giao thành công&quot;, nhận diện theo ngày giao hàng (delivered_at), kể cả COD chưa thu tiền. Biểu đồ bên dưới là xu hướng lịch sử của cửa sổ, không phải con số này.">Doanh thu kỳ hiện tại</div>
                        <div class="fs-4 fw-bold" id="revTotal"><?= format_money((int)$sum['today_revenue']) ?></div>
                        <div class="small text-muted" id="revPeriodLabel">Hôm nay</div>
                    </div>
                </div>
                <div id="revChartWrap" class="rev-chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div id="revColumns" class="rev-columns d-none"></div>
                <div id="revEmpty" class="empty-state d-none"><?= icon('bi-graph-up-arrow') ?>Chưa có dữ liệu cho khoảng này</div>
            </div>
        </div>
    </div>

    <!-- Trạng thái đơn: stacked bar + lưới auto-fit CHỈ trạng thái có đơn; dòng "+N chưa có đơn" mở rộng trượt; bấm ô → popup tra cứu -->
    <div class="col-4s">
        <div class="admin-card h-100">
            <div class="card-head d-flex justify-content-between align-items-center">
                <span>Trạng thái đơn hàng</span>
                <span class="st-total">Tổng: <?= (int)$sum['total_orders'] ?> đơn</span>
            </div>
            <div class="card-body">
                <?php
                $__stColors = ['pending'=>'#B58900','manual_verifying'=>'#6C71C4','confirmed'=>'#268BD2','preparing'=>'#839496','shipping'=>'#586E75','delivery_failed'=>'#CB4B16','delivered'=>'#859900','returned'=>'#DC322F','cancelled'=>'#93A1A1'];
                $__stOrder  = ['pending','confirmed','preparing','shipping','delivered','manual_verifying','delivery_failed','returned','cancelled'];
                $__stTot    = (int)$sum['total_orders'];
                $__stZero   = [];
                foreach ($__stOrder as $__s) { if ((int)($orderDist[$__s] ?? 0) <= 0) $__stZero[] = $__s; }
                ?>
                <div class="st-stack" title="Tỷ lệ theo 9 trạng thái đơn hàng">
                    <?php foreach ($__stOrder as $__st):
                        $__c = (int)($orderDist[$__st] ?? 0);
                        if ($__c <= 0) continue; ?>
                        <i class="st-seg" style="width:<?= round($__c / max($__stTot, 1) * 100) ?>%;background:<?= $__stColors[$__st] ?>" title="<?= e(Order::STATUS_LABEL[$__st] ?? $__st) ?> — <?= $__c ?> đơn"></i>
                    <?php endforeach; ?>
                </div>
                <div class="st-grid">
                    <?php foreach ($__stOrder as $__st):
                        $__c = (int)($orderDist[$__st] ?? 0);
                        if ($__c <= 0) continue; ?>
                        <button type="button" class="st-cell" data-status="<?= $__st ?>" style="--st-color:<?= $__stColors[$__st] ?>" title="Xem đơn trạng thái: <?= e(Order::STATUS_LABEL[$__st] ?? $__st) ?>">
                            <span class="st-name"><?= e(Order::STATUS_LABEL[$__st] ?? $__st) ?></span>
                            <span class="st-count"><?= $__c ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($__stZero): ?>
                    <button type="button" class="st-more" id="stMore" aria-expanded="false">
                        <?= icon('bi-chevron-down', 'st-more-icon') ?>
                        <span>+ <?= count($__stZero) ?> trạng thái khác chưa có đơn hàng</span>
                    </button>
                    <div class="st-zero" id="stZero" style="max-height:0">
                        <div class="st-zero-list">
                            <?php foreach ($__stOrder as $__st):
                                if ((int)($orderDist[$__st] ?? 0) > 0) continue; ?>
                                <button type="button" class="st-zero-item" data-status="<?= $__st ?>" style="--st-color:<?= $__stColors[$__st] ?>" title="Xem đơn trạng thái: <?= e(Order::STATUS_LABEL[$__st] ?? $__st) ?> (0 đơn)">
                                    <i class="st-dot" style="background:<?= $__stColors[$__st] ?>"></i>
                                    <span class="st-name"><?= e(Order::STATUS_LABEL[$__st] ?? $__st) ?></span>
                                    <span class="st-count">0</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sản phẩm bán chạy (mỗi hàng = lưới 3 cột cố định badge/ảnh/thông tin; bar tỉ lệ theo sold max) -->
    <div class="col-4s">
        <div class="admin-card h-100">
            <div class="card-head d-flex justify-content-between align-items-baseline">
                <span>Sản phẩm bán chạy</span>
                <a href="<?= BASE_URL ?>/quan-tri/bao-cao" class="small text-decoration-none">Xem thêm →</a>
            </div>
            <div class="card-body">
                <?php if (empty($bestSellers)): ?>
                    <div class="empty-state"><?= icon('bi-trophy') ?>Chưa có đơn bán<button class="btn btn-sm btn-outline-primary mt-2" onclick="location.href='<?= BASE_URL ?>/quan-tri/san-pham'">Thêm sản phẩm</button></div>
                <?php else:
                    $__topSold = max((int)$bestSellers[0]['sold'], 1); ?>
                    <div class="top-list">
                        <?php foreach ($bestSellers as $i => $__p):
                            $__rank = $i + 1;
                            $__sold = (int)$__p['sold']; ?>
                            <a class="top-item rank<?= $__rank ?>" href="<?= BASE_URL ?>/quan-tri/san-pham/sua/<?= (int)$__p['id'] ?>" title="Xem sản phẩm: <?= e($__p['name']) ?>">
                                <span class="top-rank"><?= $__rank ?></span>
                                <img src="<?= e(image_url($__p['cover_image'] ?? '')) ?>" alt="" loading="lazy" class="top-img">
                                <span class="top-info">
                                    <span class="top-name"><?= e($__p['name']) ?></span>
                                    <span class="top-sold">Đã bán <?= $__sold ?></span>
                                    <span class="top-track"><i class="top-bar" style="width:<?= round($__sold / $__topSold * 100, 2) ?>%"></i></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Đơn hàng mới nhất -->
    <div class="col-8s">
        <div class="admin-card h-100">
            <div class="card-head d-flex justify-content-between align-items-center">
                <span>Đơn hàng mới nhất</span>
                <a href="<?= BASE_URL ?>/quan-tri/don-hang" class="small text-decoration-none">Xem tất cả →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                    <div class="empty-state"><?= icon('bi-receipt') ?>Chưa có đơn hàng</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Phương thức</th><th class="money">Tổng</th><th>Trạng thái</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent as $__o): ?>
                                    <tr>
                                        <td data-label="Mã đơn"><a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__o['order_code']) ?>" class="fw-semibold text-decoration-none"><?= e($__o['order_code']) ?></a></td>
                                        <td data-label="Khách hàng"><?= e($__o['customer_name']) ?></td>
                                        <td data-label="Phương thức"><span class="badge text-bg-light"><?= strtoupper($__o['payment_method']) ?></span></td>
                                        <td data-label="Tổng" class="money fw-semibold"><?= format_money((int)$__o['total_amount']) ?></td>
                                        <td data-label="Trạng thái"><span class="badge text-bg-<?= ['pending'=>'warning','manual_verifying'=>'danger','confirmed'=>'info','preparing'=>'secondary','shipping'=>'primary','delivery_failed'=>'dark','delivered'=>'success','returned'=>'danger','cancelled'=>'secondary'][$__o['order_status']] ?? 'secondary' ?>"><?= e(Order::STATUS_LABEL[$__o['order_status']] ?? $__o['order_status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- POPUP tra cứu nhanh đơn theo trạng thái: CHỈ đóng bằng nút X (không đóng khi bấm overlay)
     Tiêu đề + ô tìm kiếm + nút X cố định; thân danh sách cuộn độc lập -->
<div class="status-modal" id="statusModal" aria-hidden="true">
    <div class="status-modal-backdrop"></div>
    <div class="status-modal-box" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle">
        <div class="status-modal-head">
            <h3 class="status-modal-title" id="statusModalTitle">Đơn hàng</h3>
            <button type="button" class="status-modal-close" id="statusModalClose" aria-label="Đóng popup (chỉ đóng bằng nút X)">
                <?= icon('bi-x-lg') ?>
            </button>
        </div>
        <div class="status-modal-search d-none" id="statusModalSearchWrap">
            <?= icon('bi-search') ?>
            <input type="text" id="statusSearch" placeholder="Tìm mã đơn / tên khách / SĐT..." autocomplete="off">
        </div>
        <div class="status-modal-body" id="statusModalBody"></div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/chart.umd.min.js"></script>
<script>
(function () {
    /* ===== Doanh thu: Chart.js LINE. Con số lớn = ĐÚNG 1 kỳ hiện tại; biểu đồ = xu hướng cửa sổ.
         Biểu đồ vẽ NGAY từ dữ liệu PHP server-render (không đợi AJAX) → luôn hiển thị dù fetch lỗi. ===== */
    var revTotalEl = document.getElementById('revTotal');
    var revPeriodEl = document.getElementById('revPeriodLabel');
    var revWindowEl = document.getElementById('revWindowLabel');
    var revEmptyEl = document.getElementById('revEmpty');
    var revColsEl = document.getElementById('revColumns');
    var revWrapEl = document.getElementById('revChartWrap');
    var revCanvas = document.getElementById('revenueChart');
    var revWindowMap = { day: '7 ngày gần nhất', week: '8 tuần gần nhất', month: '12 tháng gần nhất', year: '5 năm gần nhất' };
    var revChart = null;

    if (revTotalEl) {
        var revInit = <?= json_encode([
            'labels' => $revChart['labels'],
            'values' => array_map(fn($v) => (float)$v, $revChart['values']),
        ]) ?>;
        var revAccent = getComputedStyle(document.documentElement).getPropertyValue('--wc-accent').trim() || '#268bd2';
        var hasChartLib = typeof Chart !== 'undefined' && !!revCanvas;

        function moneyFmt(n) {
            return Number(n || 0).toLocaleString('vi-VN');
        }

        function showEmpty() {
            if (revEmptyEl) revEmptyEl.classList.remove('d-none');
            if (revColsEl) { revColsEl.classList.add('d-none'); revColsEl.innerHTML = ''; }
            if (revWrapEl) revWrapEl.classList.add('d-none');
        }

        /* Fallback: cột thuần CSS (chỉ dùng khi Chart.js không tải được hoặc renderChart lỗi) */
        function renderColumns(labels, vals) {
            if (revEmptyEl) revEmptyEl.classList.add('d-none');
            if (revWrapEl) revWrapEl.classList.add('d-none');
            if (!revColsEl) return;
            revColsEl.classList.remove('d-none');
            var max = 0, i;
            for (i = 0; i < vals.length; i++) if (vals[i] > max) max = vals[i];
            revColsEl.innerHTML = '';
            for (i = 0; i < vals.length; i++) {
                var v = vals[i] || 0;
                var pct = max > 0 ? Math.round(v / max * 100) : 0;
                if (v > 0 && pct < 3) pct = 3;
                var col = document.createElement('div');
                col.className = 'rev-column';
                var val = document.createElement('div');
                val.className = 'rev-val';
                val.textContent = v > 0 ? moneyFmt(v) : '0';
                var grow = document.createElement('div');
                grow.className = 'rev-grow';
                var bar = document.createElement('div');
                bar.className = 'rev-bar';
                bar.style.height = (v > 0 ? pct : 2) + '%';
                bar.style.opacity = v > 0 ? 1 : 0.2;
                grow.appendChild(bar);
                var lab = document.createElement('div');
                lab.className = 'rev-lab';
                lab.textContent = labels[i] || '';
                col.appendChild(val); col.appendChild(grow); col.appendChild(lab);
                revColsEl.appendChild(col);
            }
        }

        function renderChart(labels, vals) {
            try {
                if (revEmptyEl) revEmptyEl.classList.add('d-none');
                if (revColsEl) revColsEl.classList.add('d-none');
                if (revWrapEl) revWrapEl.classList.remove('d-none');
                if (revChart) revChart.destroy();
                var ctx = revCanvas.getContext('2d');
                var h = (revCanvas.parentNode && revCanvas.parentNode.clientHeight) || 220;
                var grad = ctx.createLinearGradient(0, 0, 0, h);
                grad.addColorStop(0, 'rgba(38,139,210,0.20)');
                grad.addColorStop(1, 'rgba(38,139,210,0.02)');
                revChart = new Chart(revCanvas, {
                    type: 'line',
                    data: { labels: labels, datasets: [{
                        data: vals,
                        borderColor: revAccent,
                        backgroundColor: grad,
                        fill: true,
                        tension: 0.38,
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointHoverRadius: 5.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: revAccent,
                        pointBorderWidth: 2
                    }]},
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(30,32,38,0.94)',
                                cornerRadius: 8,
                                padding: 10,
                                callbacks: {
                                    label: function (c) { return ' Doanh thu: ' + moneyFmt(c.parsed.y) + 'đ'; }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: true, color: 'rgba(0,0,0,0.3)' },
                                ticks: { color: '#5a5f6a', font: { size: 12, weight: 600 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.07)', drawBorder: false },
                                border: { display: false },
                                ticks: {
                                    color: '#8a8f99',
                                    font: { size: 11 },
                                    maxTicksLimit: 4,
                                    callback: function (v) {
                                        if (v >= 1000000000) return (v / 1000000000).toFixed(1).replace(/\.0$/, '') + ' tỷ';
                                        if (v >= 1000000) return (v / 1000000).toFixed(1).replace(/\.0$/, '') + 'tr';
                                        if (v >= 1000) return (v / 1000).toFixed(0) + 'k';
                                        return v;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                renderColumns(labels, vals);
            }
        }

        function draw(labels, vals) {
            if (!vals.some(function (v) { return v > 0; })) { showEmpty(); return; }
            if (hasChartLib) renderChart(labels, vals);
            else renderColumns(labels, vals);
        }

        function loadRevenue(period) {
            fetch(<?= json_encode(BASE_URL) ?> + '/quan-tri/doanh-thu-ajax?period=' + period, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.querySelectorAll('.rev-tab').forEach(function (b) { b.classList.remove('active'); });
                    var activeBtn = document.querySelector('.rev-tab[data-period="' + period + '"]');
                    if (activeBtn) activeBtn.classList.add('active');
                    if (revPeriodEl) revPeriodEl.textContent = data.periodLabel || '';
                    if (revWindowEl) revWindowEl.textContent = revWindowMap[period] || '';
                    if (revTotalEl) revTotalEl.textContent = moneyFmt(data.periodTotal) + 'đ';
                    draw(data.labels || [], data.values || []);
                })
                .catch(function () {
                    if (revWindowEl) revWindowEl.textContent = revWindowMap[period] || '';
                });
        }

        document.querySelectorAll('.rev-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                loadRevenue(btn.getAttribute('data-period'));
            });
        });

        // Vẽ NGAY bằng dữ liệu PHP server-render → biểu đồ luôn hiển thị kể cả khi fetch lỗi.
        draw(revInit.labels, revInit.values);
    }

    /* ===== Trạng thái đơn: CHỈ hiện trạng thái có đơn (lưới auto-fit). Dòng "+N chưa có đơn hàng"
         mở rộng trượt (max-height transition). Bấm ô trạng thái → POPUP tra cứu đơn đúng trạng thái.
         Popup CHỈ đóng bằng nút X — bấm overlay/ngoài popup KHÔNG đóng (thuần DOM, không cần thư viện). ===== */
    (function () {
        var STATUS_LABELS = <?= json_encode(Order::STATUS_LABEL) ?>;
        var BASE = <?= json_encode(BASE_URL) ?>;

        var zeroWrap = document.getElementById('stZero');
        var moreBtn = document.getElementById('stMore');
        if (moreBtn && zeroWrap) {
            moreBtn.addEventListener('click', function () {
                var open = zeroWrap.classList.toggle('st-zero-open');
                zeroWrap.style.maxHeight = open ? zeroWrap.scrollHeight + 'px' : '0px';
                moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        var modal = document.getElementById('statusModal');
        if (!modal) return;
        var titleEl = document.getElementById('statusModalTitle');
        var searchWrap = document.getElementById('statusModalSearchWrap');
        var searchInput = document.getElementById('statusSearch');
        var bodyWrap = document.getElementById('statusModalBody');
        var closeBtn = document.getElementById('statusModalClose');
        var lastTrigger = null;
        var reqId = 0;

        /* Bỏ dấu tiếng Việt để tìm kiếm gõ nhanh không cần nhập đúng dấu */
        function vnFold(s) {
            return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
        }

        function filterRows() {
            if (!searchInput) return;
            var q = vnFold(searchInput.value.trim());
            var rows = bodyWrap.querySelectorAll('.st-order');
            var visible = 0;
            rows.forEach(function (r) {
                var hit = q === '' || (r.getAttribute('data-search') || '').indexOf(q) !== -1;
                r.classList.toggle('d-none', !hit);
                if (hit) visible++;
            });
            var noResult = document.getElementById('statusNoResult');
            if (noResult) noResult.classList.toggle('d-none', visible > 0 || q === '');
        }

        function addOrderRow(order) {
            var a = document.createElement('a');
            a.className = 'st-order';
            a.href = BASE + '/quan-tri/don-hang/xem/' + encodeURIComponent(order.order_code);
            a.setAttribute('data-search', vnFold(order.order_code + ' ' + order.customer_name + ' ' + order.customer_phone));

            var thumb = document.createElement('span');
            thumb.className = 'st-order-thumb';
            var img = document.createElement('img');
            img.loading = 'lazy';
            img.src = order.first_image;
            img.alt = '';
            thumb.appendChild(img);
            if (order.plus > 0) {
                var plus = document.createElement('i');
                plus.className = 'st-order-plus';
                plus.textContent = '+' + order.plus;
                thumb.appendChild(plus);
            }
            a.appendChild(thumb);

            var info = document.createElement('span');
            info.className = 'st-order-info';
            var code = document.createElement('div');
            code.className = 'st-order-code';
            code.textContent = order.order_code;
            var cust = document.createElement('div');
            cust.className = 'st-order-cust';
            cust.textContent = order.customer_name + ' · ' + order.customer_phone;
            info.appendChild(code);
            info.appendChild(cust);
            a.appendChild(info);

            var side = document.createElement('span');
            side.className = 'st-order-side';
            var money = document.createElement('div');
            money.className = 'st-order-money';
            money.textContent = order.total_amount + 'đ';
            var time = document.createElement('div');
            time.className = 'st-order-time';
            time.textContent = order.time;
            side.appendChild(money);
            side.appendChild(time);
            a.appendChild(side);

            bodyWrap.appendChild(a);
        }

        function renderState(iconName, msg) {
            bodyWrap.innerHTML = '';
            var box = document.createElement('div');
            box.className = 'st-order-empty';
            var ic = document.createElement('svg');
            ic.setAttribute('class', 'icon');
            ic.setAttribute('aria-hidden', 'true');
            var use = document.createElement('use');
            use.setAttribute('href', BASE + '/assets/icons/sprite.svg#' + iconName);
            ic.appendChild(use);
            var sp = document.createElement('span');
            sp.textContent = msg;
            box.appendChild(ic);
            box.appendChild(sp);
            bodyWrap.appendChild(box);
        }

        function openModal(statusKey) {
            if (!(statusKey in STATUS_LABELS)) return;
            reqId++;
            var myReq = reqId;

            titleEl.textContent = 'Đơn hàng — ' + STATUS_LABELS[statusKey];
            if (searchWrap) searchWrap.classList.add('d-none');
            if (searchInput) {
                searchInput.value = '';
                searchInput.oninput = filterRows;
            }
            bodyWrap.innerHTML = '<div class="st-order-loading"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang tải đơn hàng...</div>';
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('wc-status-open');

            fetch(BASE + '/quan-tri/don-hang-theo-trang-thai-ajax?status=' + encodeURIComponent(statusKey), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    if (myReq !== reqId) return;
                    var count = Number(data && data.count || 0);
                    titleEl.textContent = 'Đơn hàng — ' + (data.statusName || STATUS_LABELS[statusKey]) + ' (' + count + ' đơn)';
                    if (!data.ok || !Array.isArray(data.orders)) throw new Error('bad');
                    bodyWrap.innerHTML = '';
                    if (count === 0) {
                        if (searchWrap) searchWrap.classList.add('d-none');
                        renderState('bi-inbox', 'Không có đơn hàng nào ở trạng thái này');
                        return;
                    }
                    data.orders.forEach(addOrderRow);
                    var noResult = document.createElement('div');
                    noResult.id = 'statusNoResult';
                    noResult.className = 'st-order-empty d-none';
                    var box = document.createElement('span');
                    box.innerHTML = '<?= icon('bi-search') ?>';
                    var msg = document.createElement('span');
                    msg.textContent = 'Không tìm thấy đơn nào khớp';
                    noResult.appendChild(box);
                    noResult.appendChild(msg);
                    bodyWrap.appendChild(noResult);
                    if (searchWrap) searchWrap.classList.remove('d-none');
                    if (searchInput) searchInput.focus();
                    filterRows();
                })
                .catch(function () {
                    if (myReq !== reqId) return;
                    if (searchWrap) searchWrap.classList.add('d-none');
                    renderState('bi-exclamation-triangle', 'Không tải được dữ liệu. Thử lại sau.');
                });
        }

        function closeModal() {
            if (!modal.classList.contains('open')) return;
            reqId++;
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('wc-status-open');
            if (lastTrigger && typeof lastTrigger.focus === 'function') lastTrigger.focus();
        }

        // CHỈ nút X đóng popup. Bấm overlay / bấm ra ngoài KHÔNG đóng (đang tra cứu không làm mất kết quả).
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        document.querySelectorAll('.st-cell, .st-zero-item').forEach(function (el) {
            el.addEventListener('click', function () {
                lastTrigger = el;
                openModal(el.getAttribute('data-status'));
            });
        });
    })();

    // Nút làm mới
    document.querySelector('.admin-refresh')?.addEventListener('click', function(){ location.reload(); });
})();
</script>
