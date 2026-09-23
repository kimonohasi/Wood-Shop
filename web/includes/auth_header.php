<?php
/**
 * WoodCon - AUTH HEADER tối giản (chỉ logo, không nav/cart/tìm kiếm)
 * Dùng RIÊNG cho nhóm trang xác thực qua BaseController::renderAuth().
 * Giữ đúng font + token --wc-*; bỏ dark toggle + floating widgets để tối giản tối đa.
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'WoodCon - Nội thất gỗ cao cấp') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'WoodCon - Đăng nhập, đăng ký và khôi phục tài khoản thành viên.') ?>">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/svg+xml">
    <script>window.WOODCON_BASE_URL = '<?= BASE_URL ?>';</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Work+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=15">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-modal.css?v=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
    <style>
        /* ============================================================
           AUTH LAYOUT tối giản + split-screen (chỉ dùng 3 trang xác thực)
           Không dùng --wc-* mới; chỉ tái sử dụng token có sẵn.
           ============================================================ */
        body.app-auth-layout {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: var(--wc-bg);
        }
        .auth-top {
            flex: 0 0 auto;
            height: 68px;
            display: flex;
            align-items: center;
            padding: 0 clamp(1rem, 4vw, 2.5rem);
            background: var(--wc-bg);
            border-bottom: 1px solid var(--wc-outline-variant);
        }
        .auth-top .app-brand-mark { width: 38px; height: 38px; }
        .auth-main {
            flex: 1 1 0;
            min-height: 0;
            display: flex;
        }
        .auth-split {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            grid-template-rows: 100%;
            width: 100%;
            height: 100%;
        }
        /* Panel ảnh thương hiệu (desktop >= lg / 992px) */
        .auth-visual {
            position: relative;
            overflow: hidden;
            background: var(--wc-primary);
        }
        .auth-visual img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .auth-visual::after {
            content: "";
            position: absolute; inset: 0;
            background: linear-gradient(to top,
                color-mix(in srgb, var(--wc-primary) 64%, transparent) 0%,
                color-mix(in srgb, var(--wc-primary) 22%, transparent) 34%,
                rgba(0, 0, 0, 0) 58%);
        }
        /* Panel form: giữ nguyên nội dung, căn giữa dọc trong panel */
        .auth-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 5vh, 3.5rem) clamp(1.25rem, 5vw, 4rem);
            background: var(--wc-bg);
        }
        .auth-form-inner { width: 100%; }
        .auth-footer {
            flex: 0 0 auto;
            padding: .8rem 1rem;
            text-align: center;
            font-size: .72rem;
            color: var(--wc-muted);
            background: var(--wc-bg);
            border-top: 1px solid var(--wc-outline-variant);
        }
        /* Dưới 992px: ẩn hoàn toàn panel ảnh, form chiếm toàn bộ (hành vi cũ) */
        @media (max-width: 991.98px) {
            .auth-split { grid-template-columns: minmax(0, 1fr); }
            .auth-visual { display: none; }
            .auth-form-panel { min-height: calc(100vh - 68px - 44px); }
        }
        @media (max-width: 575.98px) {
            .auth-top { height: 60px; }
            .auth-form-panel { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body class="app-auth-layout">

<header class="auth-top">
    <a class="app-brand" href="<?= BASE_URL ?>">
        <span class="app-brand-mark">W</span>
        <span class="app-brand-text d-none d-sm-inline">Wood<span>Con</span></span>
    </a>
</header>

<main class="auth-main">