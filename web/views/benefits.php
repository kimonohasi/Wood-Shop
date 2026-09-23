<?php
/** Trang quyền lợi thành viên — WoodCon
 *  Thiết kế theo bản tham khảo quyenloi.html · Bootstrap 5.3 + theme.css tokens
 *  Controller render: $tiers, $current, $next, $user, $benefits, $benefitRows
 *  Header/Footer được include bởi BaseController::render() — CHỈ nội dung <main> */
declare(strict_types=1);
$__cur  = $current ?? null;
$__nxt  = $next ?? null;
$__user = $user ?? null;
$__spent = isset($user['total_spent']) ? (float)$user['total_spent'] : 0.0;
$__curId = $__cur ? (int)$__cur['id'] : null;

$__acc = match (strtolower((string)($__cur['name'] ?? ''))) {
    'diamond' => 'diamond', 'vip' => 'vip', default => 'member',
};

$__parts     = is_array($__user) ? preg_split('/\s+/u', trim((string)($__user['name'] ?? ''))) ?: [] : [];
$__fullName  = trim(implode(' ', $__parts));
$__firstName = $__parts[0] ?? '';
$__initials  = mb_strtoupper(
    mb_substr($__parts[0] ?? '', 0, 1)
    . (isset($__parts[1]) && $__parts[1] !== '' ? mb_substr($__parts[1], 0, 1) : '')
);
$__initials = $__initials !== '' ? $__initials : 'W';

$__pct    = fn($v) => rtrim(rtrim((string)(float)$v, '0'), '.');
$__pctVal = fn($v) => $__pct($v) !== '' ? $__pct($v) : '0';
$__mul    = fn($v) => number_format((float)$v, 1, '.', '');
$__isOtp  = fn(array $t) => in_array(strtoupper((string)($t['name'] ?? '')), ['VIP', 'DIAMOND'], true);
$__isDia  = fn(array $t) => strtoupper((string)($t['name'] ?? '')) === 'DIAMOND';

$__tierIcon = ['member' => 'bi-shield-check', 'vip' => 'bi-trophy', 'diamond' => 'bi-gem'];

$__displayTier   = $__cur['name'] ?? 'Thành viên';
$__displayHolder = $__fullName !== '' ? $__fullName : 'Quý khách';

// Miễn xác thực OTP (tôn trọng hạn hiệu lực hạng)
$__otpExempt = false;
if ($__cur && $__isOtp($__cur)) {
    $__exp = (string)($__cur['membership_expired'] ?? '');
    $__otpExempt = ($__exp === '' || $__exp >= date('Y-m-d'));
}

// Tóm tắt đặc quyền hiện tại cho hero lead
$__perks = [];
if ($__cur) {
    if ((float)$__cur['discount_percent'] > 0) $__perks[] = 'giảm ' . $__pct($__cur['discount_percent']) . '% mỗi đơn';
    $__perks[] = 'tích điểm x' . $__mul($__cur['points_multiplier']);
    if ($__otpExempt) $__perks[] = 'COD không cần OTP';
}

// Giá trị hiển thị trên thẻ thành viên
$__cardVal = [];
if ($__cur && (float)$__cur['discount_percent'] > 0) $__cardVal[] = $__pct($__cur['discount_percent']) . '% giảm giá';
$__cardVal[] = 'x' . $__mul((float)($__cur['points_multiplier'] ?? 1)) . ' điểm';
if ($__cur && $__otpExempt) $__cardVal[] = 'COD không OTP';
$__cardVal = implode(' · ', $__cardVal);

// Milestone / tiến trình
$__tierCount = count($tiers ?? []);
$__maxSpend  = $__tierCount > 0
    ? max(array_map(fn($t) => (float)$t['min_total_spent'], $tiers))
    : 0.0;
$__maxSpend  = max(1.0, $__maxSpend);
$__pctBar    = min(100.0, ($__spent / $__maxSpend) * 100);
$__isHighest = ($__nxt === null || (float)$__nxt['min_total_spent'] <= $__spent);
?>
<style>
/* ================================================================
   TRANG QUYỀN LỢI — theo bản tham khảo quyenloi.html
   Dùng token theme.css (--wc-*) + vàng đồng --wc-gold. KHÔNG hardcode.
   ================================================================ */
