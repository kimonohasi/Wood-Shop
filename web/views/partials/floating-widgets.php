<?php
/**
 * WoodCon - Floating Widgets (Pill button + Chat AI + Liên hệ hỗ trợ)
 * Include truoc </body> trong footer.php.
 */

declare(strict_types=1);

function _wc_abs_url(string $raw, string $prefix): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $raw)) {
        return $raw;
    }
    if (str_starts_with(strtolower($raw), strtolower($prefix))) {
        return 'https://' . ltrim($raw, '/');
    }
    return 'https://' . trim($prefix, '/') . '/' . ltrim($raw, '/');
}

$_fw_phone = e(get_setting('site_phone', '1900 8686'));
$_fw_zalo  = e(_wc_abs_url((string)get_setting('site_zalo', ''), 'zalo.me'));
$_fw_fb    = e(_wc_abs_url((string)get_setting('site_facebook', ''), 'facebook.com'));
$_fw_email = e(get_setting('shop_email', 'hello@woodcon.vn'));
?>

<!-- ============================================================
     Pill Floating Button
     ============================================================ -->
<div class="wc-pill-fab" id="pillFab">
    <button type="button" class="wc-pill-item" id="pillAssistant" title="Trợ lý AI">
        <?= icon('ms-auto_awesome') ?>
        <span class="wc-pill-label">Trợ lý</span>
    </button>
    <div class="wc-pill-divider" aria-hidden="true"></div>
    <button type="button" class="wc-pill-item" id="pillContact" title="Liên hệ hỗ trợ">
        <?= icon('ms-headset_mic') ?>
        <span class="wc-pill-label">Liên hệ</span>
    </button>
</div>

<!-- ============================================================
     Panel: Trợ lý AI
     ============================================================ -->
<div class="wc-panel wc-chat-panel d-none" id="chatPanel">
    <div class="wc-panel-header">
        <?= icon('ms-auto_awesome') ?>
        <div>
            <div class="wc-panel-title">WoodCon hỗ trợ</div>
            <div class="wc-panel-subtitle">Trực tuyến - phản hồi trong vài phút</div>
        </div>
        <button type="button" class="wc-panel-close" id="chatClose" title="Đóng">
            <?= icon('ms-close') ?>
        </button>
    </div>

    <div class="wc-chat-messages" id="chatMessages">
        <div class="wc-chat-msg wc-chat-msg-bot">
            <div class="wc-chat-avatar-bot">
                <?= icon('ms-auto_awesome') ?>
            </div>
            <div class="wc-chat-bubble" id="chatWelcomeMsg">Xin chào! Mình có thể giúp gì cho bạn?</div>
        </div>

        <div class="wc-chat-sugg" id="chatSuggestions">
            <span class="wc-chat-sugg-label">Gợi ý nhanh:</span>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="wc-chip" data-topic="Hỗ trợ mua hàng - gợi ý sản phẩm nội thất">Tư vấn sản phẩm</button>
                <button type="button" class="wc-chip" data-topic="Hướng dẫn thanh toán (COD, chuyển khoản)">Hướng dẫn thanh toán</button>
                <button type="button" class="wc-chip" data-topic="Hướng dẫn liên hệ tư vấn viên khi gặp sự cố">Liên hệ CSKH</button>
            </div>
        </div>
    </div>

    <form class="wc-chat-input" id="chatForm" autocomplete="off">
        <input type="text" class="wc-chat-textbox" id="chatInput" placeholder="Nhập tin nhắn..." maxlength="500" required>
        <button type="submit" class="wc-chat-send" id="chatSend" title="Gửi">
            <?= icon('ms-send') ?>
        </button>
    </form>
</div>

<!-- ============================================================
     Panel: Liên hệ hỗ trợ
     ============================================================ -->
<div class="wc-panel wc-contact-panel d-none" id="contactPanel">
    <div class="wc-panel-header">
        <?= icon('ms-headset_mic') ?>
        <div>
            <div class="wc-panel-title">Liên hệ hỗ trợ</div>
            <div class="wc-panel-subtitle">Chúng tôi luôn sẵn sàng giúp bạn</div>
        </div>
        <button type="button" class="wc-panel-close" id="contactClose" title="Đóng">
            <?= icon('ms-close') ?>
        </button>
    </div>

    <div class="wc-contact-body">
        <div class="wc-contact-status">
            <span class="wc-contact-badge">Đang trực tuyến</span>
        </div>

        <div class="wc-contact-channels">
            <a class="wc-contact-channel wc-contact-channel-phone" href="tel:<?= $_fw_phone ?>" aria-label="Gọi điện">
                <span class="wc-contact-icon-box">
                    <?= icon('ms-call') ?>
                </span>
                <span class="wc-contact-channel-text">Gọi điện</span>
                <?= icon('ms-arrow_forward', 'wc-contact-arrow') ?>
            </a>
            <a class="wc-contact-channel wc-contact-channel-zalo" href="<?= $_fw_zalo ?>" target="_blank" rel="noopener" aria-label="Zalo">
                <span class="wc-contact-icon-box">
                    <?= icon('ms-chat') ?>
                </span>
                <span class="wc-contact-channel-text">Zalo</span>
                <?= icon('ms-arrow_forward', 'wc-contact-arrow') ?>
            </a>
            <a class="wc-contact-channel wc-contact-channel-messenger" href="<?= $_fw_fb ?>" target="_blank" rel="noopener" aria-label="Messenger">
                <span class="wc-contact-icon-box">
                    <?= icon('ms-question_answer') ?>
                </span>
                <span class="wc-contact-channel-text">Messenger</span>
                <?= icon('ms-arrow_forward', 'wc-contact-arrow') ?>
            </a>
        </div>

        <div class="wc-contact-footer">
            <small>Hotline: <a href="tel:<?= $_fw_phone ?>"><?= $_fw_phone ?></a></small>
            <small>Email: <a href="mailto:<?= $_fw_email ?>"><?= $_fw_email ?></a></small>
        </div>
    </div>
</div>