.rewards-hero{position:relative;text-align:center;padding:3.2rem 1rem 2.2rem;overflow:hidden}
.rewards-eyebrow{display:block;letter-spacing:.16em;font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--wc-gold-dark);margin-bottom:.9rem}
.rewards-hero h1{font-family:var(--wc-font-heading);font-weight:700;letter-spacing:-.02em;line-height:1.12;font-size:clamp(1.9rem,4vw,3rem);color:var(--wc-text);margin-bottom:1rem}
.rewards-lead{color:var(--wc-on-surface-variant);max-width:640px;margin-inline:auto;font-size:1.04rem;line-height:1.65}
.rewards-lead strong{color:var(--wc-text);font-weight:600}

/* Nút chuẩn trang */
.rw-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;border-radius:var(--wc-radius-pill);padding:.72rem 1.5rem;font-size:.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-decoration:none;transition:transform .2s ease,box-shadow .2s ease,background .2s ease,color .2s ease}
.rw-btn:hover{text-decoration:none}
.rw-btn-gold{background:var(--wc-gold);border:1px solid var(--wc-gold);color:var(--wc-wood-ebony);box-shadow:0 10px 22px var(--wc-gold-glow)}
.rw-btn-gold:hover{transform:translateY(-2px);background:var(--wc-gold-dark);color:var(--wc-wood-ebony)}
.rw-btn-outline{border:1px solid var(--wc-gold);color:var(--wc-gold-dark);background:transparent}
.rw-btn-outline:hover{background:var(--wc-gold);color:var(--wc-wood-ebony)}
.rw-btn-dark{border:1px solid var(--wc-wood-ebony);color:var(--wc-wood-ebony);background:transparent}
.rw-btn-dark:hover{background:var(--wc-wood-ebony);color:var(--wc-wood-oak-light)}

/* ================================================================
   THẺ THÀNH VIÊN CỦA BẠN (đã đăng nhập + có hạng)
   ================================================================ */
.member-card{position:relative;border-radius:var(--wc-radius-lg);color:var(--wc-wood-cream);padding:1.4rem 1.6rem;overflow:hidden;box-shadow:var(--wc-shadow);display:flex;flex-direction:column;gap:.9rem;transition:transform .3s ease}
.member-card:hover{transform:translateY(-3px)}
.member-card::before{content:"";position:absolute;inset:0;z-index:1;border-radius:inherit}
.member-card[data-wood="member"]{background:linear-gradient(135deg,var(--wc-wood-oak),var(--wc-wood-oak-dark))}
.member-card[data-wood="vip"]{background:linear-gradient(135deg,var(--wc-wood-walnut),var(--wc-wood-walnut-dark))}
.member-card[data-wood="diamond"]{background:linear-gradient(135deg,var(--wc-wood-ebony),var(--wc-wood-burgundy));border:1px solid var(--wc-gold)}
.member-card[data-wood="member"]::before{background-image:repeating-linear-gradient(100deg,rgba(0,0,0,.08) 0 1px,transparent 1px 26px),repeating-linear-gradient(70deg,rgba(255,255,255,.1) 0 2px,transparent 2px 30px),radial-gradient(circle at 80% 10%,rgba(255,255,255,.18),transparent 55%)}
.member-card[data-wood="vip"]::before{background-image:repeating-linear-gradient(100deg,rgba(0,0,0,.16) 0 2px,transparent 2px 22px),repeating-linear-gradient(70deg,rgba(255,255,255,.09) 0 1px,transparent 1px 26px),radial-gradient(circle at 82% 12%,rgba(255,255,255,.16),transparent 55%)}
.member-card[data-wood="diamond"]::before{background-image:repeating-linear-gradient(100deg,rgba(255,255,255,.05) 0 1px,transparent 1px 24px),repeating-linear-gradient(70deg,rgba(0,0,0,.18) 0 2px,transparent 2px 20px),radial-gradient(circle at 85% 8%,rgba(255,255,255,.1),transparent 55%)}
.member-card>*{position:relative;z-index:3}
.member-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap}
.member-id{display:flex;align-items:center;gap:.7rem}
.member-avatar{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--wc-font-heading);font-weight:600;font-size:1.05rem;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.38)}
.member-label{letter-spacing:.16em;text-transform:uppercase;font-size:.55rem;opacity:.75;margin-bottom:.25rem}
.member-name{font-style:normal;font-weight:500;letter-spacing:.09em;text-transform:uppercase;font-size:.95rem;opacity:.96}
.member-tier{text-align:right}
.member-tier-name{font-family:var(--wc-font-heading);font-weight:500;letter-spacing:.12em;text-transform:uppercase;font-size:1.2rem;display:flex;align-items:center;gap:.4rem;justify-content:flex-end}
.member-card[data-wood="diamond"] .member-tier-name{color:var(--wc-gold-light)}
.member-tier-name .icon{font-size:.95rem;opacity:.95}
.member-stats{display:flex;gap:1.8rem;font-size:.84rem;opacity:.95}
.member-stat{display:flex;flex-direction:column}
.member-stat small{font-size:.58rem;letter-spacing:.12em;text-transform:uppercase;opacity:.72}
.member-stat strong{font-size:1.05rem}
.member-hr{height:1px;background:rgba(255,255,255,.18);border:0;margin:0;opacity:.6}
.member-meta{font-size:.84rem;opacity:.95}

/* Milestone */
.member-milestone{position:relative;padding-top:24px}
.member-bar{height:4px;border-radius:99px;background:rgba(255,255,255,.2);position:relative}
.member-fill{position:absolute;left:0;top:0;height:100%;border-radius:99px;background:var(--wc-gold);transition:width .8s cubic-bezier(.22,1,.36,1)}
.member-stop{position:absolute;top:0;display:flex;flex-direction:column;align-items:center;transform:translateX(-50%)}
.member-dot{width:12px;height:12px;border-radius:50%;background:rgba(255,255,255,.25);border:2px solid rgba(255,255,255,.35);position:relative;z-index:2;margin-bottom:6px;transition:all .3s ease}
.member-dot.reached{border-color:var(--wc-gold);background:var(--wc-gold)}
.member-dot.current{border-color:#fff;background:var(--wc-gold);box-shadow:0 0 0 4px rgba(255,255,255,.25)}
.member-lbl{text-align:center;font-size:.55rem;line-height:1.3;color:rgba(255,255,255,.75);white-space:nowrap}
.member-lbl strong{display:block;color:rgba(255,255,255,.94);font-weight:600;font-size:.66rem}
.member-hint{font-size:.78rem;color:rgba(255,255,255,.92);margin-top:.5rem;display:flex;align-items:center;gap:.4rem}
.member-hint strong{font-weight:600}

/* ================================================================
   THẺ HẠNG THÀNH VIÊN (render từ $tiers)
   ================================================================ */
.rw-section-head{text-align:center;max-width:620px;margin-inline:auto;margin-bottom:2.4rem}
.rw-section-head .eyebrow{display:block;letter-spacing:.16em;font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--wc-gold-dark);margin-bottom:.6rem}
.rw-section-head h2{font-family:var(--wc-font-heading);font-weight:700;letter-spacing:-.015em;color:var(--wc-text);margin-bottom:.6rem}
.rw-section-head p{color:var(--wc-on-surface-variant);font-size:.95rem;margin:0}

.tier-grid{display:grid;grid-template-columns:1fr;gap:1.5rem;align-items:stretch}
@media(min-width:992px){.tier-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:992px){.tier-card--premium{margin-top:-.5rem}}

.tier-card{position:relative;display:flex;flex-direction:column;justify-content:space-between;background:var(--wc-surface);border:1px solid var(--wc-border);border-radius:var(--wc-radius-lg);padding:2rem 1.8rem;box-shadow:var(--wc-shadow);transition:transform .25s ease,box-shadow .25s ease,background .25s ease;color:var(--wc-text)}
.tier-card:hover{transform:translateY(-4px);box-shadow:0 18px 42px -14px rgba(45,36,30,.16)}
.tier-card.is-current{border-color:var(--wc-gold);box-shadow:0 0 0 1px var(--wc-gold),var(--wc-shadow)}
.tier-badge-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.3rem}
.tier-rank{font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;background:var(--wc-gold-glow);color:var(--wc-gold-dark);border-radius:var(--wc-radius-pill);padding:.32rem .8rem}
.tier-icon{font-size:1.9rem;color:var(--wc-secondary)}
.tier-card--premium .tier-rank{background:color-mix(in srgb,var(--wc-gold) 22%,transparent);border:1px solid color-mix(in srgb,var(--wc-gold) 40%,transparent);color:var(--wc-gold-light)}
.member-card[data-wood="vip"] .member-tier-name .icon{margin-right:0}
.tier-icon.tier-icon-vip{color:var(--wc-gold-dark)}
.tier-icon.tier-icon-dia{color:var(--wc-gold-light)}
.tier-name{font-family:var(--wc-font-heading);font-weight:700;font-size:1.5rem;letter-spacing:-.01em;color:var(--wc-text);margin-bottom:.2rem}
.tier-tagline{font-size:.86rem;color:var(--wc-on-surface-variant);margin-bottom:1.4rem}
.tier-condition{border-top:1px solid var(--wc-border);border-bottom:1px solid var(--wc-border);padding:1rem 0;margin-bottom:1.4rem}
.tier-condition .lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--wc-muted);display:block;margin-bottom:.25rem}
.tier-condition .val{font-size:1.3rem;font-weight:700;color:var(--wc-text)}
.tier-condition .val small{font-size:.78rem;font-weight:400;color:var(--wc-muted)}
.tier-benefits{list-style:none;padding:0;margin:0 0 1.6rem;display:grid;gap:.95rem}
.tier-benefits li{display:flex;align-items:center;gap:.65rem;font-size:.9rem;color:var(--wc-on-surface-variant);line-height:1.35}
.tier-benefits li strong{color:var(--wc-text)}
.tier-benefits .b-check{flex-shrink:0;width:24px;height:24px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;color:var(--wc-gold-dark);background:var(--wc-gold-glow)}
.tier-benefits .b-no{flex-shrink:0;width:24px;height:24px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;color:var(--wc-outline);opacity:.5}
.tier-benefits li.is-off{color:var(--wc-muted)}
.tier-benefits li.is-off span{text-decoration:line-through;color:var(--wc-muted)}
.tier-cta{display:flex;flex-direction:column;gap:.4rem}
.tier-cta .rw-btn{width:100%}
.tier-current-pill{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;width:100%;border-radius:var(--wc-radius-pill);padding:.72rem 1.5rem;font-size:.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;background:var(--wc-gold);color:var(--wc-wood-ebony)}

/* Thẻ premium (Diamond / cao nhất) */
.tier-card--premium{background:linear-gradient(135deg,var(--wc-wood-ebony),var(--wc-wood-walnut));border-color:var(--wc-gold);color:var(--wc-wood-oak-light);box-shadow:0 18px 44px -14px rgba(40,22,8,.35)}
.tier-card--premium:hover{box-shadow:0 22px 50px -14px rgba(40,22,8,.45)}
.tier-card--premium .tier-name{color:var(--wc-gold-light)}
.tier-card--premium .tier-tagline{color:var(--wc-wood-oak-light);opacity:.9}
.tier-card--premium .tier-condition{border-color:color-mix(in srgb,var(--wc-gold) 22%,transparent)}
.tier-card--premium .tier-condition .lbl{color:var(--wc-wood-oak-light);opacity:.75}
.tier-card--premium .tier-condition .val{color:var(--wc-gold-light)}
.tier-card--premium .tier-benefits li{color:var(--wc-wood-oak-light)}
.tier-card--premium .tier-benefits li strong{color:#fff}
.tier-card--premium .tier-benefits .b-check{color:var(--wc-wood-ebony);background:var(--wc-gold-light)}
.tier-best{position:absolute;top:-.8rem;right:1.4rem;background:var(--wc-gold);color:var(--wc-wood-ebony);font-size:.64rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;border-radius:var(--wc-radius-pill);padding:.35rem .9rem;box-shadow:0 6px 14px -6px var(--wc-gold-glow);display:inline-flex;align-items:center;gap:.35rem}

/* ================================================================
   VÙNG KHÁCH CHƯA ĐĂNG NHẬP
   ================================================================ */
.rw-guest{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;background:color-mix(in srgb,var(--wc-gold) 8%,var(--wc-surface));border:1px solid color-mix(in srgb,var(--wc-gold) 36%,var(--wc-border));border-radius:var(--wc-radius-lg);padding:1.25rem 1.5rem;color:var(--wc-text)}
.rw-guest p{margin:0;color:var(--wc-on-surface-variant);font-size:.95rem}
.rw-guest strong{color:var(--wc-text);font-weight:600}

/* ================================================================
   QUY TẮC TÍNH GIÁ & DỊCH VỤ CAM KẾT
   ================================================================ */
.rw-panel{background:var(--wc-surface);border:1px solid var(--wc-border);border-radius:var(--wc-radius-lg);padding:1.6rem 1.5rem;box-shadow:var(--wc-shadow);height:100%}
.rw-panel-head{display:flex;align-items:center;gap:.8rem;margin-bottom:1.3rem}
.rw-panel-icon{width:42px;height:42px;border-radius:12px;background:var(--wc-surface-low);color:var(--wc-gold-dark);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.rw-panel-icon .icon{width:22px;height:22px}
.rw-panel h3{font-family:var(--wc-font-heading);font-weight:700;font-size:1.05rem;color:var(--wc-text);margin-bottom:.15rem}
.rw-panel .sub{font-size:.78rem;color:var(--wc-muted)}
.rw-table{width:100%;font-size:.88rem;border-collapse:collapse}
.rw-table thead th{background:var(--wc-surface-low);color:var(--wc-text);font-size:.66rem;text-transform:uppercase;letter-spacing:.06em;padding:.7rem .85rem;text-align:left}
.rw-table thead th:nth-child(3){text-align:right}
.rw-table tbody td{padding:.78rem .85rem;border-bottom:1px solid var(--wc-border);color:var(--wc-on-surface-variant);vertical-align:middle}
.rw-table tbody td:first-child{font-weight:700;color:var(--wc-text)}
.rw-table tbody td:nth-child(3){text-align:right}
.rw-ship-special{color:var(--wc-gold-dark);font-weight:600;background:var(--wc-gold-glow);border-radius:6px;padding:.15rem .45rem;white-space:nowrap}
.rw-svc{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:576px){.rw-svc{grid-template-columns:1fr 1fr}}
.rw-svc-card{background:var(--wc-surface);border:1px solid var(--wc-border);border-radius:var(--wc-radius);padding:1.25rem 1.15rem;display:flex;align-items:flex-start;gap:.85rem;box-shadow:var(--wc-shadow);transition:transform .2s ease,border-color .2s ease;color:var(--wc-text)}
.rw-svc-card:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--wc-gold) 50%,var(--wc-border))}
.rw-svc-icon{width:44px;height:44px;border-radius:12px;background:var(--wc-surface-low);color:var(--wc-gold-dark);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.rw-svc-icon .icon{width:22px;height:22px}
.rw-svc-card h4{font-family:var(--wc-font-heading);font-weight:700;font-size:.9rem;color:var(--wc-text);margin-bottom:.3rem}
.rw-svc-card p{font-size:.8rem;color:var(--wc-on-surface-variant);line-height:1.5;margin:0}

/* ================================================================
   SCROLL REVEAL + REDUCED MOTION
   ================================================================ */
.reveal{opacity:0;transform:translateY(16px);transition:opacity .55s ease,transform .55s ease}
.reveal.is-in{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){
  .reveal,.tier-card,.member-card,.rw-svc-card{transition:none!important;transform:none!important}
  .member-fill{transition:none!important}
}
</style>

<div class="container py-4">

    <!-- Breadcrumb -->
    <nav class="app-crumb" aria-label="Điều hướng">
        <a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span class="cur">Quyền lợi thành viên</span>
    </nav>

    <!-- ============================================================
         HERO
         ============================================================ -->
    <section class="rewards-hero" aria-label="Giới thiệu quyền lợi thành viên">
        <div class="reveal">
            <span class="rewards-eyebrow">WoodCon Membership</span>
            <?php if ($__user && $__cur): ?>
                <h1>Hành trình đặc quyền của <?= e($__displayHolder) ?></h1>
                <p class="rewards-lead mb-4">
                    Bạn đang ở hạng <strong><?= e($__displayTier) ?></strong> — <?= e(implode(' · ', $__perks !== [] ? $__perks : ['tích điểm x' . $__mul(1.0)])) ?>.
                    Tích lũy chi tiêu để mở khóa đặc quyền cao hơn.
                </p>
            <?php else: ?>
                <h1>Hành Trình Đặc Quyền Thành Viên</h1>
                <p class="rewards-lead mb-4">
                    Tích lũy chi tiêu để mở khóa các đặc quyền cao cấp: từ chiết khấu trực tiếp,
                    nhân đôi điểm thưởng đến hỗ trợ giao hàng tận nơi.
                </p>
            <?php endif; ?>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="rw-btn rw-btn-gold" href="#thanh-vien"><?= icon('bi-arrow-down-circle') ?> Khám phá ưu đãi</a>
                <?php if ($__user): ?>
                    <a class="rw-btn rw-btn-outline" href="<?= BASE_URL ?>/tai-khoan/"><?= icon('bi-clock-history') ?> Lịch sử điểm</a>
                <?php else: ?>
                    <a class="rw-btn rw-btn-outline" href="<?= BASE_URL ?>/dang-ky"><?= icon('bi-person-plus') ?> Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         THẺ THÀNH VIÊN CỦA BẠN (chỉ khi đã đăng nhập + có hạng)
         ============================================================ -->
    <?php if ($__user && $__cur): ?>
    <section class="mb-5 reveal" aria-label="Thẻ thành viên của bạn">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="member-card" data-wood="<?= e($__acc) ?>" role="img" aria-label="Thẻ thành viên hạng <?= e($__displayTier) ?>">
                    <div class="member-top">
                        <div class="member-id">
                            <span class="member-avatar" aria-hidden="true"><?= e($__initials) ?></span>
                            <div>
                                <div class="member-label">Chủ thẻ</div>
                                <div class="member-name"><?= e($__displayHolder) ?></div>
                            </div>
                        </div>
                        <div class="member-tier">
                            <div class="member-label">Hạng thành viên</div>
                            <div class="member-tier-name"><?= icon($__tierIcon[$__acc] ?? 'bi-stars') ?><?= e($__displayTier) ?></div>
                        </div>
                    </div>
                    <div class="member-stats">
                        <div class="member-stat">
                            <small>Điểm tích lũy</small>
                            <strong><?= number_format((int)($__user['points'] ?? 0)) ?> <span style="font-size:.78rem;font-weight:400">điểm</span></strong>
                        </div>
                        <div class="member-stat">
                            <small>Tổng chi tiêu</small>
                            <strong><?= format_money((int)$__spent) ?></strong>
                        </div>
                    </div>
                    <hr class="member-hr" aria-hidden="true">
                    <div class="member-meta"><?= e($__cardVal) ?></div>
                    <div class="member-milestone" role="group" aria-label="Tiến trình nâng hạng">
                        <div class="member-bar">
                            <div class="member-fill" style="width:<?= round($__pctBar, 1) ?>%"
                                 role="progressbar" aria-valuenow="<?= (int)$__pctBar ?>" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="Đã tích lũy <?= number_format((int)$__spent) ?> đồng"></div>
                            <?php foreach (($tiers ?? []) as $_t):
                                $_pos = max(0, min(100, ((float)$_t['min_total_spent'] / $__maxSpend) * 100));
                                $_reached = $__spent >= (float)$_t['min_total_spent'];
                                $_curMt   = $__curId !== null && (int)$_t['id'] === $__curId;
                            ?>
                            <div class="member-stop" style="left:<?= $_pos ?>%">
                                <span class="member-dot<?= $_reached ? ' reached' : '' ?><?= $_curMt ? ' current' : '' ?>"></span>
                                <span class="member-lbl"><strong><?= e($_t['name']) ?></strong><?= format_money((int)$_t['min_total_spent']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="member-hint">
                            <?php if ($__isHighest): ?>
                                <?= icon('bi-star-fill') ?> Bạn đã đạt hạng cao nhất.
                            <?php elseif ($__nxt): ?>
                                <?= icon('bi-arrow-repeat') ?> Lên <strong><?= e($__nxt['name'] ?? '') ?></strong> · còn <strong><?= format_money((int)max(0, (float)$__nxt['min_total_spent'] - $__spent)) ?></strong>
                            <?php else: ?>
                                <?= icon('bi-star-fill') ?> Đăng ký để bắt đầu tích điểm và nâng hạng.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         THẺ HẠNG THÀNH VIÊN (theo quyenloi.html, render từ $tiers)
         ============================================================ -->
    <section id="thanh-vien" class="mb-5" aria-label="Các hạng thành viên">
        <div class="rw-section-head reveal">
            <span class="eyebrow">WoodCon Membership</span>
            <h2>Các hạng thành viên</h2>
            <p>Càng tích lũy chi tiêu, bạn càng nhận được nhiều đặc quyền — từ chiết khấu trực tiếp đến dịch vụ chăm sóc riêng.</p>
        </div>

        <?php if (empty($tiers)): ?>
            <div class="app-empty"><?= icon('bi-inbox', 'mb-3') ?>Chưa có hạng thành viên nào.</div>
        <?php else: ?>
        <div class="tier-grid">
            <?php foreach ($tiers as $__index => $_t):
                $__tName  = (string)($_t['name'] ?? '');
                $__tUpper = strtoupper($__tName);
                $__tIsTop = $__index === $__tierCount - 1;
                $__tDia   = $__tUpper === 'DIAMOND';
                $__tOtp   = in_array($__tUpper, ['VIP', 'DIAMOND'], true);
                $__tDisc  = (float)($_t['discount_percent'] ?? 0);
                $__tMult  = $__mul($_t['points_multiplier'] ?? 1);
                $__tScore = (float)$_t['min_total_spent'];
                $__tIsCur = $__curId !== null && (int)$_t['id'] === $__curId;
                $__tIcon  = $__tDia ? 'bi-gem' : ($__tUpper === 'VIP' ? 'bi-trophy' : 'bi-shield-check');
                $__tTag   = $__index === 0 ? 'Khởi đầu trải nghiệm' : ($__tIsTop ? 'Đặc quyền tối thượng' : 'Ưu đãi vượt trội');
            ?>
            <article class="tier-card<?= $__tIsTop ? ' tier-card--premium' : '' ?><?= $__tIsCur ? ' is-current' : '' ?> reveal" style="transition-delay:<?= $__index * .07 ?>s">
                <?php if ($__tIsTop): ?>
                    <span class="tier-best"><?= icon('bi-star-fill') ?> Tốt nhất</span>
                <?php endif; ?>
                <div>
                    <div class="tier-badge-row">
                        <span class="tier-rank">Hạng <?= str_pad((string)($__index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <?= icon($__tIcon, $__tDia ? 'tier-icon tier-icon-dia' : ($__tUpper === 'VIP' ? 'tier-icon tier-icon-vip' : 'tier-icon')) ?>
                    </div>
                    <h3 class="tier-name"><?= e($__tName) ?></h3>
                    <p class="tier-tagline"><?= e($__tTag) ?></p>
                    <div class="tier-condition">
                        <span class="lbl">Điều kiện tích lũy</span>
                        <?php if ($__tScore <= 0): ?>
                            <span class="val">0 đ <small>(Mới đăng ký)</small></span>
                        <?php else: ?>
                            <span class="val">Từ <?= format_money((int)$__tScore) ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="tier-benefits">
                        <li><span class="b-check"><?= icon('bi-check-circle-fill') ?></span>Giảm giá đơn hàng: <strong><?= $__tDisc > 0 ? $__pctVal($__tDisc) . '%' : '0%' ?></strong></li>
                        <li><span class="b-check"><?= icon('bi-check-circle-fill') ?></span>Hệ số tích điểm: <strong>x<?= $__tMult ?></strong></li>
                        <?php if ($__tOtp): ?>
                            <li><span class="b-check"><?= icon('bi-check-circle-fill') ?></span>Đặt COD <strong>không cần OTP</strong></li>
                        <?php else: ?>
                            <li class="is-off"><span class="b-no"><?= icon('bi-dash-lg') ?></span><span>Đặt COD không cần OTP</span></li>
                        <?php endif; ?>
                        <?php if ($__tDia): ?>
                            <li><span class="b-check"><?= icon('bi-check-circle-fill') ?></span>Ưu tiên xử lý đơn &amp; Trợ lý riêng</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="tier-cta">
                    <?php if ($__tIsCur): ?>
                        <span class="tier-current-pill"><?= icon('bi-check-lg') ?> Hạng hiện tại</span>
                    <?php elseif ($__index === 0): ?>
                        <a class="rw-btn rw-btn-dark" href="<?= $__user ? BASE_URL . '/tai-khoan/' : BASE_URL . '/dang-ky' ?>"><?= icon('bi-check-lg') ?> Kích hoạt hạng</a>
                    <?php else: ?>
                        <a class="rw-btn <?= $__tIsTop ? 'rw-btn-gold' : 'rw-btn-dark' ?>" href="<?= $__user ? BASE_URL . '/tai-khoan/' : BASE_URL . '/dang-ky' ?>"><?= $__tIsTop ? 'Khám phá ' : 'Nâng hạng ' ?><?= e($__tName) ?></a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
         VÙNG KHÁCH CHƯA ĐĂNG NHẬP
         ============================================================ -->
    <?php if (!$__user): ?>
    <section class="rw-guest mb-5 reveal" aria-label="Đăng ký để nhận đặc quyền theo hạng">
        <p class="mb-0"><strong>Bạn chưa có tài khoản?</strong> Đăng ký để tích lũy điểm và nhận đặc quyền theo từng hạng.</p>
        <div class="d-flex gap-2 flex-shrink-0">
            <a class="rw-btn rw-btn-gold" href="<?= BASE_URL ?>/dang-nhap">Đăng nhập</a>
            <a class="rw-btn rw-btn-outline" href="<?= BASE_URL ?>/dang-ky">Đăng ký</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================================
         QUY TẮC TÍNH GIÁ & DỊCH VỤ CAM KẾT
         ============================================================ -->
    <section class="mb-5" aria-label="Quy tắc tính giá và dịch vụ cam kết">
        <div class="rw-section-head reveal">
            <span class="eyebrow">WoodCon Commitment</span>
            <h2>Quy Tắc Tính Giá &amp; Dịch Vụ Cam Kết</h2>
            <p>WoodCon cam kết minh bạch mọi chi phí — giá cuối cùng luôn rõ ràng trước khi thanh toán.</p>
        </div>

        <div class="row g-4">
            <!-- Cột trái: Phí vận chuyển theo khoảng cách -->
            <div class="col-lg-5">
                <div class="rw-panel reveal">
                    <div class="rw-panel-head">
                        <div class="rw-panel-icon"><?= icon('bi-truck') ?></div>
                        <div>
                            <h3>Phí vận chuyển theo khoảng cách</h3>
                            <div class="sub">Khoảng cách tính từ kho WoodCon đến địa chỉ nhận</div>
                        </div>
                    </div>
                    <div class="rw-table-wrap">
                        <table class="rw-table">
                            <thead>
                                <tr><th scope="col">Mức</th><th scope="col">Khoảng cách</th><th scope="col">Phí dịch vụ</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>1</td><td>0 – 30 km</td><td><span class="rw-ship-special">Thấp nhất (Ưu đãi)</span></td></tr>
                                <tr><td>2</td><td>31 – 100 km</td><td>Tiêu chuẩn</td></tr>
                                <tr><td>3</td><td>101 – 300 km</td><td class="rw-ship-text">Nâng cao</td></tr>
                                <tr><td>4</td><td>Trên 300 km</td><td>Theo liên hệ</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cột phải: 6 thẻ quyền lợi dịch vụ -->
            <div class="col-lg-7">
                <div class="rw-svc">
                    <div class="rw-svc-card reveal">
                        <div class="rw-svc-icon"><?= icon('bi-receipt') ?></div>
                        <div><h4>Giá đã bao gồm VAT</h4><p>Mọi sản phẩm hiển thị giá cuối cùng đã bao gồm thuế VAT đầy đủ.</p></div>
                    </div>
                    <div class="rw-svc-card reveal" style="transition-delay:.05s">
                        <div class="rw-svc-icon"><?= icon('bi-truck') ?></div>
                        <div><h4>Miễn phí vận chuyển nội tỉnh</h4><p>Áp dụng đơn giao trong TP.HCM và các khu vực hỗ trợ theo quy định.</p></div>
                    </div>
                    <div class="rw-svc-card reveal" style="transition-delay:.1s">
                        <div class="rw-svc-icon"><?= icon('bi-tools') ?></div>
                        <div><h4>Phí lắp đặt tận nơi</h4><p>Tính theo sản phẩm &amp; khu vực; miễn phí hoàn toàn cho đơn đủ điều kiện.</p></div>
                    </div>
                    <div class="rw-svc-card reveal" style="transition-delay:.15s">
                        <div class="rw-svc-icon"><?= icon('bi-ticket') ?></div>
                        <div><h4>Áp dụng mã giảm giá</h4><p>Nhập voucher ở bước thanh toán để nhận thêm ưu đãi cộng dồn.</p></div>
                    </div>
                    <div class="rw-svc-card reveal" style="transition-delay:.2s">
                        <div class="rw-svc-icon"><?= icon('bi-cash-coin') ?></div>
                        <div><h4>Tích điểm thành viên</h4><p>Mỗi đơn giao thành công tích điểm theo hệ số hạng (x1.0 – x2.0) dùng để đổi quà.</p></div>
                    </div>
                    <div class="rw-svc-card reveal" style="transition-delay:.25s">
                        <div class="rw-svc-icon"><?= icon('bi-shield-check') ?></div>
                        <div><h4>Xác minh COD linh hoạt</h4><p>Đơn COD được bảo mật. Hạng VIP &amp; Diamond được miễn xác thực OTP.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div><!-- /.container -->

<script>
(function () {
    /* Scroll reveal — tôn trọng prefers-reduced-motion */
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-in'); });
        return;
    }
    var els = document.querySelectorAll('.reveal');
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) {
        for (var i = 0; i < els.length; i++) els[i].classList.add('is-in');
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i].isIntersecting) { entries[i].target.classList.add('is-in'); io.unobserve(entries[i].target); }
        }
    }, { threshold: 0.12 });
    for (var j = 0; j < els.length; j++) io.observe(els[j]);
})();
</script>