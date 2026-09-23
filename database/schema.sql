-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 04:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schema`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','admin','employee') NOT NULL DEFAULT 'employee',
  `role_id` int(10) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `phone`, `password`, `avatar`, `role`, `role_id`, `status`, `is_deleted`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@woodcon.vn', '0900000000', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'superadmin', NULL, 1, 0, '2026-09-16 18:06:58', '2026-09-14 11:49:35', '2026-09-16 11:06:58'),
(2, 'Quản lý kho', 'kho@woodcon.vn', '0900000001', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'admin', 3, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:51:27'),
(3, 'Nhân viên bán hàng 1', 'nv1@woodcon.vn', '0900000002', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', NULL, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(4, 'Nhân viên bán hàng 2', 'nv2@woodcon.vn', '0900000003', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', NULL, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(5, 'Nhân viên CSKH', 'cskh@woodcon.vn', '0900000004', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', 4, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:51:28'),
(6, 'Kế toán', 'ketoan@woodcon.vn', '0900000005', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'admin', 2, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:51:27'),
(7, 'Nhân viên kho 2', 'kho2@woodcon.vn', '0900000006', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', 3, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:51:28'),
(8, 'Nhân viên bán hàng 3', 'nv3@woodcon.vn', '0900000007', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', NULL, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(9, 'Nhân viên giao hàng', 'shipper@woodcon.vn', '0900000008', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'employee', NULL, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(10, 'Trợ lý Admin', 'troly@woodcon.vn', '0900000009', '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'admin', NULL, 1, 0, NULL, '2026-09-14 11:49:35', '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `ai_error_log`
--

CREATE TABLE `ai_error_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `model` varchar(120) DEFAULT NULL COMMENT 'Model AI thất bại (nếu biết)',
  `loi` text NOT NULL COMMENT 'Mô tả lỗi (không chứa secret/API key)',
  `thoi_gian` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_name` varchar(120) DEFAULT NULL,
  `role_name` varchar(120) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, EXPORT, LOGIN, LOCK...',
  `module` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `role_name`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'Super Admin', 'superadmin', 'LOGIN', 'Auth', 'Đăng nhập hệ thống quản trị.', '::1', '2026-09-14 17:36:35'),
(2, 1, 'Super Admin', 'superadmin', 'LOGIN', 'Auth', 'Đăng nhập hệ thống quản trị.', '::1', '2026-09-15 07:12:21'),
(3, 1, 'Super Admin', 'superadmin', 'UPDATE', 'Settings', 'Cập nhật 3 cài đặt (nhóm: ai)', '::1', '2026-09-15 16:31:05'),
(4, 1, 'Super Admin', 'superadmin', 'LOGIN', 'Auth', 'Đăng nhập hệ thống quản trị.', '::1', '2026-09-16 11:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `position` enum('slider','banner','flash_sale') NOT NULL DEFAULT 'banner' COMMENT 'slider = slider chính, banner = banner trang trí, flash_sale = banner khuyến mãi',
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `interval_ms` int(10) UNSIGNED DEFAULT NULL COMMENT 'Tốc độ trượt (ms) - chỉ áp dụng cho position=slider',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `position`, `image`, `link`, `subtitle`, `sort_order`, `interval_ms`, `status`, `created_at`) VALUES
(1, 'Khuyến mãi sofa đến 30%', 'slider', 'assets/images/shop/sofa-dark.jpg', 'danh-muc/sofa-va-ghe', 'Bộ sưu tập mới nhất 2026', 1, NULL, 1, '2026-09-14 11:49:35'),
(2, 'Đồng giá đồ gỗ óc chó', 'slider', 'assets/images/shop/dining-wood.jpg', 'danh-muc/ban', 'Tinh tế cho không gian của bạn', 2, NULL, 1, '2026-09-14 11:49:35'),
(3, 'Ưu đãi ngày chủ nhật', 'slider', 'assets/images/shop/bed-modern.jpg', 'khuyen-mai', 'Giảm giá sốc cuối tuần', 3, NULL, 1, '2026-09-14 11:49:35'),
(4, 'Gỗ sồi chính hãng', 'banner', 'assets/images/shop/outdoor-1.jpg', 'danh-muc/giuong-va-tu', 'Chất liệu tuyển chọn rừng Bắc Âu', 1, NULL, 1, '2026-09-14 11:49:35'),
(5, 'Bố trí nội thất theo phong thủy', 'banner', 'assets/images/shop/vanity-deco.jpg', 'tin-tuc', 'Tư vấn miễn phí cùng chuyên gia', 2, NULL, 1, '2026-09-14 11:49:35'),
(6, 'Flash sale đèn bàn', 'flash_sale', 'assets/images/shop/desk-modern.jpg', 'danh-muc/den', 'Giảm tới 45%', 1, NULL, 1, '2026-09-14 11:49:35'),
(7, 'Flash sale bàn làm việc', 'flash_sale', 'assets/images/shop/lamp-1.jpg', 'danh-muc/ban-lam-viec', 'Săn deal mid-year', 2, NULL, 1, '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `status`, `created_at`) VALUES
(1, 'WoodCon Originals', 'woodcon-originals', NULL, 1, '2026-09-14 11:49:35'),
(2, 'Sồi & Mây', 'soi-va-may', NULL, 1, '2026-09-14 11:49:35'),
(3, 'Lục Giác', 'luc-giac', NULL, 1, '2026-09-14 11:49:35'),
(4, 'Nam Phương Craft', 'nam-phuong-craft', NULL, 1, '2026-09-14 11:49:35'),
(5, 'Bách Furniture', 'bach-furniture', NULL, 1, '2026-09-14 11:49:35'),
(6, 'Trúc Lâm', 'truc-lam', NULL, 1, '2026-09-14 11:49:35'),
(7, 'Thiên Mộc', 'thien-moc', NULL, 1, '2026-09-14 11:49:35'),
(8, 'Gỗ Phương Nam', 'go-phuong-nam', NULL, 1, '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `carrier_rates`
--

CREATE TABLE `carrier_rates` (
  `id` int(10) UNSIGNED NOT NULL,
  `zone` varchar(20) NOT NULL,
  `label` varchar(80) NOT NULL,
  `base_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Phí cơ bản cho đơn',
  `price_per_kg` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đơn giá theo kg tính cước',
  `free_ship_threshold` decimal(15,2) DEFAULT NULL COMMENT 'Ngưỡng miễn phí ship riêng cho vùng; NULL/0 = dùng ngưỡng chung'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carrier_rates`
--

INSERT INTO `carrier_rates` (`id`, `zone`, `label`, `base_fee`, `price_per_kg`, `free_ship_threshold`) VALUES
(1, 'noithanh', 'Nội thành (TP. HCM)', 20000.00, 1500.00, 3000000.00),
(2, 'lientinh', 'Liên tỉnh gần', 35000.00, 3500.00, 8000000.00),
(3, 'lienmien', 'Liên miền / xa', 55000.00, 6500.00, 18000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_snapshot` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `ship_supports_type1` tinyint(1) NOT NULL DEFAULT 1,
  `ship_supports_type2` tinyint(1) NOT NULL DEFAULT 1,
  `install_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image`, `description`, `sort_order`, `status`, `ship_supports_type1`, `ship_supports_type2`, `install_fee`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Sofa & Ghế', 'sofa-va-ghe', 'assets/images/shop/sofa-green.jpg', 'Sofa, ghế thư giãn cho phòng khách và phòng làm việc', 1, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(2, NULL, 'Bàn', 'ban', 'assets/images/shop/table-wood.jpg', 'Bàn trà, bàn ăn, bàn làm việc các chất liệu', 2, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(3, NULL, 'Giường & Tủ', 'giuong-va-tu', 'assets/images/shop/bed-king.jpg', 'Giường ngủ và tủ quần áo, tủ đầu giường', 3, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(4, NULL, 'Kệ & Lưu trữ', 'ke-va-luu-tru', 'assets/images/shop/shelf-console.jpg', 'Kệ tivi, kệ sách, tủ trang trí không gian sống', 4, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(5, NULL, 'Đèn & Trang trí', 'den-va-trang-tri', 'assets/images/shop/lamp-2.jpg', 'Đèn bàn, đèn sàn, gương, tranh trang trí', 5, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(6, NULL, 'Ngoài trời', 'ngoai-troi', 'assets/images/shop/outdoor-1.jpg', 'Bàn ghế sân vườn, ghế xếp gỗ', 6, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(7, 1, 'Sofa bộ', 'sofa-bo', 'assets/images/shop/sofa-dark.jpg', 'Sofa văng nhiều chỗ cho phòng khách', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(8, 1, 'Ghế bành & Ghế đơn', 'ghe-banh-ghe-don', 'assets/images/shop/armchair-navy.jpg', 'Ghế bành thư giãn, ghế đơn nổi bật', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(9, 1, 'Ghế làm việc', 'ghe-lam-viec', 'assets/images/shop/desk-office.jpg', 'Ghế văn phòng, ghế quay', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(10, 2, 'Bàn trà', 'ban-tra', 'assets/images/shop/table-2.jpg', 'Bàn trà phòng khách', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(11, 2, 'Bàn ăn', 'ban-an', 'assets/images/shop/dining-wood.jpg', 'Bàn ăn gia đình', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(12, 2, 'Bàn làm việc', 'ban-lam-viec', 'assets/images/shop/desk-modern.jpg', 'Bàn làm việc tại nhà', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(13, 3, 'Giường ngủ', 'giuong-ngu', 'assets/images/shop/bed-linen.jpg', 'Giường hộp, giường tầng', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(14, 3, 'Tủ quần áo', 'tu-quan-ao', 'assets/images/shop/wardrobe-2.jpg', 'Tủ quần áo nhiều cánh', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(15, 4, 'Kệ tivi', 'ke-tivi', 'assets/images/shop/shelf-wall.jpg', 'Kệ tivi phòng khách', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(16, 4, 'Kệ sách', 'ke-sach', 'assets/images/shop/shelf-2.jpg', 'Kệ sách đa năng', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(17, 5, 'Đèn', 'den', 'assets/images/shop/lamp-3.jpg', 'Đèn bàn, đèn sàn, đèn trần', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(18, 5, 'Gương & Phụ kiện', 'guong-phu-kien', 'assets/images/shop/mirror-brick.jpg', 'Gương trang trí, đồng hồ, bình hoa', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(19, 2, 'Bàn trang điểm', 'ban-trang-diem', 'assets/images/shop/vanity-deco.jpg', 'Bàn trang điểm có gương', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20'),
(20, 6, 'Bàn ghế sân vườn', 'ban-ghe-san-vuon', 'assets/images/shop/outdoor-3.jpg', 'Bộ bàn ghế ngoài trời', 0, 1, 1, 1, 0.00, '2026-09-14 11:49:35', '2026-09-14 12:06:20');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(64) NOT NULL COMMENT 'ID phiên hội thoại (client giữ)',
  `sender_type` enum('user','bot','staff') NOT NULL DEFAULT 'user',
  `message` text NOT NULL COMMENT 'Nội dung tin nhắn',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `sender_type`, `message`, `created_at`) VALUES
(1, 'cs_mu1ikr82_cw35dwdj', 'user', 'Hỗ trợ mua hàng - gợi ý sản phẩm nội thất', '2026-09-14 17:34:20'),
(2, 'cs_mu1ikr82_cw35dwdj', 'bot', 'Hệ thống đang bảo trì... Vui lòng thử lại sau.', '2026-09-14 17:34:20'),
(3, 'cs_mtwx8bnb_k06phaz9', 'user', 'Hỗ trợ mua hàng - gợi ý sản phẩm nội thất', '2026-09-14 17:36:18'),
(4, 'cs_mtwx8bnb_k06phaz9', 'bot', 'Hệ thống đang bảo trì... Vui lòng thử lại sau.', '2026-09-14 17:36:18'),
(5, 'cs_mtwx8bnb_k06phaz9', 'user', 'Hỗ trợ mua hàng - gợi ý sản phẩm nội thất', '2026-09-14 18:48:17'),
(6, 'cs_mtwx8bnb_k06phaz9', 'bot', 'Hệ thống đang bảo trì... Vui lòng thử lại sau.', '2026-09-14 18:48:17'),
(7, 'cs_mtwx8bnb_k06phaz9', 'user', 'Hỗ trợ mua hàng - gợi ý sản phẩm nội thất', '2026-09-15 16:31:29'),
(8, 'cs_mtwx8bnb_k06phaz9', 'bot', 'Dạ chào anh/chị, em rất sẵn lòng hỗ trợ mình chọn đồ nội thất ưng ý cho không gian nhà mình đây ạ.\n\nKhông biết anh/chị đang muốn tìm món đồ nào (như sofa, bàn ăn, hay giường ngủ...) để em lọc mấy mẫu đẹp và hợp túi tiền cho mình xem thử nhé?', '2026-09-15 16:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','hidden') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','answered','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_order_logs`
--

CREATE TABLE `deleted_order_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `snapshot_json` mediumtext NOT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL,
  `deleted_by_name` varchar(120) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goong_cache`
--

CREATE TABLE `goong_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `cache_key` varchar(255) NOT NULL COMMENT 'Khóa cache (input chuẩn hóa + location)',
  `payload` mediumtext NOT NULL COMMENT 'JSON kết quả cache',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache tạm kết quả Goong Maps (TTL ngắn, tránh trùng request)';

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_code` varchar(40) NOT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `type` enum('SALE_INVOICE','REFUND_INVOICE') NOT NULL DEFAULT 'SALE_INVOICE',
  `order_id` int(10) UNSIGNED NOT NULL,
  `original_invoice_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'REFUND_INVOICE trỏ về hóa đơn bán gốc',
  `customer_name` varchar(120) DEFAULT '',
  `customer_phone` varchar(20) DEFAULT '',
  `customer_email` varchar(150) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `invoice_date` datetime NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tạm tính (chưa thuế), âm với REFUND',
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Chiết khấu, âm với REFUND',
  `tier_discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm giá theo hạng thành viên (%) - thông tin',
  `tier_discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền giảm theo hạng (âm với REFUND)',
  `taxable` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền chịu thuế = subtotal - discount',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Thuế GTGT, âm với REFUND',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `install_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Phí lắp đặt (đã gồm VAT)',
  `vat_shipping_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'VAT tách ngược trên phí vận chuyển (thông tin)',
  `vat_install_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'VAT tách ngược trên phí lắp đặt (thông tin)',
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Taxable + VAT + Ship, âm với REFUND',
  `currency` varchar(3) NOT NULL DEFAULT 'VND',
  `note` varchar(500) DEFAULT NULL,
  `is_immutable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Chống xóa — luôn = 1',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_code`, `invoice_number`, `type`, `order_id`, `original_invoice_id`, `customer_name`, `customer_phone`, `customer_email`, `address`, `invoice_date`, `subtotal`, `discount`, `tier_discount_percent`, `tier_discount_amount`, `taxable`, `vat_rate`, `vat`, `shipping_fee`, `install_fee`, `vat_shipping_amount`, `vat_install_amount`, `grand_total`, `currency`, `note`, `is_immutable`, `created_by`, `created_at`) VALUES
(1, 'HD-000001-1', 'HD-000001', 'SALE_INVOICE', 1, NULL, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', '2026-04-21 10:33:12', 17222222.22, 0.00, 0.00, 0.00, 17222222.22, 8.00, 1377777.78, 100000.00, 0.00, 4761.90, 0.00, 18700000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-21 03:33:12'),
(2, 'HD-000002-2', 'HD-000002', 'SALE_INVOICE', 2, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', '2026-04-14 10:37:52', 16481481.48, 200000.00, 0.00, 0.00, 16296296.30, 8.00, 1303703.70, 150000.00, 0.00, 7142.86, 0.00, 17750000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-14 03:37:52'),
(3, 'HD-000003-3', 'HD-000003', 'SALE_INVOICE', 3, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-04-18 12:05:16', 99537037.04, 50000.00, 0.00, 0.00, 99490740.74, 8.00, 7959259.26, 200000.00, 0.00, 9523.81, 0.00, 107650000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-18 05:05:16'),
(4, 'HD-000004-4', 'HD-000004', 'SALE_INVOICE', 4, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', '2026-04-28 09:36:29', 76111111.11, 0.00, 0.00, 0.00, 76111111.11, 8.00, 6088888.89, 100000.00, 150000.00, 4761.90, 7142.86, 82450000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-28 02:36:29'),
(5, 'HD-000005-5', 'HD-000005', 'SALE_INVOICE', 5, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', '2026-04-24 17:32:09', 23037037.04, 0.00, 0.00, 0.00, 23037037.04, 8.00, 1842962.96, 50000.00, 0.00, 2380.95, 0.00, 24930000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-24 10:32:09'),
(6, 'HD-000006-6', 'HD-000006', 'SALE_INVOICE', 6, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', '2026-04-24 17:05:13', 55277777.78, 100000.00, 0.00, 0.00, 55185185.19, 8.00, 4414814.81, 50000.00, 150000.00, 2380.95, 7142.86, 59800000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-04-24 10:05:13'),
(7, 'HD-000008-8', 'HD-000008', 'SALE_INVOICE', 8, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-05-19 13:42:07', 2129629.63, 150000.00, 5.00, 107500.00, 1891203.70, 8.00, 151296.30, 50000.00, 0.00, 2380.95, 0.00, 2092500.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-19 06:42:07'),
(8, 'HD-000009-9', 'HD-000009', 'SALE_INVOICE', 9, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', '2026-05-25 09:23:22', 6888888.89, 0.00, 0.00, 0.00, 6888888.89, 8.00, 551111.11, 50000.00, 0.00, 2380.95, 0.00, 7490000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-25 02:23:22'),
(9, 'HD-000010-10', 'HD-000010', 'SALE_INVOICE', 10, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-05-14 12:44:29', 9629629.63, 100000.00, 5.00, 515000.00, 9060185.19, 8.00, 724814.81, 50000.00, 150000.00, 2380.95, 7142.86, 9985000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-14 05:44:29'),
(10, 'HD-000011-11', 'HD-000011', 'SALE_INVOICE', 11, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', '2026-05-10 13:07:48', 7870370.37, 250000.00, 0.00, 0.00, 7638888.89, 8.00, 611111.11, 200000.00, 150000.00, 9523.81, 7142.86, 8600000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-10 06:07:48'),
(11, 'HD-000012-12', 'HD-000012', 'SALE_INVOICE', 12, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', '2026-05-22 09:10:52', 5000000.00, 150000.00, 0.00, 0.00, 4861111.11, 8.00, 388888.89, 150000.00, 150000.00, 7142.86, 7142.86, 5550000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-22 02:10:52'),
(12, 'HD-000013-13', 'HD-000013', 'SALE_INVOICE', 13, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-05-10 12:03:49', 9351851.85, 0.00, 5.00, 505000.00, 8884259.26, 8.00, 710740.74, 0.00, 0.00, 0.00, 0.00, 9595000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-10 05:03:49'),
(13, 'HD-000014-14', 'HD-000014', 'SALE_INVOICE', 14, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-05-24 15:05:29', 32314814.81, 0.00, 5.00, 1745000.00, 30699074.07, 8.00, 2455925.93, 150000.00, 150000.00, 7142.86, 7142.86, 33455000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-05-24 08:05:29'),
(14, 'HD-000015-15', 'HD-000015', 'SALE_INVOICE', 15, NULL, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', '2026-06-14 17:40:08', 3148148.15, 0.00, 0.00, 0.00, 3148148.15, 8.00, 251851.85, 200000.00, 150000.00, 9523.81, 7142.86, 3750000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-14 10:40:08'),
(15, 'HD-000016-16', 'HD-000016', 'SALE_INVOICE', 16, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', '2026-06-06 14:00:21', 26481481.48, 0.00, 0.00, 0.00, 26481481.48, 8.00, 2118518.52, 0.00, 0.00, 0.00, 0.00, 28600000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-06 07:00:21'),
(16, 'HD-000017-17', 'HD-000017', 'SALE_INVOICE', 17, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-06-13 09:49:08', 39898148.15, 0.00, 5.00, 2154500.00, 37903240.74, 8.00, 3032259.26, 50000.00, 0.00, 2380.95, 0.00, 40985500.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-13 02:49:08'),
(17, 'HD-000018-18', 'HD-000018', 'SALE_INVOICE', 18, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-06-28 16:35:54', 2592592.59, 0.00, 3.00, 84000.00, 2514814.81, 8.00, 201185.19, 150000.00, 150000.00, 7142.86, 7142.86, 3016000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-28 09:35:54'),
(18, 'HD-000019-19', 'HD-000019', 'SALE_INVOICE', 19, NULL, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', '2026-06-09 16:17:02', 12592592.59, 0.00, 0.00, 0.00, 12592592.59, 8.00, 1007407.41, 0.00, 0.00, 0.00, 0.00, 13600000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-09 09:17:02'),
(19, 'HD-000020-20', 'HD-000020', 'SALE_INVOICE', 20, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', '2026-06-10 14:49:24', 25555555.56, 0.00, 0.00, 0.00, 25555555.56, 8.00, 2044444.44, 100000.00, 0.00, 4761.90, 0.00, 27700000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-10 07:49:24'),
(20, 'HD-000021-21', 'HD-000021', 'SALE_INVOICE', 21, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-06-20 12:45:18', 6851851.85, 50000.00, 3.00, 220500.00, 6601388.89, 8.00, 528111.11, 150000.00, 150000.00, 7142.86, 7142.86, 7429500.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-20 05:45:18'),
(21, 'HD-000022-22', 'HD-000022', 'SALE_INVOICE', 22, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-06-05 10:20:34', 46574074.07, 0.00, 0.00, 0.00, 46574074.07, 8.00, 3725925.93, 0.00, 0.00, 0.00, 0.00, 50300000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-06-05 03:20:34'),
(22, 'HD-000024-24', 'HD-000024', 'SALE_INVOICE', 24, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-07-24 17:04:17', 15185185.19, 50000.00, 3.00, 490500.00, 14684722.22, 8.00, 1174777.78, 50000.00, 0.00, 2380.95, 0.00, 15909500.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-24 10:04:17'),
(23, 'HD-000025-25', 'HD-000025', 'SALE_INVOICE', 25, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', '2026-07-26 14:28:42', 2962962.96, 0.00, 0.00, 0.00, 2962962.96, 8.00, 237037.04, 0.00, 150000.00, 0.00, 7142.86, 3350000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-26 07:28:42'),
(24, 'HD-000026-26', 'HD-000026', 'SALE_INVOICE', 26, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-07-21 18:20:57', 8055555.56, 0.00, 5.00, 435000.00, 7652777.78, 8.00, 612222.22, 100000.00, 150000.00, 4761.90, 7142.86, 8515000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-21 11:20:57'),
(25, 'HD-000027-27', 'HD-000027', 'SALE_INVOICE', 27, NULL, 'Võ Mỹ Linh', '0909000006', 'khach.linh@example.com', '60 Nguyễn Chí Thanh', '2026-07-10 15:39:07', 57777777.78, 150000.00, 0.00, 0.00, 57638888.89, 8.00, 4611111.11, 0.00, 0.00, 0.00, 0.00, 62250000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-10 08:39:07'),
(26, 'HD-000028-28', 'HD-000028', 'SALE_INVOICE', 28, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-07-12 14:29:19', 8518518.52, 0.00, 0.00, 0.00, 8518518.52, 8.00, 681481.48, 150000.00, 0.00, 7142.86, 0.00, 9350000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-12 07:29:19'),
(27, 'HD-000029-29', 'HD-000029', 'SALE_INVOICE', 29, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', '2026-07-07 14:18:28', 122037037.04, 0.00, 0.00, 0.00, 122037037.04, 8.00, 9762962.96, 100000.00, 0.00, 4761.90, 0.00, 131900000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-07 07:18:28'),
(28, 'HD-000030-30', 'HD-000030', 'SALE_INVOICE', 30, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-07-27 16:54:26', 12592592.59, 0.00, 0.00, 0.00, 12592592.59, 8.00, 1007407.41, 150000.00, 0.00, 7142.86, 0.00, 13750000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-27 09:54:26'),
(29, 'HD-000031-31', 'HD-000031', 'SALE_INVOICE', 31, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', '2026-07-10 16:07:40', 58148148.15, 0.00, 0.00, 0.00, 58148148.15, 8.00, 4651851.85, 200000.00, 0.00, 9523.81, 0.00, 63000000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-10 09:07:40'),
(30, 'HD-000032-32', 'HD-000032', 'SALE_INVOICE', 32, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-07-27 13:51:53', 33148148.15, 0.00, 0.00, 0.00, 33148148.15, 8.00, 2651851.85, 0.00, 150000.00, 0.00, 7142.86, 35950000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-07-27 06:51:53'),
(31, 'HD-000033-33', 'HD-000033', 'SALE_INVOICE', 33, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', '2026-08-10 18:49:04', 36851851.85, 0.00, 0.00, 0.00, 36851851.85, 8.00, 2948148.15, 0.00, 0.00, 0.00, 0.00, 39800000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-10 11:49:04'),
(32, 'HD-000034-34', 'HD-000034', 'SALE_INVOICE', 34, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-08-18 11:13:46', 42500000.00, 0.00, 3.00, 1377000.00, 41225000.00, 8.00, 3298000.00, 150000.00, 0.00, 7142.86, 0.00, 44673000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-18 04:13:46'),
(33, 'HD-000035-35', 'HD-000035', 'SALE_INVOICE', 35, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-08-05 15:35:46', 28796296.30, 0.00, 3.00, 933000.00, 27932407.41, 8.00, 2234592.59, 150000.00, 150000.00, 7142.86, 7142.86, 30467000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-05 08:35:46'),
(34, 'HD-000036-36', 'HD-000036', 'SALE_INVOICE', 36, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', '2026-08-27 15:43:30', 21435185.19, 0.00, 0.00, 0.00, 21435185.19, 8.00, 1714814.81, 100000.00, 0.00, 4761.90, 0.00, 23250000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-27 08:43:30'),
(35, 'HD-000037-37', 'HD-000037', 'SALE_INVOICE', 37, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', '2026-08-16 10:39:26', 48287037.04, 50000.00, 0.00, 0.00, 48240740.74, 8.00, 3859259.26, 200000.00, 150000.00, 9523.81, 7142.86, 52450000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-16 03:39:26'),
(36, 'HD-000038-38', 'HD-000038', 'SALE_INVOICE', 38, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-08-21 15:57:50', 30555555.56, 0.00, 5.00, 1650000.00, 29027777.78, 8.00, 2322222.22, 150000.00, 150000.00, 7142.86, 7142.86, 31650000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-21 08:57:50'),
(37, 'HD-000039-39', 'HD-000039', 'SALE_INVOICE', 39, NULL, 'Lê Hoàng Cương', '0912345672', 'cuong@example.com', '56 Trần Phú', '2026-08-11 10:35:29', 18148148.15, 0.00, 0.00, 0.00, 18148148.15, 8.00, 1451851.85, 200000.00, 0.00, 9523.81, 0.00, 19800000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-11 03:35:29'),
(38, 'HD-000040-40', 'HD-000040', 'SALE_INVOICE', 40, NULL, 'Đặng Phương Thảo', '0909000007', 'khach.thao@example.com', '70 Võ Văn Tần', '2026-08-12 14:05:42', 1712962.96, 150000.00, 0.00, 0.00, 1574074.07, 8.00, 125925.93, 200000.00, 0.00, 9523.81, 0.00, 1900000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-12 07:05:42'),
(39, 'HD-000041-41', 'HD-000041', 'SALE_INVOICE', 41, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-08-04 10:53:42', 16481481.48, 0.00, 0.00, 0.00, 16481481.48, 8.00, 1318518.52, 150000.00, 0.00, 7142.86, 0.00, 17950000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-04 03:53:42'),
(40, 'HD-000042-42', 'HD-000042', 'SALE_INVOICE', 42, NULL, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', '2026-08-10 13:49:23', 39814814.81, 0.00, 5.00, 2150000.00, 37824074.07, 8.00, 3025925.93, 150000.00, 0.00, 7142.86, 0.00, 41000000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-08-10 06:49:23'),
(41, 'HD-000044-44', 'HD-000044', 'SALE_INVOICE', 44, NULL, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', '2026-09-10 18:19:48', 6851851.85, 100000.00, 3.00, 219000.00, 6556481.48, 8.00, 524518.52, 100000.00, 0.00, 4761.90, 0.00, 7181000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-10 11:19:48'),
(42, 'HD-000045-45', 'HD-000045', 'SALE_INVOICE', 45, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', '2026-09-08 16:10:08', 35648148.15, 100000.00, 0.00, 0.00, 35555555.56, 8.00, 2844444.44, 0.00, 0.00, 0.00, 0.00, 38400000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-08 09:10:08'),
(43, 'HD-000046-46', 'HD-000046', 'SALE_INVOICE', 46, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', '2026-09-12 17:41:35', 11833333.33, 0.00, 0.00, 0.00, 11833333.33, 8.00, 946666.67, 50000.00, 0.00, 2380.95, 0.00, 12830000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-12 10:41:35'),
(44, 'HD-000047-47', 'HD-000047', 'SALE_INVOICE', 47, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', '2026-09-08 15:29:31', 68518518.52, 0.00, 0.00, 0.00, 68518518.52, 8.00, 5481481.48, 200000.00, 0.00, 9523.81, 0.00, 74200000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-08 08:29:31'),
(45, 'HD-000048-48', 'HD-000048', 'SALE_INVOICE', 48, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', '2026-09-13 13:09:44', 31759259.26, 0.00, 0.00, 0.00, 31759259.26, 8.00, 2540740.74, 150000.00, 0.00, 7142.86, 0.00, 34450000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-13 06:09:44'),
(46, 'HD-000049-49', 'HD-000049', 'SALE_INVOICE', 49, NULL, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', '2026-09-14 11:07:12', 29629629.63, 0.00, 0.00, 0.00, 29629629.63, 8.00, 2370370.37, 50000.00, 0.00, 2380.95, 0.00, 32050000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-14 04:07:12'),
(47, 'HD-000050-50', 'HD-000050', 'SALE_INVOICE', 50, NULL, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', '2026-09-13 15:30:09', 13703703.70, 0.00, 0.00, 0.00, 13703703.70, 8.00, 1096296.30, 100000.00, 0.00, 4761.90, 0.00, 14900000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-13 08:30:09'),
(48, 'HD-000051-51', 'HD-000051', 'SALE_INVOICE', 51, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', '2026-09-07 13:27:29', 101574074.07, 0.00, 0.00, 0.00, 101574074.07, 8.00, 8125925.93, 0.00, 0.00, 0.00, 0.00, 109700000.00, 'VND', 'Tự động phát hành khi đơn hoàn thành.', 1, NULL, '2026-09-07 06:27:29');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(120) DEFAULT NULL,
  `variant_value` varchar(120) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đơn giá (chưa thuế)',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'quantity * unit_price'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `product_name`, `variant_name`, `variant_value`, `quantity`, `unit_price`, `vat_rate`, `line_total`) VALUES
(1, 1, 7, 'Bàn trà gỗ sồi tròn', NULL, NULL, 1, 3800000.00, 8.00, 3800000.00),
(2, 1, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 2, 6800000.00, 8.00, 13600000.00),
(3, 1, 36, 'Đồng hồ treo tường gỗ sồi', NULL, NULL, 1, 1200000.00, 8.00, 1200000.00),
(4, 2, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 2, 8900000.00, 8.00, 17800000.00),
(5, 3, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 2, 11900000.00, 8.00, 23800000.00),
(6, 3, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 1, 65900000.00, 8.00, 65900000.00),
(7, 3, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 2, 8900000.00, 8.00, 17800000.00),
(8, 4, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 1, 12500000.00, 8.00, 12500000.00),
(9, 4, 31, 'Sofa 3 chỗ da lộn sang trọng', NULL, NULL, 1, 45900000.00, 8.00, 45900000.00),
(10, 4, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 2, 11900000.00, 8.00, 23800000.00),
(11, 5, 28, 'Sofa giường thông minh đa năng', NULL, NULL, 1, 11200000.00, 8.00, 11200000.00),
(12, 5, 22, 'Đèn bàn gỗ sồi vintage', NULL, NULL, 2, 890000.00, 8.00, 1780000.00),
(13, 5, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 1, 11900000.00, 8.00, 11900000.00),
(14, 6, 47, 'Bộ bàn ghế xếp sân vườn', NULL, NULL, 1, 4200000.00, 8.00, 4200000.00),
(15, 6, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 2, 21500000.00, 8.00, 43000000.00),
(16, 6, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 1, 12500000.00, 8.00, 12500000.00),
(17, 7, 33, 'Đèn chùm tre trang trí', NULL, NULL, 1, 2300000.00, 8.00, 2300000.00),
(18, 8, 21, 'Kệ sách chữ L văn phòng', NULL, NULL, 1, 4900000.00, 8.00, 4900000.00),
(19, 8, 41, 'Ghế bố dây choàng ban công', NULL, NULL, 1, 1850000.00, 8.00, 1850000.00),
(20, 8, 44, 'Đèn bàn sạc không dây sen đá', NULL, NULL, 1, 690000.00, 8.00, 690000.00),
(21, 9, 10, 'Bàn ăn gấp thông minh', NULL, NULL, 2, 5200000.00, 8.00, 10400000.00),
(22, 10, 46, 'Kệ gỗ nhiều ngăn Decalist', NULL, NULL, 1, 2600000.00, 8.00, 2600000.00),
(23, 10, 25, 'Bàn trang điểm gương sáng 3 thùy', NULL, NULL, 1, 5900000.00, 8.00, 5900000.00),
(24, 11, 34, 'Bàn trà mặt kính chân sắt', NULL, NULL, 2, 2700000.00, 8.00, 5400000.00),
(25, 12, 42, 'Bàn ăn xếp hộp 1m4', NULL, NULL, 1, 4900000.00, 8.00, 4900000.00),
(26, 12, 10, 'Bàn ăn gấp thông minh', NULL, NULL, 1, 5200000.00, 8.00, 5200000.00),
(27, 13, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 2, 3200000.00, 8.00, 6400000.00),
(28, 13, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 1, 28500000.00, 8.00, 28500000.00),
(29, 14, 40, 'Bàn làm việc đơn giản 120cm', NULL, NULL, 1, 3400000.00, 8.00, 3400000.00),
(30, 15, 30, 'Giường ngủ 1m2 cho bé', NULL, NULL, 2, 5900000.00, 8.00, 11800000.00),
(31, 15, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 1, 16800000.00, 8.00, 16800000.00),
(32, 16, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 1, 8900000.00, 8.00, 8900000.00),
(33, 16, 27, 'Ghế xếp gỗ du lịch', NULL, NULL, 1, 590000.00, 8.00, 590000.00),
(34, 16, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 2, 16800000.00, 8.00, 33600000.00),
(35, 17, 50, 'Bàn làm việc cực gọn 90cm', NULL, NULL, 1, 2800000.00, 8.00, 2800000.00),
(36, 18, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 2, 6800000.00, 8.00, 13600000.00),
(37, 19, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 1, 9800000.00, 8.00, 9800000.00),
(38, 19, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 2, 8900000.00, 8.00, 17800000.00),
(39, 20, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 1, 7400000.00, 8.00, 7400000.00),
(40, 21, 11, 'Bàn ăn mặt đá hoa cương', NULL, NULL, 2, 18900000.00, 8.00, 37800000.00),
(41, 21, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 1, 12500000.00, 8.00, 12500000.00),
(42, 22, 29, 'Bàn làm việc bo góc chữ L', NULL, NULL, 2, 8200000.00, 8.00, 16400000.00),
(43, 23, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 1, 3200000.00, 8.00, 3200000.00),
(44, 24, 20, 'Kệ sách đứng gỗ thông', NULL, NULL, 1, 2900000.00, 8.00, 2900000.00),
(45, 24, 40, 'Bàn làm việc đơn giản 120cm', NULL, NULL, 1, 3400000.00, 8.00, 3400000.00),
(46, 24, 36, 'Đồng hồ treo tường gỗ sồi', NULL, NULL, 2, 1200000.00, 8.00, 2400000.00),
(47, 25, 25, 'Bàn trang điểm gương sáng 3 thùy', NULL, NULL, 2, 5900000.00, 8.00, 11800000.00),
(48, 25, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 2, 21500000.00, 8.00, 43000000.00),
(49, 25, 7, 'Bàn trà gỗ sồi tròn', NULL, NULL, 2, 3800000.00, 8.00, 7600000.00),
(50, 26, 35, 'Kệ tivi cánh lùa hiện đại', NULL, NULL, 2, 4600000.00, 8.00, 9200000.00),
(51, 27, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 2, 65900000.00, 8.00, 131800000.00),
(52, 28, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 2, 6800000.00, 8.00, 13600000.00),
(53, 29, 11, 'Bàn ăn mặt đá hoa cương', NULL, NULL, 2, 18900000.00, 8.00, 37800000.00),
(54, 29, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 2, 12500000.00, 8.00, 25000000.00),
(55, 30, 18, 'Kệ tivi gỗ sồi hiện đại', NULL, NULL, 2, 5800000.00, 8.00, 11600000.00),
(56, 30, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 2, 3200000.00, 8.00, 6400000.00),
(57, 30, 13, 'Bàn làm việc nâng hạ thông minh', NULL, NULL, 2, 8900000.00, 8.00, 17800000.00),
(58, 31, 39, 'Giường ngủ 1m8 đầu giường liền tủ', NULL, NULL, 2, 19900000.00, 8.00, 39800000.00),
(59, 32, 17, 'Tủ quần áo 6 cánh cao cấp', NULL, NULL, 1, 38500000.00, 8.00, 38500000.00),
(60, 32, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 1, 7400000.00, 8.00, 7400000.00),
(61, 33, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 2, 12500000.00, 8.00, 25000000.00),
(62, 33, 6, 'Ghế làm việc gỗ Sồi Bách', NULL, NULL, 1, 2900000.00, 8.00, 2900000.00),
(63, 33, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 1, 3200000.00, 8.00, 3200000.00),
(64, 34, 23, 'Đèn sàn đứng 3 chân', NULL, NULL, 1, 2350000.00, 8.00, 2350000.00),
(65, 34, 47, 'Bộ bàn ghế xếp sân vườn', NULL, NULL, 2, 4200000.00, 8.00, 8400000.00),
(66, 34, 12, 'Bàn làm việc gỗ sồi chân A', NULL, NULL, 2, 6200000.00, 8.00, 12400000.00),
(67, 35, 1, 'Sofa bộ 3 chỗ WoodCon Classic', NULL, NULL, 2, 18900000.00, 8.00, 37800000.00),
(68, 35, 37, 'Giá treo tường 3 tầng', NULL, NULL, 1, 1850000.00, 8.00, 1850000.00),
(69, 35, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 1, 12500000.00, 8.00, 12500000.00),
(70, 36, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 2, 3200000.00, 8.00, 6400000.00),
(71, 36, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 1, 16800000.00, 8.00, 16800000.00),
(72, 36, 42, 'Bàn ăn xếp hộp 1m4', NULL, NULL, 2, 4900000.00, 8.00, 9800000.00),
(73, 37, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 2, 9800000.00, 8.00, 19600000.00),
(74, 38, 37, 'Giá treo tường 3 tầng', NULL, NULL, 1, 1850000.00, 8.00, 1850000.00),
(75, 39, 3, 'Sofa văng 2 chỗ Mây Làm', NULL, NULL, 2, 8900000.00, 8.00, 17800000.00),
(76, 40, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 2, 21500000.00, 8.00, 43000000.00),
(77, 41, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 1, 7400000.00, 8.00, 7400000.00),
(78, 42, 17, 'Tủ quần áo 6 cánh cao cấp', NULL, NULL, 1, 38500000.00, 8.00, 38500000.00),
(79, 43, 27, 'Ghế xếp gỗ du lịch', NULL, NULL, 2, 590000.00, 8.00, 1180000.00),
(80, 43, 18, 'Kệ tivi gỗ sồi hiện đại', NULL, NULL, 2, 5800000.00, 8.00, 11600000.00),
(81, 44, 21, 'Kệ sách chữ L văn phòng', NULL, NULL, 1, 4900000.00, 8.00, 4900000.00),
(82, 44, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 1, 3200000.00, 8.00, 3200000.00),
(83, 44, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 1, 65900000.00, 8.00, 65900000.00),
(84, 45, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 1, 28500000.00, 8.00, 28500000.00),
(85, 45, 20, 'Kệ sách đứng gỗ thông', NULL, NULL, 2, 2900000.00, 8.00, 5800000.00),
(86, 46, 12, 'Bàn làm việc gỗ sồi chân A', NULL, NULL, 2, 6200000.00, 8.00, 12400000.00),
(87, 46, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 2, 9800000.00, 8.00, 19600000.00),
(88, 47, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 2, 7400000.00, 8.00, 14800000.00),
(89, 48, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 2, 28500000.00, 8.00, 57000000.00),
(90, 48, 31, 'Sofa 3 chỗ da lộn sang trọng', NULL, NULL, 1, 45900000.00, 8.00, 45900000.00),
(91, 48, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 1, 6800000.00, 8.00, 6800000.00);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `log_type` varchar(50) NOT NULL DEFAULT 'system',
  `description` text DEFAULT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `log_type`, `description`, `admin_id`, `user_id`, `ip`, `created_at`) VALUES
(1, 'admin', 'Đăng nhập admin admin@woodcon.vn', 1, NULL, '::1', '2026-09-14 17:36:35'),
(2, 'admin', 'Đăng nhập admin admin@woodcon.vn', 1, NULL, '::1', '2026-09-15 07:12:21'),
(3, 'settings', 'Cập nhật 3 cài đặt (nhóm: ai)', 1, NULL, '::1', '2026-09-15 16:31:05'),
(4, 'error', '[Route dang-nhap] SQLSTATE[42S22]: Column not found: 1054 Unknown column \'username\' in \'where clause\' @ C:\\xampp\\htdocs\\wood-shop\\models\\Base.php:62', NULL, NULL, '::1', '2026-09-15 17:03:07'),
(5, 'admin', 'Đăng nhập admin admin@woodcon.vn', 1, NULL, '::1', '2026-09-16 11:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `membership_benefits`
--

CREATE TABLE `membership_benefits` (
  `id` int(10) UNSIGNED NOT NULL,
  `tier_id` int(10) UNSIGNED NOT NULL COMMENT 'FK -> membership_tiers.id',
  `label` varchar(255) NOT NULL COMMENT 'Mô tả đặc quyền theo văn phong cao cấp',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `membership_benefits`
--

INSERT INTO `membership_benefits` (`id`, `tier_id`, `label`, `sort_order`, `status`) VALUES
(1, 1, 'Tích điểm thưởng cho từng đơn hàng', 10, 1),
(2, 1, 'Thanh toán linh hoạt: COD hoặc chuyển khoản', 30, 1),
(3, 2, 'Tích điểm thưởng cho từng đơn hàng', 10, 1),
(4, 2, 'Giảm giá trực tiếp mỗi đơn hàng', 20, 1),
(5, 2, 'Thanh toán linh hoạt: COD hoặc chuyển khoản', 30, 1),
(6, 2, 'Ưu tiên xử lý đơn & giao hàng sớm', 40, 1),
(7, 3, 'Tích điểm thưởng cho từng đơn hàng', 10, 1),
(8, 3, 'Giảm giá trực tiếp mỗi đơn hàng', 20, 1),
(9, 3, 'Thanh toán linh hoạt: COD hoặc chuyển khoản', 30, 1),
(10, 3, 'Ưu tiên xử lý đơn & giao hàng sớm', 40, 1),
(11, 3, 'Chuyên viên chăm sóc khách hàng riêng', 50, 1);

-- --------------------------------------------------------

--
-- Table structure for table `membership_tiers`
--

CREATE TABLE `membership_tiers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `min_total_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng chi tiêu tối thiểu để đạt hạng',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `points_multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `membership_tiers`
--

INSERT INTO `membership_tiers` (`id`, `name`, `min_total_spent`, `discount_percent`, `points_multiplier`, `status`, `created_at`) VALUES
(1, 'Thành viên', 0.00, 0.00, 1.00, 1, '2026-09-14 11:49:35'),
(2, 'VIP', 5000000.00, 3.00, 1.20, 1, '2026-09-14 11:49:35'),
(3, 'Diamond', 20000000.00, 5.00, 1.50, 1, '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(120) DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `summary`, `content`, `image`, `author`, `view_count`, `status`, `created_at`, `updated_at`) VALUES
(1, '5 xu hướng nội thất gỗ nổi bật 2026', '5-xu-huong-noi-that-go-2026', 'Khám phá những xu hướng thiết kế nội thất gỗ đang dẫn đầu thị trường năm 2026.', '<p>Năm 2026 chứng kiến sự trở lại mạnh mẽ của nội thất gỗ tự nhiên với các tông màu ấm, chất liệu bền vững và thiết kế tối giản. Dưới đây là 5 xu hướng đáng chú ý nhất.</p>', 'assets/images/shop/shelf-minimal.jpg', 'WoodCon Editor', 0, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(2, 'Mẹo chọn sofa phù hợp diện tích phòng khách', 'meo-chon-sofa-phu-hop-dien-tich-phong-khach', 'Hướng dẫn chọn sofa theo kích thước phòng giúp không gian hài hòa và tiện nghi.', '<p>Việc chọn sofa cần cân nhắc diện tích, lối đi và tỷ lệ với các món nội thất khác.</p>', 'assets/images/shop/sofa-grey.jpg', 'WoodCon Editor', 0, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(3, 'Gỗ óc chó hay gỗ sồi? Nên chọn chất liệu nào', 'go-oc-cho-hay-go-soi', 'So sánh chi tiết hai loại gỗ nội thất phổ biến nhất trong trang trí nhà ở.', '<p>Gỗ óc chó sang trọng, vân đậm; gỗ sồi sáng, nhẹ. Tùy phong cách và ngân sách mà lựa chọn phù hợp.</p>', 'assets/images/shop/dining-wood.jpg', 'WoodCon Editor', 0, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(4, 'Cách bảo quản nội thất gỗ bền đẹp theo năm', 'cach-bao-quan-noi-that-go', 'Chia sẻ phương pháp vệ sinh, chống mối mọt và giữ màu gỗ lâu dài.', '<p>Để nội thất gỗ bền, cần tránh ánh nắng trực tiếp, lau bằng khăn mềm và định kỳ dưỡng gỗ.</p>', 'assets/images/shop/desk-modern.jpg', 'WoodCon Editor', 0, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(5, 'Bố trí phòng ngủ gỗ tối giản giúp ngủ ngon', 'bo-tri-phong-ngu-go-toi-gian', 'Ý tưởng phòng ngủ với nội thất gỗ tạo cảm giác thư thái.', '<p>Không gian ngủ tối giản với giường gỗ, tủ thấp và ánh sáng vàng giúp thư giãn tốt hơn.</p>', 'assets/images/shop/bed-linen.jpg', 'WoodCon Editor', 0, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'null = gửi admin',
  `type` varchar(40) NOT NULL DEFAULT 'system',
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(30) NOT NULL,
  `invoice_number` varchar(60) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `delivery_lat` decimal(10,7) DEFAULT NULL COMMENT 'Vĩ độ địa chỉ giao (Goong Maps)',
  `delivery_lng` decimal(10,7) DEFAULT NULL COMMENT 'Kinh độ địa chỉ giao (Goong Maps)',
  `note` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tạm tính (chưa VAT)',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tiền VAT đã trích',
  `vat_shipping_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'VAT tách ngược trên phí vận chuyển (đã gồm trong giá)',
  `vat_install_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'VAT tách ngược trên phí lắp đặt (đã gồm trong giá)',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shipping_type` varchar(20) DEFAULT NULL COMMENT 'shop (Loại 1) | carrier (Loại 2)',
  `install_requested` tinyint(1) NOT NULL DEFAULT 0,
  `install_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm giá mã giảm giá',
  `freeship_discount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm phí ship do mã freeship',
  `tier_discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Giảm giá theo hạng thành viên (%) - snapshot lúc đặt',
  `tier_discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền giảm theo hạng (snapshot lúc đặt, đã trừ khỏi total_amount)',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng cộng (gồm VAT)',
  `payment_method` enum('cod','bank','qr','wallet') NOT NULL DEFAULT 'cod',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `order_status` enum('pending','manual_verifying','confirmed','preparing','shipping','delivery_failed','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `delivery_fail_count` int(11) NOT NULL DEFAULT 0,
  `tracking_code` varchar(60) DEFAULT NULL COMMENT 'Mã vận đơn',
  `delivery_company` varchar(60) DEFAULT NULL,
  `cancel_request_status` enum('none','requested','approved','rejected') NOT NULL DEFAULT 'none',
  `cancel_reason` varchar(500) DEFAULT NULL,
  `cancel_reject_reason` varchar(500) DEFAULT NULL,
  `trust_level_at_order` enum('green','yellow','red') DEFAULT NULL,
  `risk_flag` tinyint(1) NOT NULL DEFAULT 0,
  `otp_verified` tinyint(1) NOT NULL DEFAULT 0,
  `manual_verify_note` varchar(500) DEFAULT NULL,
  `otp_bypass_reason` varchar(255) DEFAULT NULL COMMENT 'Lý do miễn OTP (VD hạng thành viên)',
  `voucher_code` varchar(40) DEFAULT NULL,
  `freeship_code` varchar(40) DEFAULT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `points_used` int(11) NOT NULL DEFAULT 0 COMMENT 'Số điểm khách đã dùng cho đơn này',
  `delivered_at` datetime DEFAULT NULL,
  `refund_thread` text DEFAULT NULL COMMENT 'Chuỗi log hoàn tiền JSON',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `invoice_number`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `address`, `ward`, `district`, `city`, `delivery_lat`, `delivery_lng`, `note`, `subtotal`, `vat_rate`, `vat_amount`, `vat_shipping_amount`, `vat_install_amount`, `shipping_fee`, `shipping_type`, `install_requested`, `install_fee`, `discount_amount`, `freeship_discount`, `tier_discount_percent`, `tier_discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `is_deleted`, `deleted_at`, `delivery_fail_count`, `tracking_code`, `delivery_company`, `cancel_request_status`, `cancel_reason`, `cancel_reject_reason`, `trust_level_at_order`, `risk_flag`, `otp_verified`, `manual_verify_note`, `otp_bypass_reason`, `voucher_code`, `freeship_code`, `points_earned`, `points_used`, `delivered_at`, `refund_thread`, `created_at`, `updated_at`) VALUES
(1, 'WC260418191F', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 17222222.22, 8.00, 1377777.78, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 18700000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-21 10:33:12', NULL, '2026-04-18 03:58:59', '2026-04-21 03:33:12'),
(2, 'WC26041382A1', NULL, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', 'Hải Châu 1', 'Hải Châu', 'Đà Nẵng', NULL, NULL, 'Đơn hàng mua sắm nội thất', 16481481.48, 8.00, 1303703.70, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 200000.00, 0.00, 0.00, 0.00, 17750000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-14 10:37:52', NULL, '2026-04-13 01:35:51', '2026-04-14 03:37:52'),
(3, 'WC260416F92B', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 99537037.04, 8.00, 7959259.26, 9523.81, 0.00, 200000.00, 'standard', 0, 0.00, 50000.00, 0.00, 0.00, 0.00, 107650000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-18 12:05:16', NULL, '2026-04-16 04:29:12', '2026-04-18 05:05:16'),
(4, 'WC2604274747', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 76111111.11, 8.00, 6088888.89, 4761.90, 7142.86, 100000.00, 'standard', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, 82450000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-28 09:36:29', NULL, '2026-04-27 06:43:23', '2026-04-28 02:36:29'),
(5, 'WC2604224844', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 23037037.04, 8.00, 1842962.96, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 24930000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-24 17:32:09', NULL, '2026-04-22 09:33:52', '2026-04-24 10:32:09'),
(6, 'WC2604214BBA', NULL, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', 'Hải Châu 1', 'Hải Châu', 'Đà Nẵng', NULL, NULL, 'Đơn hàng mua sắm nội thất', 55277777.78, 8.00, 4414814.81, 2380.95, 7142.86, 50000.00, 'standard', 1, 150000.00, 100000.00, 0.00, 0.00, 0.00, 59800000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-04-24 17:05:13', NULL, '2026-04-21 11:43:15', '2026-04-24 10:05:13'),
(7, 'WC260423CA7C', NULL, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', 'Phường 13', 'Quận 3', 'TP. Hồ Chí Minh', NULL, NULL, 'Khách đổi ý muốn chọn mẫu khác', 11574074.07, 8.00, 925925.93, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 12550000.00, 'cod', 'unpaid', 'cancelled', 0, NULL, 0, NULL, NULL, 'none', 'Khách đổi ý muốn chọn mẫu khác', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-04-23 04:48:52', '2026-04-23 04:48:52'),
(8, 'WC260517D92E', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 2129629.63, 8.00, 151296.30, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 150000.00, 0.00, 5.00, 107500.00, 2092500.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-19 13:42:07', NULL, '2026-05-17 07:44:34', '2026-05-19 06:42:07'),
(9, 'WC2605221978', NULL, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', 'Phường 13', 'Quận 3', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 6888888.89, 8.00, 551111.11, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 7490000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-25 09:23:22', NULL, '2026-05-22 01:50:50', '2026-05-25 02:23:22'),
(10, 'WC260512F9AC', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 9629629.63, 8.00, 724814.81, 2380.95, 7142.86, 50000.00, 'standard', 1, 150000.00, 100000.00, 0.00, 5.00, 515000.00, 9985000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-14 12:44:29', NULL, '2026-05-12 11:09:34', '2026-05-14 05:44:29'),
(11, 'WC260507E3D2', NULL, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', 'Quan Hoa', 'Cầu Giấy', 'Hà Nội', NULL, NULL, 'Đơn hàng mua sắm nội thất', 7870370.37, 8.00, 611111.11, 9523.81, 7142.86, 200000.00, 'standard', 1, 150000.00, 250000.00, 0.00, 0.00, 0.00, 8600000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-10 13:07:48', NULL, '2026-05-07 01:26:51', '2026-05-10 06:07:48'),
(12, 'WC260519BA5A', NULL, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', 'Tân Hưng', 'Quận 7', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 5000000.00, 8.00, 388888.89, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 150000.00, 0.00, 0.00, 0.00, 5550000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-22 09:10:52', NULL, '2026-05-19 02:55:06', '2026-05-22 02:10:52'),
(13, 'WC2605082D26', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 9351851.85, 8.00, 710740.74, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 5.00, 505000.00, 9595000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-10 12:03:49', NULL, '2026-05-08 10:03:17', '2026-05-10 05:03:49'),
(14, 'WC260521653F', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 32314814.81, 8.00, 2455925.93, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 0.00, 0.00, 5.00, 1745000.00, 33455000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-24 15:05:29', NULL, '2026-05-21 10:06:06', '2026-05-24 08:05:29'),
(15, 'WC260611873E', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 3148148.15, 8.00, 251851.85, 9523.81, 7142.86, 200000.00, 'standard', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, 3750000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-14 17:40:08', NULL, '2026-06-11 04:53:51', '2026-06-14 10:40:08'),
(16, 'WC260604363C', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 26481481.48, 8.00, 2118518.52, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 28600000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-06 14:00:21', NULL, '2026-06-04 07:46:55', '2026-06-06 07:00:21'),
(17, 'WC260610C06B', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 39898148.15, 8.00, 3032259.26, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 0.00, 0.00, 5.00, 2154500.00, 40985500.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-13 09:49:08', NULL, '2026-06-10 07:15:29', '2026-06-13 02:49:08'),
(18, 'WC260625E902', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 2592592.59, 8.00, 201185.19, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 0.00, 0.00, 3.00, 84000.00, 3016000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-28 16:35:54', NULL, '2026-06-25 07:28:53', '2026-06-28 09:35:54'),
(19, 'WC26060652B4', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 12592592.59, 8.00, 1007407.41, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 13600000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-09 16:17:02', NULL, '2026-06-06 10:54:39', '2026-06-09 09:17:02'),
(20, 'WC2606087FD0', NULL, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', 'Tân Hưng', 'Quận 7', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 25555555.56, 8.00, 2044444.44, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 27700000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-10 14:49:24', NULL, '2026-06-08 07:08:52', '2026-06-10 07:49:24'),
(21, 'WC260618F25A', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 6851851.85, 8.00, 528111.11, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 50000.00, 0.00, 3.00, 220500.00, 7429500.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-20 12:45:18', NULL, '2026-06-18 06:42:55', '2026-06-20 05:45:18'),
(22, 'WC26060424B9', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 46574074.07, 8.00, 3725925.93, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 50300000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-06-05 10:20:34', NULL, '2026-06-04 10:24:05', '2026-06-05 03:20:34'),
(23, 'WC2606075956', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Khách đổi ý muốn chọn mẫu khác', 4537037.04, 8.00, 362962.96, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 4950000.00, 'cod', 'unpaid', 'cancelled', 0, NULL, 0, NULL, NULL, 'none', 'Khách đổi ý muốn chọn mẫu khác', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-06-07 03:52:35', '2026-06-07 03:52:35'),
(24, 'WC2607215418', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 15185185.19, 8.00, 1174777.78, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 50000.00, 0.00, 3.00, 490500.00, 15909500.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-24 17:04:17', NULL, '2026-07-21 02:01:59', '2026-07-24 10:04:17'),
(25, 'WC260723E09D', NULL, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', 'Phường 13', 'Quận 3', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 2962962.96, 8.00, 237037.04, 0.00, 7142.86, 0.00, 'standard', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, 3350000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-26 14:28:42', NULL, '2026-07-23 10:44:27', '2026-07-26 07:28:42'),
(26, 'WC26071855F0', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 8055555.56, 8.00, 612222.22, 4761.90, 7142.86, 100000.00, 'standard', 1, 150000.00, 0.00, 0.00, 5.00, 435000.00, 8515000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-21 18:20:57', NULL, '2026-07-18 02:45:44', '2026-07-21 11:20:57'),
(27, 'WC2607075088', NULL, NULL, 'Võ Mỹ Linh', '0909000006', 'khach.linh@example.com', '60 Nguyễn Chí Thanh', 'Kim Mã', 'Ba Đình', 'Hà Nội', NULL, NULL, 'Đơn hàng mua sắm nội thất', 57777777.78, 8.00, 4611111.11, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 150000.00, 0.00, 0.00, 0.00, 62250000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-10 15:39:07', NULL, '2026-07-07 12:25:51', '2026-07-10 08:39:07'),
(28, 'WC260711D288', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 8518518.52, 8.00, 681481.48, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 9350000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-12 14:29:19', NULL, '2026-07-11 12:53:20', '2026-07-12 07:29:19'),
(29, 'WC2607063931', NULL, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', 'Quan Hoa', 'Cầu Giấy', 'Hà Nội', NULL, NULL, 'Đơn hàng mua sắm nội thất', 122037037.04, 8.00, 9762962.96, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 131900000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-07 14:18:28', NULL, '2026-07-06 11:44:59', '2026-07-07 07:18:28'),
(30, 'WC260724C751', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 12592592.59, 8.00, 1007407.41, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 13750000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-27 16:54:26', NULL, '2026-07-24 01:58:44', '2026-07-27 09:54:26'),
(31, 'WC260709DC4D', NULL, NULL, 'Ngô Công Thành', '0909000005', 'khach.thanh@example.com', '50 Xuân Thủy', 'Quan Hoa', 'Cầu Giấy', 'Hà Nội', NULL, NULL, 'Đơn hàng mua sắm nội thất', 58148148.15, 8.00, 4651851.85, 9523.81, 0.00, 200000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 63000000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-10 16:07:40', NULL, '2026-07-09 01:34:10', '2026-07-10 09:07:40'),
(32, 'WC2607266FF7', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 33148148.15, 8.00, 2651851.85, 0.00, 7142.86, 0.00, 'standard', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, 35950000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-07-27 13:51:53', NULL, '2026-07-26 11:04:51', '2026-07-27 06:51:53'),
(33, 'WC2608086810', NULL, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', 'Phường 13', 'Quận 3', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 36851851.85, 8.00, 2948148.15, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 39800000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-10 18:49:04', NULL, '2026-08-08 11:50:24', '2026-08-10 11:49:04'),
(34, 'WC260817C3EB', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 42500000.00, 8.00, 3298000.00, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 3.00, 1377000.00, 44673000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-18 11:13:46', NULL, '2026-08-17 04:51:32', '2026-08-18 04:13:46'),
(35, 'WC26080280EB', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 28796296.30, 8.00, 2234592.59, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 0.00, 0.00, 3.00, 933000.00, 30467000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-05 15:35:46', NULL, '2026-08-02 11:39:32', '2026-08-05 08:35:46'),
(36, 'WC2608250A67', NULL, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', 'Tân Hưng', 'Quận 7', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 21435185.19, 8.00, 1714814.81, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 23250000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-27 15:43:30', NULL, '2026-08-25 09:44:24', '2026-08-27 08:43:30'),
(37, 'WC2608148585', NULL, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', 'Hải Châu 1', 'Hải Châu', 'Đà Nẵng', NULL, NULL, 'Đơn hàng mua sắm nội thất', 48287037.04, 8.00, 3859259.26, 9523.81, 7142.86, 200000.00, 'standard', 1, 150000.00, 50000.00, 0.00, 0.00, 0.00, 52450000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-16 10:39:26', NULL, '2026-08-14 11:36:03', '2026-08-16 03:39:26'),
(38, 'WC2608203E8E', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 30555555.56, 8.00, 2322222.22, 7142.86, 7142.86, 150000.00, 'standard', 1, 150000.00, 0.00, 0.00, 5.00, 1650000.00, 31650000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-21 15:57:50', NULL, '2026-08-20 04:24:14', '2026-08-21 08:57:50'),
(39, 'WC2608108408', NULL, 3, 'Lê Hoàng Cương', '0912345672', 'cuong@example.com', '56 Trần Phú', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 18148148.15, 8.00, 1451851.85, 9523.81, 0.00, 200000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 19800000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-11 10:35:29', NULL, '2026-08-10 02:55:50', '2026-08-11 03:35:29'),
(40, 'WC2608119F04', NULL, NULL, 'Đặng Phương Thảo', '0909000007', 'khach.thao@example.com', '70 Võ Văn Tần', 'Phường 15', 'Bình Thạnh', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 1712962.96, 8.00, 125925.93, 9523.81, 0.00, 200000.00, 'standard', 0, 0.00, 150000.00, 0.00, 0.00, 0.00, 1900000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-12 14:05:42', NULL, '2026-08-11 01:56:34', '2026-08-12 07:05:42'),
(41, 'WC26080146F1', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 16481481.48, 8.00, 1318518.52, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 17950000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-04 10:53:42', NULL, '2026-08-01 12:11:04', '2026-08-04 03:53:42'),
(42, 'WC260809608B', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 39814814.81, 8.00, 3025925.93, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 5.00, 2150000.00, 41000000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-08-10 13:49:23', NULL, '2026-08-09 07:37:13', '2026-08-10 06:49:23'),
(43, 'WC2608305EF9', NULL, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', 'Hải Châu 1', 'Hải Châu', 'Đà Nẵng', NULL, NULL, 'Khách đổi ý muốn chọn mẫu khác', 824074.07, 8.00, 65925.93, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 940000.00, 'cod', 'unpaid', 'cancelled', 0, NULL, 0, NULL, NULL, 'none', 'Khách đổi ý muốn chọn mẫu khác', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-08-30 08:18:05', '2026-08-30 08:18:05'),
(44, 'WC260909D30A', NULL, 1, 'Nguyễn Văn An', '0912345670', 'an@example.com', '12 Nguyễn Huệ', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 6851851.85, 8.00, 524518.52, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 100000.00, 0.00, 3.00, 219000.00, 7181000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-10 18:19:48', NULL, '2026-09-09 07:07:37', '2026-09-10 11:19:48'),
(45, 'WC2609071ED6', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 35648148.15, 8.00, 2844444.44, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 100000.00, 0.00, 0.00, 0.00, 38400000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-08 16:10:08', NULL, '2026-09-07 02:53:38', '2026-09-08 09:10:08'),
(46, 'WC26091082C4', NULL, NULL, 'Phạm Gia Khang', '0909000004', 'khach.khang@example.com', '40 Cao Thắng', 'Tân Hưng', 'Quận 7', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 11833333.33, 8.00, 946666.67, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 12830000.00, 'qr', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-12 17:41:35', NULL, '2026-09-10 05:58:48', '2026-09-12 10:41:35'),
(47, 'WC260907D574', NULL, NULL, 'Nguyễn Văn Toàn', '0909000001', 'khach.toan@example.com', '10 Phan Chu Trinh', 'Hải Châu 1', 'Hải Châu', 'Đà Nẵng', NULL, NULL, 'Đơn hàng mua sắm nội thất', 68518518.52, 8.00, 5481481.48, 9523.81, 0.00, 200000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 74200000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-08 15:29:31', NULL, '2026-09-07 01:52:12', '2026-09-08 08:29:31'),
(48, 'WC2609123660', NULL, NULL, 'Lê Thu Hằng', '0909000003', 'khach.hang@example.com', '30 Hàm Nghi', 'Phường 13', 'Quận 3', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 31759259.26, 8.00, 2540740.74, 7142.86, 0.00, 150000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 34450000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-13 13:09:44', NULL, '2026-09-12 03:33:02', '2026-09-13 06:09:44'),
(49, 'WC2609122040', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 29629629.63, 8.00, 2370370.37, 2380.95, 0.00, 50000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 32050000.00, 'bank', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-14 11:07:12', NULL, '2026-09-12 13:58:28', '2026-09-14 04:07:12'),
(50, 'WC260912BA85', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Phường 12', 'Quận 10', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 13703703.70, 8.00, 1096296.30, 4761.90, 0.00, 100000.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 14900000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-13 15:30:09', NULL, '2026-09-12 02:07:00', '2026-09-13 08:30:09'),
(51, 'WC260904807D', NULL, NULL, 'Trần Văn Tuấn', '0909000002', 'khach.tuan@example.com', '20 Lý Tự Trọng', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Đơn hàng mua sắm nội thất', 101574074.07, 8.00, 8125925.93, 0.00, 0.00, 0.00, 'standard', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 109700000.00, 'cod', 'paid', 'delivered', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, '2026-09-07 13:27:29', NULL, '2026-09-04 08:08:21', '2026-09-07 06:27:29'),
(52, 'WC2609137287', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 2175925.93, 8.00, 174074.07, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 2400000.00, 'cod', 'paid', 'shipping', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-13 12:49:33', '2026-09-13 12:49:33'),
(53, 'WC26091405EC', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 42500000.00, 8.00, 3400000.00, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 45950000.00, 'cod', 'paid', 'shipping', 0, NULL, 0, 'VTP00000000', 'Viettel Post', 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-14 05:39:30', '2026-09-14 05:39:30'),
(54, 'WC260914C3EC', NULL, 3, 'Lê Hoàng Cương', '0912345672', 'cuong@example.com', '56 Trần Phú', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 1712962.96, 8.00, 137037.04, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 1900000.00, 'cod', 'paid', 'preparing', 0, NULL, 0, NULL, NULL, 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-14 09:42:18', '2026-09-14 09:42:18'),
(55, 'WC2609156900', NULL, 5, 'Võ Thị Em', '0912345674', 'em@example.com', '345 CMT8', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 2129629.63, 8.00, 170370.37, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 2350000.00, 'cod', 'paid', 'confirmed', 0, NULL, 0, NULL, NULL, 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-15 04:13:33', '2026-09-15 04:13:33'),
(56, 'WC2609150B0E', NULL, 4, 'Phạm Minh Đức', '0912345673', 'duc@example.com', '89 Hai Bà Trưng', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 19907407.41, 8.00, 1592592.59, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 21550000.00, 'cod', 'unpaid', 'pending', 0, NULL, 0, NULL, NULL, 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-15 07:26:52', '2026-09-15 07:26:52'),
(57, 'WC2609154395', NULL, 2, 'Trần Thị Bích', '0912345671', 'bich@example.com', '234 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', NULL, NULL, 'Giao giờ hành chính', 2685185.19, 8.00, 214814.81, 0.00, 0.00, 50000.00, NULL, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 2950000.00, 'cod', 'unpaid', 'pending', 0, NULL, 0, NULL, NULL, 'none', NULL, NULL, 'green', 0, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '2026-09-15 02:00:15', '2026-09-15 02:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_cancel_requests`
--

CREATE TABLE `order_cancel_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL COMMENT 'Snapshot tên SP',
  `variant_name` varchar(120) DEFAULT NULL,
  `variant_value` varchar(120) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá bán từng sản phẩm (đã gồm VAT nếu áp)',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_name`, `variant_value`, `cover_image`, `quantity`, `price`, `vat_rate`, `subtotal`, `created_at`) VALUES
(1, 1, 7, 'Bàn trà gỗ sồi tròn', NULL, NULL, 'assets/images/shop/table-wood.jpg', 1, 3800000.00, 8.00, 3800000.00, '2026-04-18 03:58:59'),
(2, 1, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 'assets/images/shop/armchair-navy.jpg', 2, 6800000.00, 8.00, 13600000.00, '2026-04-18 03:58:59'),
(3, 1, 36, 'Đồng hồ treo tường gỗ sồi', NULL, NULL, 'assets/images/shop/shelf-minimal.jpg', 1, 1200000.00, 8.00, 1200000.00, '2026-04-18 03:58:59'),
(4, 2, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 'assets/images/shop/table-wood.jpg', 2, 8900000.00, 8.00, 17800000.00, '2026-04-13 01:35:51'),
(5, 3, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 'assets/images/shop/vanity-2.jpg', 2, 11900000.00, 8.00, 23800000.00, '2026-04-16 04:29:12'),
(6, 3, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 'assets/images/shop/sofa-set.jpg', 1, 65900000.00, 8.00, 65900000.00, '2026-04-16 04:29:12'),
(7, 3, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 'assets/images/shop/table-wood.jpg', 2, 8900000.00, 8.00, 17800000.00, '2026-04-16 04:29:12'),
(8, 4, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 'assets/images/shop/outdoor-1.jpg', 1, 12500000.00, 8.00, 12500000.00, '2026-04-27 06:43:23'),
(9, 4, 31, 'Sofa 3 chỗ da lộn sang trọng', NULL, NULL, 'assets/images/shop/sofa-grey.jpg', 1, 45900000.00, 8.00, 45900000.00, '2026-04-27 06:43:23'),
(10, 4, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 'assets/images/shop/vanity-2.jpg', 2, 11900000.00, 8.00, 23800000.00, '2026-04-27 06:43:23'),
(11, 5, 28, 'Sofa giường thông minh đa năng', NULL, NULL, 'assets/images/shop/sofa-set.jpg', 1, 11200000.00, 8.00, 11200000.00, '2026-04-22 09:33:52'),
(12, 5, 22, 'Đèn bàn gỗ sồi vintage', NULL, NULL, 'assets/images/shop/lamp-1.jpg', 2, 890000.00, 8.00, 1780000.00, '2026-04-22 09:33:52'),
(13, 5, 38, 'Bàn trang điểm gỗ óc chó 1m2', NULL, NULL, 'assets/images/shop/vanity-2.jpg', 1, 11900000.00, 8.00, 11900000.00, '2026-04-22 09:33:52'),
(14, 6, 47, 'Bộ bàn ghế xếp sân vườn', NULL, NULL, 'assets/images/shop/outdoor-4.jpg', 1, 4200000.00, 8.00, 4200000.00, '2026-04-21 11:43:15'),
(15, 6, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 2, 21500000.00, 8.00, 43000000.00, '2026-04-21 11:43:15'),
(16, 6, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 'assets/images/shop/bed-king.jpg', 1, 12500000.00, 8.00, 12500000.00, '2026-04-21 11:43:15'),
(17, 7, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 'assets/images/shop/bed-king.jpg', 1, 12500000.00, 8.00, 12500000.00, '2026-04-23 04:48:52'),
(18, 8, 33, 'Đèn chùm tre trang trí', NULL, NULL, 'assets/images/shop/lamp-2.jpg', 1, 2300000.00, 8.00, 2300000.00, '2026-05-17 07:44:34'),
(19, 9, 21, 'Kệ sách chữ L văn phòng', NULL, NULL, 'assets/images/shop/shelf-minimal.jpg', 1, 4900000.00, 8.00, 4900000.00, '2026-05-22 01:50:50'),
(20, 9, 41, 'Ghế bố dây choàng ban công', NULL, NULL, 'assets/images/shop/outdoor-3.jpg', 1, 1850000.00, 8.00, 1850000.00, '2026-05-22 01:50:50'),
(21, 9, 44, 'Đèn bàn sạc không dây sen đá', NULL, NULL, 'assets/images/shop/lamp-2.jpg', 1, 690000.00, 8.00, 690000.00, '2026-05-22 01:50:50'),
(22, 10, 10, 'Bàn ăn gấp thông minh', NULL, NULL, 'assets/images/shop/dining-modern.jpg', 2, 5200000.00, 8.00, 10400000.00, '2026-05-12 11:09:34'),
(23, 11, 46, 'Kệ gỗ nhiều ngăn Decalist', NULL, NULL, 'assets/images/shop/shelf-2.jpg', 1, 2600000.00, 8.00, 2600000.00, '2026-05-07 01:26:51'),
(24, 11, 25, 'Bàn trang điểm gương sáng 3 thùy', NULL, NULL, 'assets/images/shop/vanity-deco.jpg', 1, 5900000.00, 8.00, 5900000.00, '2026-05-07 01:26:51'),
(25, 12, 34, 'Bàn trà mặt kính chân sắt', NULL, NULL, 'assets/images/shop/table-glass.jpg', 2, 2700000.00, 8.00, 5400000.00, '2026-05-19 02:55:06'),
(26, 13, 42, 'Bàn ăn xếp hộp 1m4', NULL, NULL, 'assets/images/shop/dining-modern.jpg', 1, 4900000.00, 8.00, 4900000.00, '2026-05-08 10:03:17'),
(27, 13, 10, 'Bàn ăn gấp thông minh', NULL, NULL, 'assets/images/shop/dining-modern.jpg', 1, 5200000.00, 8.00, 5200000.00, '2026-05-08 10:03:17'),
(28, 14, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 'assets/images/shop/mirror-brick.jpg', 2, 3200000.00, 8.00, 6400000.00, '2026-05-21 10:06:06'),
(29, 14, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 'assets/images/shop/dining-wood.jpg', 1, 28500000.00, 8.00, 28500000.00, '2026-05-21 10:06:06'),
(30, 15, 40, 'Bàn làm việc đơn giản 120cm', NULL, NULL, 'assets/images/shop/desk-small.jpg', 1, 3400000.00, 8.00, 3400000.00, '2026-06-11 04:53:51'),
(31, 16, 30, 'Giường ngủ 1m2 cho bé', NULL, NULL, 'assets/images/shop/bed-linen.jpg', 2, 5900000.00, 8.00, 11800000.00, '2026-06-04 07:46:55'),
(32, 16, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 'assets/images/shop/wardrobe-2.jpg', 1, 16800000.00, 8.00, 16800000.00, '2026-06-04 07:46:55'),
(33, 17, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 'assets/images/shop/table-wood.jpg', 1, 8900000.00, 8.00, 8900000.00, '2026-06-10 07:15:29'),
(34, 17, 27, 'Ghế xếp gỗ du lịch', NULL, NULL, 'assets/images/shop/outdoor-2.jpg', 1, 590000.00, 8.00, 590000.00, '2026-06-10 07:15:29'),
(35, 17, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 'assets/images/shop/wardrobe-2.jpg', 2, 16800000.00, 8.00, 33600000.00, '2026-06-10 07:15:29'),
(36, 18, 50, 'Bàn làm việc cực gọn 90cm', NULL, NULL, 'assets/images/shop/desk-small.jpg', 1, 2800000.00, 8.00, 2800000.00, '2026-06-25 07:28:53'),
(37, 19, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 'assets/images/shop/armchair-navy.jpg', 2, 6800000.00, 8.00, 13600000.00, '2026-06-06 10:54:39'),
(38, 20, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 'assets/images/shop/bed-child.jpg', 1, 9800000.00, 8.00, 9800000.00, '2026-06-08 07:08:52'),
(39, 20, 45, 'Bàn trà gỗ óc chó hình chữ nhật', NULL, NULL, 'assets/images/shop/table-wood.jpg', 2, 8900000.00, 8.00, 17800000.00, '2026-06-08 07:08:52'),
(40, 21, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 7400000.00, 8.00, 7400000.00, '2026-06-18 06:42:55'),
(41, 22, 11, 'Bàn ăn mặt đá hoa cương', NULL, NULL, 'assets/images/shop/table-glass.jpg', 2, 18900000.00, 8.00, 37800000.00, '2026-06-04 10:24:05'),
(42, 22, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 'assets/images/shop/bed-king.jpg', 1, 12500000.00, 8.00, 12500000.00, '2026-06-04 10:24:05'),
(43, 23, 21, 'Kệ sách chữ L văn phòng', NULL, NULL, 'assets/images/shop/shelf-minimal.jpg', 1, 4900000.00, 8.00, 4900000.00, '2026-06-07 03:52:35'),
(44, 24, 29, 'Bàn làm việc bo góc chữ L', NULL, NULL, 'assets/images/shop/desk-2.jpg', 2, 8200000.00, 8.00, 16400000.00, '2026-07-21 02:01:59'),
(45, 25, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 'assets/images/shop/mirror-brick.jpg', 1, 3200000.00, 8.00, 3200000.00, '2026-07-23 10:44:27'),
(46, 26, 20, 'Kệ sách đứng gỗ thông', NULL, NULL, 'assets/images/shop/shelf-2.jpg', 1, 2900000.00, 8.00, 2900000.00, '2026-07-18 02:45:44'),
(47, 26, 40, 'Bàn làm việc đơn giản 120cm', NULL, NULL, 'assets/images/shop/desk-small.jpg', 1, 3400000.00, 8.00, 3400000.00, '2026-07-18 02:45:44'),
(48, 26, 36, 'Đồng hồ treo tường gỗ sồi', NULL, NULL, 'assets/images/shop/shelf-minimal.jpg', 2, 1200000.00, 8.00, 2400000.00, '2026-07-18 02:45:44'),
(49, 27, 25, 'Bàn trang điểm gương sáng 3 thùy', NULL, NULL, 'assets/images/shop/vanity-deco.jpg', 2, 5900000.00, 8.00, 11800000.00, '2026-07-07 12:25:51'),
(50, 27, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 2, 21500000.00, 8.00, 43000000.00, '2026-07-07 12:25:51'),
(51, 27, 7, 'Bàn trà gỗ sồi tròn', NULL, NULL, 'assets/images/shop/table-wood.jpg', 2, 3800000.00, 8.00, 7600000.00, '2026-07-07 12:25:51'),
(52, 28, 35, 'Kệ tivi cánh lùa hiện đại', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 2, 4600000.00, 8.00, 9200000.00, '2026-07-11 12:53:20'),
(53, 29, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 'assets/images/shop/sofa-set.jpg', 2, 65900000.00, 8.00, 131800000.00, '2026-07-06 11:44:59'),
(54, 30, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 'assets/images/shop/armchair-navy.jpg', 2, 6800000.00, 8.00, 13600000.00, '2026-07-24 01:58:44'),
(55, 31, 11, 'Bàn ăn mặt đá hoa cương', NULL, NULL, 'assets/images/shop/table-glass.jpg', 2, 18900000.00, 8.00, 37800000.00, '2026-07-09 01:34:10'),
(56, 31, 14, 'Giường ngủ gỗ sồi hộp 1m6', NULL, NULL, 'assets/images/shop/bed-king.jpg', 2, 12500000.00, 8.00, 25000000.00, '2026-07-09 01:34:10'),
(57, 32, 18, 'Kệ tivi gỗ sồi hiện đại', NULL, NULL, 'assets/images/shop/shelf-console.jpg', 2, 5800000.00, 8.00, 11600000.00, '2026-07-26 11:04:51'),
(58, 32, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 'assets/images/shop/table-2.jpg', 2, 3200000.00, 8.00, 6400000.00, '2026-07-26 11:04:51'),
(59, 32, 13, 'Bàn làm việc nâng hạ thông minh', NULL, NULL, 'assets/images/shop/desk-loft.jpg', 2, 8900000.00, 8.00, 17800000.00, '2026-07-26 11:04:51'),
(60, 33, 39, 'Giường ngủ 1m8 đầu giường liền tủ', NULL, NULL, 'assets/images/shop/bed-modern.jpg', 2, 19900000.00, 8.00, 39800000.00, '2026-08-08 11:50:24'),
(61, 34, 17, 'Tủ quần áo 6 cánh cao cấp', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 1, 38500000.00, 8.00, 38500000.00, '2026-08-17 04:51:32'),
(62, 34, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 7400000.00, 8.00, 7400000.00, '2026-08-17 04:51:32'),
(63, 35, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 'assets/images/shop/outdoor-1.jpg', 2, 12500000.00, 8.00, 25000000.00, '2026-08-02 11:39:32'),
(64, 35, 6, 'Ghế làm việc gỗ Sồi Bách', NULL, NULL, 'assets/images/shop/desk-office.jpg', 1, 2900000.00, 8.00, 2900000.00, '2026-08-02 11:39:32'),
(65, 35, 24, 'Gương đứng tròn WoodCon', NULL, NULL, 'assets/images/shop/mirror-brick.jpg', 1, 3200000.00, 8.00, 3200000.00, '2026-08-02 11:39:32'),
(66, 36, 23, 'Đèn sàn đứng 3 chân', NULL, NULL, 'assets/images/shop/lamp-3.jpg', 1, 2350000.00, 8.00, 2350000.00, '2026-08-25 09:44:24'),
(67, 36, 47, 'Bộ bàn ghế xếp sân vườn', NULL, NULL, 'assets/images/shop/outdoor-4.jpg', 2, 4200000.00, 8.00, 8400000.00, '2026-08-25 09:44:24'),
(68, 36, 12, 'Bàn làm việc gỗ sồi chân A', NULL, NULL, 'assets/images/shop/desk-modern.jpg', 2, 6200000.00, 8.00, 12400000.00, '2026-08-25 09:44:24'),
(69, 37, 1, 'Sofa bộ 3 chỗ WoodCon Classic', NULL, NULL, 'assets/images/shop/sofa-green.jpg', 2, 18900000.00, 8.00, 37800000.00, '2026-08-14 11:36:03'),
(70, 37, 37, 'Giá treo tường 3 tầng', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 1850000.00, 8.00, 1850000.00, '2026-08-14 11:36:03'),
(71, 37, 26, 'Bộ bàn ghế sân vườn 5 món', NULL, NULL, 'assets/images/shop/outdoor-1.jpg', 1, 12500000.00, 8.00, 12500000.00, '2026-08-14 11:36:03'),
(72, 38, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 'assets/images/shop/table-2.jpg', 2, 3200000.00, 8.00, 6400000.00, '2026-08-20 04:24:14'),
(73, 38, 16, 'Tủ quần áo 4 cánh gỗ sồi', NULL, NULL, 'assets/images/shop/wardrobe-2.jpg', 1, 16800000.00, 8.00, 16800000.00, '2026-08-20 04:24:14'),
(74, 38, 42, 'Bàn ăn xếp hộp 1m4', NULL, NULL, 'assets/images/shop/dining-modern.jpg', 2, 4900000.00, 8.00, 9800000.00, '2026-08-20 04:24:14'),
(75, 39, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 'assets/images/shop/bed-child.jpg', 2, 9800000.00, 8.00, 19600000.00, '2026-08-10 02:55:50'),
(76, 40, 37, 'Giá treo tường 3 tầng', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 1850000.00, 8.00, 1850000.00, '2026-08-11 01:56:34'),
(77, 41, 3, 'Sofa văng 2 chỗ Mây Làm', NULL, NULL, 'assets/images/shop/sofa-beige.jpg', 2, 8900000.00, 8.00, 17800000.00, '2026-08-01 12:11:04'),
(78, 42, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 2, 21500000.00, 8.00, 43000000.00, '2026-08-09 07:37:13'),
(79, 43, 22, 'Đèn bàn gỗ sồi vintage', NULL, NULL, 'assets/images/shop/lamp-1.jpg', 1, 890000.00, 8.00, 890000.00, '2026-08-30 08:18:05'),
(80, 44, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 7400000.00, 8.00, 7400000.00, '2026-09-09 07:07:37'),
(81, 45, 17, 'Tủ quần áo 6 cánh cao cấp', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 1, 38500000.00, 8.00, 38500000.00, '2026-09-07 02:53:38'),
(82, 46, 27, 'Ghế xếp gỗ du lịch', NULL, NULL, 'assets/images/shop/outdoor-2.jpg', 2, 590000.00, 8.00, 1180000.00, '2026-09-10 05:58:48'),
(83, 46, 18, 'Kệ tivi gỗ sồi hiện đại', NULL, NULL, 'assets/images/shop/shelf-console.jpg', 2, 5800000.00, 8.00, 11600000.00, '2026-09-10 05:58:48'),
(84, 47, 21, 'Kệ sách chữ L văn phòng', NULL, NULL, 'assets/images/shop/shelf-minimal.jpg', 1, 4900000.00, 8.00, 4900000.00, '2026-09-07 01:52:12'),
(85, 47, 8, 'Bàn trà gỗ công nghiệp hiện đại', NULL, NULL, 'assets/images/shop/table-2.jpg', 1, 3200000.00, 8.00, 3200000.00, '2026-09-07 01:52:12'),
(86, 47, 49, 'Sofa bộ 7 món phòng khách cao cấp', NULL, NULL, 'assets/images/shop/sofa-set.jpg', 1, 65900000.00, 8.00, 65900000.00, '2026-09-07 01:52:12'),
(87, 48, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 'assets/images/shop/dining-wood.jpg', 1, 28500000.00, 8.00, 28500000.00, '2026-09-12 03:33:02'),
(88, 48, 20, 'Kệ sách đứng gỗ thông', NULL, NULL, 'assets/images/shop/shelf-2.jpg', 2, 2900000.00, 8.00, 5800000.00, '2026-09-12 03:33:02'),
(89, 49, 12, 'Bàn làm việc gỗ sồi chân A', NULL, NULL, 'assets/images/shop/desk-modern.jpg', 2, 6200000.00, 8.00, 12400000.00, '2026-09-12 13:58:28'),
(90, 49, 15, 'Giường ngủ tầng trẻ em', NULL, NULL, 'assets/images/shop/bed-child.jpg', 2, 9800000.00, 8.00, 19600000.00, '2026-09-12 13:58:28'),
(91, 50, 32, 'Kệ tivi kết hợp giá trang trí', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 2, 7400000.00, 8.00, 14800000.00, '2026-09-12 02:07:00'),
(92, 51, 9, 'Bàn ăn gỗ óc chó 4-6 người', NULL, NULL, 'assets/images/shop/dining-wood.jpg', 2, 28500000.00, 8.00, 57000000.00, '2026-09-04 08:08:21'),
(93, 51, 31, 'Sofa 3 chỗ da lộn sang trọng', NULL, NULL, 'assets/images/shop/sofa-grey.jpg', 1, 45900000.00, 8.00, 45900000.00, '2026-09-04 08:08:21'),
(94, 51, 4, 'Ghế bành thư giãn Nam Phương', NULL, NULL, 'assets/images/shop/armchair-navy.jpg', 1, 6800000.00, 8.00, 6800000.00, '2026-09-04 08:08:21'),
(95, 52, 23, 'Đèn sàn đứng 3 chân', NULL, NULL, 'assets/images/shop/lamp-3.jpg', 1, 2350000.00, 8.00, 2350000.00, '2026-09-13 12:49:33'),
(96, 53, 31, 'Sofa 3 chỗ da lộn sang trọng', NULL, NULL, 'assets/images/shop/sofa-grey.jpg', 1, 45900000.00, 8.00, 45900000.00, '2026-09-14 05:39:30'),
(97, 54, 37, 'Giá treo tường 3 tầng', NULL, NULL, 'assets/images/shop/shelf-wall.jpg', 1, 1850000.00, 8.00, 1850000.00, '2026-09-14 09:42:18'),
(98, 55, 33, 'Đèn chùm tre trang trí', NULL, NULL, 'assets/images/shop/lamp-2.jpg', 1, 2300000.00, 8.00, 2300000.00, '2026-09-15 04:13:33'),
(99, 56, 43, 'Tủ quần áo cánh kính 2 buồng', NULL, NULL, 'assets/images/shop/wardrobe-3.jpg', 1, 21500000.00, 8.00, 21500000.00, '2026-09-15 07:26:52'),
(100, 57, 6, 'Ghế làm việc gỗ Sồi Bách', NULL, NULL, 'assets/images/shop/desk-office.jpg', 1, 2900000.00, 8.00, 2900000.00, '2026-09-15 02:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL,
  `purpose` varchar(30) NOT NULL DEFAULT 'order',
  `otp` varchar(6) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `expired_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL COMMENT 'Module: staffs, orders, products...',
  `action` varchar(50) NOT NULL COMMENT 'view, add, edit, delete, export...',
  `label` varchar(150) NOT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `module`, `action`, `label`, `is_sensitive`) VALUES
(1, 'dashboard', 'view', 'Xem Dashboard', 0),
(2, 'orders', 'view', 'Xem đơn hàng', 0),
(3, 'orders', 'add', 'Tạo đơn hàng', 0),
(4, 'orders', 'edit', 'Cập nhật đơn hàng', 0),
(5, 'orders', 'delete', 'Xóa đơn hàng', 0),
(6, 'orders', 'export', 'Xuất Excel đơn hàng', 0),
(7, 'invoices', 'view', 'Xem hóa đơn', 0),
(8, 'invoices', 'export', 'Xuất hóa đơn', 0),
(9, 'products', 'view', 'Xem sản phẩm', 0),
(10, 'products', 'add', 'Thêm sản phẩm', 0),
(11, 'products', 'edit', 'Sửa sản phẩm', 0),
(12, 'products', 'delete', 'Xóa sản phẩm', 0),
(13, 'categories', 'view', 'Xem danh mục', 0),
(14, 'categories', 'add', 'Thêm danh mục', 0),
(15, 'categories', 'edit', 'Sửa danh mục', 0),
(16, 'categories', 'delete', 'Xóa danh mục', 0),
(17, 'staffs', 'view', 'Xem nhân sự', 0),
(18, 'staffs', 'add', 'Tạo tài khoản nhân viên', 0),
(19, 'staffs', 'edit', 'Sửa nhân viên / phân quyền', 0),
(20, 'staffs', 'delete', 'Xóa nhân viên', 1),
(21, 'roles', 'view', 'Xem vai trò & phân quyền', 1),
(22, 'roles', 'edit', 'Chỉnh phân quyền', 1),
(23, 'reports', 'view', 'Xem báo cáo', 0),
(24, 'reports', 'export', 'Xuất báo cáo', 0),
(25, 'audit', 'view', 'Xem nhật ký giám sát', 1),
(26, 'settings', 'edit', 'Cấu hình hệ thống', 1),
(27, 'customers', 'view', 'Xem khách hàng', 0),
(28, 'customers', 'edit', 'Khóa / mở khóa khách hàng', 0),
(29, 'customers', 'export', 'Xuất danh sách khách hàng', 0),
(30, 'vouchers', 'view', 'Xem mã khuyến mãi', 0),
(31, 'vouchers', 'add', 'Thêm mã khuyến mãi', 0),
(32, 'vouchers', 'edit', 'Sửa mã khuyến mãi', 0),
(33, 'vouchers', 'delete', 'Xóa mã khuyến mãi', 0),
(34, 'banners', 'view', 'Xem banner', 0),
(35, 'banners', 'add', 'Thêm banner', 0),
(36, 'banners', 'edit', 'Sửa banner', 0),
(37, 'banners', 'delete', 'Xóa banner', 0),
(38, 'news', 'view', 'Xem bài viết', 0),
(39, 'news', 'add', 'Thêm bài viết', 0),
(40, 'news', 'edit', 'Sửa bài viết', 0),
(41, 'news', 'delete', 'Xóa bài viết', 0),
(42, 'brands', 'view', 'Xem thương hiệu', 0),
(43, 'brands', 'add', 'Thêm thương hiệu', 0),
(44, 'brands', 'edit', 'Sửa thương hiệu', 0),
(45, 'brands', 'delete', 'Xóa thương hiệu', 0),
(46, 'taxes', 'view', 'Xem mức thuế', 0),
(47, 'taxes', 'add', 'Thêm mức thuế', 0),
(48, 'taxes', 'edit', 'Sửa mức thuế', 0),
(49, 'taxes', 'delete', 'Xóa mức thuế', 0),
(50, 'warranties', 'view', 'Xem phiếu bảo hành', 0),
(51, 'warranties', 'edit', 'Cập nhật phiếu bảo hành', 0),
(52, 'contacts', 'view', 'Xem liên hệ', 0),
(53, 'contacts', 'edit', 'Xử lý liên hệ', 0),
(54, 'reviews', 'view', 'Xem đánh giá', 0),
(55, 'reviews', 'edit', 'Duyệt / ẩn đánh giá', 0),
(85, 'warranties', 'export', 'Xuất phiếu bảo hành', 0),
(86, 'approvals', 'export', 'Xuất yêu cầu hủy / hoàn tiền', 0);

-- --------------------------------------------------------

--
-- Table structure for table `points_transactions`
--

CREATE TABLE `points_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `points_change` int(11) NOT NULL,
  `type` enum('earn','spend','expire','admin_adjust') NOT NULL DEFAULT 'earn',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `brand_id` int(10) UNSIGNED DEFAULT NULL,
  `tax_rate_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `tu_khoa_tim_kiem` varchar(255) DEFAULT NULL COMMENT 'Chuẩn hoá không dấu để tìm kiếm tiếng Việt',
  `summary` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(120) DEFAULT NULL COMMENT 'Chất liệu',
  `dimension` varchar(120) DEFAULT NULL COMMENT 'Kích thước tổng thể (cm)',
  `warranty_months` int(11) NOT NULL DEFAULT 12 COMMENT 'Số tháng bảo hành',
  `price` decimal(15,2) NOT NULL COMMENT 'Giá bán',
  `sale_price` decimal(15,2) DEFAULT NULL COMMENT 'Giá khuyến mãi',
  `cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá vốn',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `sold_count` int(11) NOT NULL DEFAULT 0,
  `weight_kg` decimal(6,2) NOT NULL DEFAULT 1.00,
  `dim_l` decimal(8,2) DEFAULT NULL COMMENT 'Dài cm (tính cước ship)',
  `dim_w` decimal(8,2) DEFAULT NULL COMMENT 'Rộng cm',
  `dim_h` decimal(8,2) DEFAULT NULL COMMENT 'Cao cm',
  `ship_supports_type1` tinyint(1) DEFAULT NULL COMMENT 'NULL theo danh mục, 1 có, 0 không',
  `ship_supports_type2` tinyint(1) DEFAULT NULL COMMENT 'NULL theo danh mục, 1 có, 0 không',
  `install_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Phí lắp đặt/sản phẩm',
  `cover_image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_new` tinyint(1) NOT NULL DEFAULT 0,
  `is_best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `tax_rate_id`, `name`, `slug`, `sku`, `tu_khoa_tim_kiem`, `summary`, `description`, `material`, `dimension`, `warranty_months`, `price`, `sale_price`, `cost`, `quantity`, `sold_count`, `weight_kg`, `dim_l`, `dim_w`, `dim_h`, `ship_supports_type1`, `ship_supports_type2`, `install_fee`, `cover_image`, `video_url`, `status`, `is_featured`, `is_new`, `is_best_seller`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 3, 'Sofa bộ 3 chỗ WoodCon Classic', 'sofa-bo-3-cho-woodcon-classic', 'WC-SF-001', NULL, 'Sofa bộ 3 chỗ gỗ sồi kết hợp nệm mút cao cấp, phong cách tối giản Scandinavian.', 'Sofa bộ 3 chỗ được thiết kế khung gỗ sồi chắc chắn, nệm mút cold-pressed 40kg/m3 bọc vải linen cao cấp, chân gỗ vát nhọn phong cách Bắc Âu. Kích thước phù hợp phòng khách 15-25m2.', 'Gỗ sồi + vải linen + mút HR', '210 x 90 x 85', 24, 18900000.00, 15900000.00, 10500000.00, 12, 86, 48.00, 220.00, 100.00, 95.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-green.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(2, 7, 2, 3, 'Sofa góc chữ L Gỗ Óc Chó', 'sofa-goc-chu-l-go-oc-cho', 'WC-SF-002', NULL, 'Sofa góc chữ L gỗ óc chó, thiết kế hiện đại ôm người, đệm bông tơi.', 'Sofa góc chữ L với khung gỗ óc chó nguyên khối, góc bo mềm mại, đệm ngồi dày 18cm. Phù hợp gia đình đông người và không gian phòng khách rộng.', 'Gỗ óc chó + vải nỉ cao cấp', '280 x 180 x 85', 24, 32900000.00, 0.00, 21800000.00, 6, 34, 96.00, 300.00, 200.00, 95.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-dark.jpg', NULL, 1, 1, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(3, 7, 1, 3, 'Sofa văng 2 chỗ Mây Làm', 'sofa-vang-2-cho-may-lam', 'WC-SF-003', NULL, 'Sofa văng 2 chỗ chất liệu mây tự nhiên, mát mẻ cho không gian nhiệt đới.', 'Sofa 2 chỗ đan bằng mây tự nhiên lồng gỗ sồi, phù hợp ban công, sân hiên hoặc phòng khách nhỏ. Khung chịu lực tốt, chịu ẩm tốt sau khi xử lý chống mối mọt.', 'Mây tự nhiên + gỗ sồi', '160 x 80 x 78', 12, 8900000.00, 7900000.00, 4900000.00, 18, 41, 22.00, 170.00, 90.00, 85.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-beige.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(4, 8, 8, 3, 'Ghế bành thư giãn Nam Phương', 'ghe-banh-thu-gian-nam-phuong', 'WC-GH-001', NULL, 'Ghế bành gỗ sồi bọc nỉ, tựa đầu chỉnh góc, phong cách mid-century.', 'Ghế bành thư giãn kiểu mid-century với khung gỗ sồi cong, bọc vải nỉ 2 lớp, tựa đầu có thể chỉnh 3 góc. Đế lắc nhẹ giúp thư giãn tối đa.', 'Gỗ sồi + vải nỉ', '75 x 80 x 95', 18, 6800000.00, 0.00, 3900000.00, 25, 63, 28.00, 80.00, 90.00, 100.00, NULL, NULL, 0.00, 'assets/images/shop/armchair-navy.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(5, 8, 5, 3, 'Ghế bành nỉ xám Trúc Lâm', 'ghe-banh-ni-xam-truc-lam', 'WC-GH-002', NULL, 'Ghế bành màu xám trung tính, khung gỗ cao su cứng cáp.', 'Ghế bành hiện đại, khung gỗ cao su phủ sơn PU chống ẩm, bọc nỉ xám dễ phối nội thất. Lý tưởng cho góc đọc sách.', 'Gỗ cao su + vải nỉ', '68 x 75 x 88', 12, 4900000.00, 4400000.00, 2600000.00, 30, 28, 24.00, 70.00, 80.00, 92.00, NULL, NULL, 0.00, 'assets/images/shop/lounge-chair.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(6, 9, 8, 3, 'Ghế làm việc gỗ Sồi Bách', 'ghe-lam-viec-go-soi-bach', 'WC-GH-003', NULL, 'Ghế làm việc gỗ sồi nguyên khối, êm ái, hỗ trợ lưng tốt.', 'Ghế làm việc cố định khung gỗ sồi, mặt ngồi bọc nỉ dày 8cm, tựa lưng nghiêng 100 độ. Phù hợp bàn làm việc tại nhà.', 'Gỗ sồi + vải nỉ', '60 x 62 x 95', 24, 2900000.00, 0.00, 1500000.00, 40, 55, 15.00, 65.00, 70.00, 100.00, NULL, NULL, 0.00, 'assets/images/shop/desk-office.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(7, 10, 1, 3, 'Bàn trà gỗ sồi tròn', 'ban-tra-go-soi-tron', 'WC-BT-001', NULL, 'Bàn trà tròn gỗ sồi kết hợp mặt đá, đường kính 90cm.', 'Bàn trà mặt đá nhân tạo vân đá tự nhiên kết hợp chân sắt phủ đen, bo tròn an toàn cho gia đình có trẻ nhỏ.', 'Gỗ sồi + đá nhân tạo', '90 x 90 x 45', 12, 3800000.00, 3300000.00, 1900000.00, 22, 74, 20.00, 95.00, 95.00, 50.00, NULL, NULL, 0.00, 'assets/images/shop/table-wood.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(8, 10, 2, 3, 'Bàn trà gỗ công nghiệp hiện đại', 'ban-tra-go-cong-nghiep-hien-dai', 'WC-BT-002', NULL, 'Bàn trà chữ nhật 120cm, ngăn kéo tiện lợi, màu óc chó.', 'Bàn trà chữ nhật kích thước lớn với ngăn kéo 2 tầng, phong cách công nghiệp kết hợp ấm cúng.', 'MDF cao cấp phủ veneer óc chó', '120 x 60 x 40', 12, 3200000.00, 0.00, 1700000.00, 35, 48, 18.00, 125.00, 65.00, 45.00, NULL, NULL, 0.00, 'assets/images/shop/table-2.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(9, 11, 4, 3, 'Bàn ăn gỗ óc chó 4-6 người', 'ban-an-go-oc-cho-4-6-nguoi', 'WC-BA-001', NULL, 'Bàn ăn mặt gỗ óc chó dày 4cm, chân vững chãi, phong cách sang trọng.', 'Bàn ăn mặt gỗ óc chó nguyên tấm dày 4cm xử lý chống cong vênh, chân bàn thiết kế mở rộng chịu lực tốt. Kèm 6 ghế ăn cùng bộ.', 'Gỗ óc chó', '180 x 90 x 75', 24, 28500000.00, 25500000.00, 17000000.00, 8, 21, 85.00, 190.00, 95.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/dining-wood.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(10, 11, 1, 3, 'Bàn ăn gấp thông minh', 'ban-an-gap-thong-minh', 'WC-BA-002', NULL, 'Bàn ăn gấp gọn tiết kiệm không gian, phù hợp căn hộ nhỏ.', 'Bàn ăn có cơ chế gấp 2 cánh linh hoạt, khi mở rộng lên 160cm phục vụ 6 người, gấp lại chỉ 60cm. Tích hợp ngăn để đồ.', 'Gỗ sồi + MDF', '120 x 80 x 75', 12, 5200000.00, 0.00, 2900000.00, 15, 37, 25.00, 170.00, 90.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/dining-modern.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(11, 11, 6, 3, 'Bàn ăn mặt đá hoa cương', 'ban-an-mat-da-hoa-cuong', 'WC-BA-003', NULL, 'Bàn ăn mặt đá granite tự nhiên, chân gỗ sồi, sang trọng vượt thời gian.', 'Mặt bàn đá granite tự nhiên chống trầy, chịu nhiệt, chân bàn gỗ sồi vững chắc. Kích thước chuẩn cho 6-8 người.', 'Đá granite + gỗ sồi', '200 x 100 x 76', 24, 18900000.00, 0.00, 12300000.00, 4, 9, 120.00, 210.00, 105.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/table-glass.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(12, 12, 8, 3, 'Bàn làm việc gỗ sồi chân A', 'ban-lam-viec-go-soi-chan-a', 'WC-BL-001', NULL, 'Bàn làm việc gỗ sồi chân chữ A hiện đại, rộng rãi cho home-office.', 'Bàn làm việc mặt gỗ sồi dày 3cm, chân chữ A mở rộng, kèm hộc tủ 2 ngăn kéo phía dưới. Kích thước lớn thoải mái đặt laptop và sách.', 'Gỗ sồi', '140 x 70 x 75', 24, 6200000.00, 5600000.00, 3300000.00, 26, 44, 32.00, 145.00, 75.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/desk-modern.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(13, 12, 5, 3, 'Bàn làm việc nâng hạ thông minh', 'ban-lam-viec-nang-ha-thong-minh', 'WC-BL-002', NULL, 'Bàn nâng hạ 2 chế độ ngồi/đứng, khung thép chắc chắn.', 'Bàn làm việc điều chỉnh độ cao điện tử (hoặc tay quay) giúp làm việc đứng/ngồi linh hoạt, khung thép chịu lực 80kg.', 'Khung thép + mặt MDF', '120 x 60 x 65-125', 24, 8900000.00, 0.00, 5200000.00, 10, 19, 38.00, 125.00, 65.00, 130.00, NULL, NULL, 0.00, 'assets/images/shop/desk-loft.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(14, 13, 1, 3, 'Giường ngủ gỗ sồi hộp 1m6', 'giuong-ngu-go-soi-hop-1m6', 'WC-GN-001', NULL, 'Giường hộp gỗ sồi 1m6, đầu giường bọc nỉ mềm, hộc chứa đồ.', 'Giường hộp 1m6 với hộc to chứa đồ dưới giường tiện lợi, đầu giường bọc nỉ êm ái, khung gỗ sồi chắc chắn chịu tải tốt.', 'Gỗ sồi + nỉ', '200 x 166 x 42', 24, 12500000.00, 0.00, 7200000.00, 14, 26, 95.00, 210.00, 180.00, 45.00, NULL, NULL, 0.00, 'assets/images/shop/bed-king.jpg', NULL, 1, 1, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(15, 13, 6, 3, 'Giường ngủ tầng trẻ em', 'giuong-ngu-tang-tre-em', 'WC-GN-002', NULL, 'Giường tầng kết hợp bàn học, an toàn, sinh động cho bé.', 'Giường 2 tầng gỗ cao su bo góc an toàn, tầng dưới kết hợp bàn học và giá sách, cầu thang có ngăn kéo.', 'Gỗ cao su + các lớp phủ chống ẩm', '200 x 180 x 160', 24, 9800000.00, 8800000.00, 5600000.00, 9, 31, 78.00, 210.00, 190.00, 170.00, NULL, NULL, 0.00, 'assets/images/shop/bed-child.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(16, 14, 1, 3, 'Tủ quần áo 4 cánh gỗ sồi', 'tu-quan-ao-4-canh-go-soi', 'WC-TQ-001', NULL, 'Tủ quần áo 4 cánh 2 buồng, khoang treo và ngăn kéo đầy đủ.', 'Tủ 4 cánh 2 buồng: khoang treo quần, khoang treo áo dài, ngăn kéo đựng đồ nhỏ, gương soi bên trong. Tay nắm kim loại sáng bóng.', 'Gỗ sồi + MDF sơn PU', '200 x 180 x 220', 24, 16800000.00, 14800000.00, 9600000.00, 7, 18, 110.00, 190.00, 185.00, 225.00, NULL, NULL, 0.00, 'assets/images/shop/wardrobe-2.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(17, 14, 8, 3, 'Tủ quần áo 6 cánh cao cấp', 'tu-quan-ao-6-canh-cao-cap', 'WC-TQ-002', NULL, 'Tủ 6 cánh gỗ óc chó, khoang trượt, thiết kế đẳng cấp phòng ngủ lớn.', 'Tủ 6 cánh gỗ óc chó với hệ ray trượt êm ái, chống ẩm, khoang rộng thoáng cho phòng ngủ lớn. Cánh tủ mở trượt tiết kiệm diện tích.', 'Gỗ óc chó', '240 x 60 x 240', 36, 38500000.00, 0.00, 23500000.00, 3, 12, 180.00, 245.00, 65.00, 245.00, NULL, NULL, 0.00, 'assets/images/shop/wardrobe-3.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(18, 15, 3, 3, 'Kệ tivi gỗ sồi hiện đại', 'ke-tivi-go-soi-hien-dai', 'WC-KT-001', NULL, 'Kệ tivi dài 180cm, 3 ngăn kệ mở thông minh.', 'Kệ tivi phòng khách với 3 ngăn kệ mở, 2 ngăn kéo, khoang đặt loa/ampli, lỗ luồn dây gọn gàng.', 'Gỗ sồi + MDF', '180 x 45 x 50', 12, 5800000.00, 0.00, 3100000.00, 20, 52, 40.00, 185.00, 50.00, 55.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-console.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(19, 15, 1, 3, 'Kệ tivi thả nổi', 'ke-tivi-tha-noi', 'WC-KT-002', NULL, 'Kệ tivi treo tường thả nổi gọn gàng hiện đại.', 'Kệ tivi thả nổi lắp âm tường, dây điện giấu kín, phong cách tối giản, gỗ MDF phủ veneer vân gỗ tự nhiên.', 'MDF veneer', '150 x 40 x 30', 12, 3900000.00, 3400000.00, 2000000.00, 18, 33, 25.00, 155.00, 45.00, 35.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-wall.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(20, 16, 7, 3, 'Kệ sách đứng gỗ thông', 'ke-sach-dung-go-thong', 'WC-KS-001', NULL, 'Kệ sách 5 tầng gỗ thông nguyên khối, tải trọng tốt.', 'Kệ sách 5 ngăn gỗ thông, thanh ngang chịu lực, có khoang kín để đồ. Phù hợp thư viện gia đình, phòng làm việc.', 'Gỗ thông', '90 x 35 x 200', 12, 2900000.00, 0.00, 1400000.00, 28, 47, 32.00, 95.00, 40.00, 205.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-2.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(21, 16, 5, 3, 'Kệ sách chữ L văn phòng', 'ke-sach-chu-l-van-phong', 'WC-KS-002', NULL, 'Kệ sách chữ L kết hợp bàn làm việc, tiết kiệm không gian.', 'Kệ sách kết hợp góc làm việc hình chữ L, tối ưu diện tích góc phòng, mặt gỗ chịu lực tốt, kích thước linh hoạt.', 'Gỗ sồi + MDF', '150 x 60 x 60', 12, 4900000.00, 0.00, 2700000.00, 15, 23, 40.00, 160.00, 65.00, 65.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-minimal.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(22, 17, 2, 3, 'Đèn bàn gỗ sồi vintage', 'den-ban-go-soi-vintage', 'WC-DB-001', NULL, 'Đèn bàn phong cách vintage, chụp vải lanh, nguồn sáng ấm.', 'Đèn bàn với thân gỗ sồi tiện tròn, chụp vải lanh, dây bật tuýt nhiều nấc sáng, ánh sáng vàng ấm 2700K thư giãn.', 'Gỗ sồi + vải lanh', '28 x 28 x 45', 6, 890000.00, 790000.00, 390000.00, 50, 88, 3.00, 30.00, 30.00, 50.00, NULL, NULL, 0.00, 'assets/images/shop/lamp-1.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(23, 17, 4, 3, 'Đèn sàn đứng 3 chân', 'den-san-dung-3-chan', 'WC-DS-001', NULL, 'Đèn sàn đứng chân gỗ, chụp cong hiện đại, ánh sáng dịu.', 'Đèn sàn cao cấp với chân đế gỗ sồi 3 chân, chụp đèn cong hướng ánh sáng, phù hợp góc đọc sách, kênh màu trung tính.', 'Gỗ sồi + kim loại', '35 x 35 x 150', 12, 2350000.00, 0.00, 1200000.00, 22, 41, 9.00, 40.00, 40.00, 155.00, NULL, NULL, 0.00, 'assets/images/shop/lamp-3.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(24, 18, 3, 3, 'Gương đứng tròn WoodCon', 'guong-dung-tron-woodcon', 'WC-GK-001', NULL, 'Gương đứng khung gỗ sồi tròn tiện, phong cách nghệ thuật.', 'Gương đứng tròn khung gỗ sồi tiện, mặt gương phủ chống mờ, có chân đứng phía sau. Điểm nhấn cho phòng thay đồ hoặc phòng ngủ.', 'Gỗ sồi + gương', '60 x 60 x 170', 12, 3200000.00, 0.00, 1700000.00, 16, 29, 15.00, 65.00, 65.00, 175.00, NULL, NULL, 0.00, 'assets/images/shop/mirror-brick.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(25, 19, 1, 3, 'Bàn trang điểm gương sáng 3 thùy', 'ban-trang-diem-guong-sang-3-thuy', 'WC-BTD-001', NULL, 'Bàn trang điểm kèm gương 3 thùy, ngăn kéo, đèn LED viền.', 'Bàn trang điểm với gương 3 thùy có đèn LED, 3 ngăn kéo, mặt kính cường lực, chân gỗ sồi tinh tế.', 'Gỗ sồi + kính cường lực', '100 x 45 x 75', 12, 5900000.00, 5200000.00, 3300000.00, 13, 36, 28.00, 105.00, 50.00, 145.00, NULL, NULL, 0.00, 'assets/images/shop/vanity-deco.jpg', NULL, 1, 1, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(26, 20, 2, 3, 'Bộ bàn ghế sân vườn 5 món', 'bo-ban-ghe-san-vuon-5-mon', 'WC-NT-001', NULL, 'Bộ bàn ghế 5 món gỗ kỹ thuật chống nước cho sân vườn.', 'Bộ bàn ghế ngoài trời gỗ kỹ thuật composite chống thấm, chống tia UV, gồm 1 bàn + 4 ghế. Phù hợp sân vườn, ban công.', 'Gỗ composite', '180 x 90 x 75', 24, 12500000.00, 10900000.00, 6900000.00, 8, 27, 150.00, 190.00, 95.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/outdoor-1.jpg', NULL, 1, 1, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(27, 6, 3, 3, 'Ghế xếp gỗ du lịch', 'ghe-xep-go-du-lich', 'WC-NT-002', NULL, 'Ghế xếp gỗ sồi gấp gọn, mang đi tiện lợi cho dã ngoại.', 'Ghế xếp gỗ sồi trọng tải 120kg, gấp gọn chỉ 15cm, phù hợp đi cắm trại, dã ngoại, sân vườn.', 'Gỗ sồi', '48 x 45 x 80', 6, 590000.00, 490000.00, 260000.00, 60, 112, 5.00, 50.00, 15.00, 85.00, NULL, NULL, 0.00, 'assets/images/shop/outdoor-2.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(28, 1, 1, 3, 'Sofa giường thông minh đa năng', 'sofa-giuong-thong-minh-da-nang', 'WC-SF-004', NULL, 'Sofa chuyển thành giường, tiết kiệm không gian cho phòng nhỏ.', 'Sofa giường 2 trong 1: ban ngày là sofa chỗ ngồi thoải mái, ban đêm gấp thành giường 1m4. Mút dày chắc chắn, bọc vải.', 'Gỗ + vải nỉ', '200 x 95 x 90', 24, 11200000.00, 0.00, 6200000.00, 11, 58, 55.00, 205.00, 100.00, 95.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-set.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(29, 2, 7, 3, 'Bàn làm việc bo góc chữ L', 'ban-lam-viec-bo-goc-chu-l', 'WC-BL-003', NULL, 'Bàn bo góc chữ L gỗ thông tự nhiên, không gian làm việc rộng.', 'Bàn chữ L thông minh sử dụng tối đa góc phòng, mặt gỗ thông sang trọng, khoang dưới thoáng đãng, giá để chân đầy đủ.', 'Gỗ thông', '160 x 160 x 75', 12, 8200000.00, 7300000.00, 4400000.00, 14, 32, 52.00, 170.00, 170.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/desk-2.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(30, 3, 8, 3, 'Giường ngủ 1m2 cho bé', 'giuong-ngu-1m2-cho-be', 'WC-GN-003', NULL, 'Giường gỗ 1m2 có thanh chắn an toàn, màu pastel dễ thương.', 'Giường ngủ 1m2 cho bé với thanh chắn an toàn 2 bên, gỗ cao su sơn phủ an toàn cho trẻ, màu pastel tươi sáng.', 'Gỗ cao su', '200 x 122 x 100', 12, 5900000.00, 0.00, 3300000.00, 20, 26, 45.00, 210.00, 130.00, 105.00, NULL, NULL, 0.00, 'assets/images/shop/bed-linen.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(31, 7, 3, 3, 'Sofa 3 chỗ da lộn sang trọng', 'sofa-3-cho-da-lon-sang-trong', 'WC-SF-005', NULL, 'Sofa da lộn bò Bắc Âu, khung gỗ bạch đàn, đẳng cấp không gian.', 'Sofa da lộng 3 chỗ sang trọng, khung bạch đàn, da lộn MPI nhập khẩu, đệm vi sinh định hình.', 'Gỗ bạch đàn + da lộn', '220 x 95 x 80', 36, 45900000.00, 39900000.00, 31000000.00, 4, 11, 60.00, 230.00, 100.00, 85.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-grey.jpg', NULL, 1, 1, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(32, 4, 1, 3, 'Kệ tivi kết hợp giá trang trí', 'ke-tivi-ket-hop-gia-trang-tri', 'WC-KT-003', NULL, 'Kệ tivi đa năng với các ngăn để đồ trang trí, xanh tươi mát.', 'Kệ tivi thiết kế đa năng kết hợp giá trang trí cây xanh, sách, lọ hoa. Tông màu gỗ sáng mang không khí tươi mới.', 'MDF veneer gỗ công nghiệp', '200 x 45 x 190', 12, 7400000.00, 0.00, 4100000.00, 16, 39, 55.00, 205.00, 50.00, 195.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-wall.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(33, 5, 2, 3, 'Đèn chùm tre trang trí', 'den-chum-tre-trang-tri', 'WC-DC-001', NULL, 'Đèn chùm đan tre thủ công, hơi thở thiên nhiên cho trần nhà.', 'Đèn chùm đan tre thủ công 3 bóng, ánh sáng ấm lan tỏa, điểm nhấn thiên nhiên cho phòng ăn.', 'Tre + mây', '60 x 60 x 50', 6, 2300000.00, 0.00, 1050000.00, 12, 25, 5.00, 65.00, 65.00, 55.00, NULL, NULL, 0.00, 'assets/images/shop/lamp-2.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(34, 10, 1, 3, 'Bàn trà mặt kính chân sắt', 'ban-tra-mat-kinh-chan-sat', 'WC-BT-003', NULL, 'Bàn trà mặt kính cường lực 8mm, chân sắt khung đôi.', 'Bàn trà mặt kính cường lực trong suốt, chân sắt khung đôi sơn tĩnh điện, phong cách công nghiệp nhẹ nhàng.', 'Kính cường lực + sắt', '100 x 60 x 40', 12, 2700000.00, 0.00, 1450000.00, 30, 61, 19.00, 105.00, 65.00, 45.00, NULL, NULL, 0.00, 'assets/images/shop/table-glass.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(35, 15, 3, 3, 'Kệ tivi cánh lùa hiện đại', 'ke-tivi-canh-lua-hien-dai', 'WC-KT-004', NULL, 'Kệ tivi có 2 cánh lùa gỗ kẻ tinh tế, ngăn chứa rộng.', 'Kệ tivi cánh lùa 2 cánh gỗ kẻ tinh tế, hệ ray trượt êm, ngăn bên trong rộng đựng đồ tiện dụng.', 'MDF + gỗ kẻ', '160 x 42 x 55', 12, 4600000.00, 0.00, 2600000.00, 24, 47, 32.00, 165.00, 46.00, 60.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-wall.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(36, 18, 5, 3, 'Đồng hồ treo tường gỗ sồi', 'dong-ho-treo-tuong-go-soi', 'WC-DH-001', NULL, 'Đồng hồ treo tường khung gỗ sồi, mặt trắng, kim đen thanh lịch.', 'Đồng hồ treo tường mặt trắng khoáng thạch, khung gỗ sồi vo tròn, máy Nhật êm, không đổ chuông.', 'Gỗ sồi + khoáng thạch', '30cm Ø', 12, 1200000.00, 990000.00, 590000.00, 40, 73, 1.00, 32.00, 32.00, 5.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-minimal.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(37, 16, 1, 3, 'Giá treo tường 3 tầng', 'gia-treo-tuong-3-tang', 'WC-GT-001', NULL, 'Giá treo tường 3 tầng kiểu công nghiệp, kệ để đồ tiện lợi.', 'Giá treo tường 3 tầng với chân sắt kiểu công nghiệp, mặt gỗ tự nhiên, dễ dàng lắp đặt trên tường.', 'MDF + sắt', '120 x 30 x 65', 12, 1850000.00, 0.00, 950000.00, 35, 54, 12.00, 125.00, 35.00, 70.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-wall.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(38, 19, 1, 3, 'Bàn trang điểm gỗ óc chó 1m2', 'ban-trang-diem-go-oc-cho-1m2', 'WC-BTD-002', NULL, 'Bàn trang điểm gỗ óc chó, vân gỗ mềm mại, gương elip sang trọng.', 'Bàn trang điểm 1m2 gỗ óc chó vân đẹp, gương elip treo tường, ngăn kéo tay nắm đồng, phù hợp phòng ngủ chính.', 'Gỗ óc chó', '120 x 50 x 76', 24, 11900000.00, 0.00, 7200000.00, 9, 17, 35.00, 125.00, 55.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/vanity-2.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(39, 13, 1, 3, 'Giường ngủ 1m8 đầu giường liền tủ', 'giuong-ngu-1m8-dau-giuong-lien-tu', 'WC-GN-004', NULL, 'Giường 1m8 kết hợp đầu giường khép kín như tủ 2 bên.', 'Giường ngủ 1m8 khung gỗ sồi, đầu giường tích hợp tủ đầu giường 2 bên khép kín, gia tăng lưu trữ cho phòng ngủ.', 'Gỗ sồi', '200 x 191 x 105', 24, 19900000.00, 0.00, 12000000.00, 5, 14, 135.00, 210.00, 20.00, 0.00, NULL, NULL, 0.00, 'assets/images/shop/bed-modern.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(40, 12, 1, 3, 'Bàn làm việc đơn giản 120cm', 'ban-lam-viec-don-gian-120cm', 'WC-BL-004', NULL, 'Bàn làm việc gọn nhẹ, 120cm, phù hợp sinh viên và nhà nhỏ.', 'Bàn làm việc ngắn gọn 120cm, ngăn kéo kèm hộc nhỏ, gỗ sồi các lớp, thiết kế tối giản cho không gian nhỏ.', 'Gỗ sồi', '120 x 60 x 75', 12, 3400000.00, 0.00, 1800000.00, 38, 83, 22.00, 125.00, 65.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/desk-small.jpg', NULL, 1, 0, 1, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(41, 6, 6, 3, 'Ghế bố dây choàng ban công', 'ghe-bo-day-choang-ban-cong', 'WC-NT-003', NULL, 'Ghế bố dây choàng vải lanh thư giãn ban công, tựa nghiêng.', 'Ghế bố tựa nghiêng nhiều nấc, khung gỗ cao su, mặt vải lanh cao cấp, phù hợp ban công thư giãn cuối tuần.', 'Gỗ cao su + vải lanh', '70 x 190 x 90', 6, 1850000.00, 0.00, 980000.00, 17, 29, 9.00, 75.00, 195.00, 95.00, NULL, NULL, 0.00, 'assets/images/shop/outdoor-3.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(42, 2, 8, 3, 'Bàn ăn xếp hộp 1m4', 'ban-an-xep-hop-1m4', 'WC-BA-004', NULL, 'Bàn ăn xếp gọn thành hộp 1m4, phong cách Nhật, màu sáng.', 'Bàn ăn đa năng xếp gọn như hộp, phù hợp không gian sinh hoạt nhiều mục đích, tông màu sáng tối giản.', 'MDF cao cấp', '140 x 75 x 70', 12, 4900000.00, 4300000.00, 2700000.00, 21, 45, 27.00, 145.00, 80.00, 75.00, NULL, NULL, 0.00, 'assets/images/shop/dining-modern.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(43, 14, 1, 3, 'Tủ quần áo cánh kính 2 buồng', 'tu-quan-ao-canh-kinh-2-buong', 'WC-TQ-003', NULL, 'Tủ quần áo kết hợp cánh kính trượt sang trọng, đèn LED bên trong.', 'Tủ 2 buồng với cánh kính trượt, đèn LED cảm biến, khoang treo rộng, ngăn kéo kính chống bụi.', 'MDF + kính cường lực', '200 x 180 x 240', 24, 21500000.00, 0.00, 12800000.00, 6, 9, 150.00, 205.00, 185.00, 245.00, NULL, NULL, 0.00, 'assets/images/shop/wardrobe-3.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(44, 17, 2, 3, 'Đèn bàn sạc không dây sen đá', 'den-ban-sac-khong-day-sen-da', 'WC-DB-002', NULL, 'Đèn bàn sạc pin, gốm mô phỏng sen đá, ánh sáng vàng ấm.', 'Đèn bàn tích hợp pin sạc 4000mAh, vẻ ngoài mô phỏng sen đá bằng gốm, kết hợp sạc không dây cho điện thoại.', 'Gốm + nhựa', '15 x 15 x 20', 6, 690000.00, 590000.00, 290000.00, 70, 95, 2.00, 16.00, 16.00, 22.00, NULL, NULL, 0.00, 'assets/images/shop/lamp-2.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(45, 10, 1, 3, 'Bàn trà gỗ óc chó hình chữ nhật', 'ban-tra-go-oc-cho-hinh-chu-nhat', 'WC-BT-004', NULL, 'Bàn trà gỗ óc chó sang trọng, kích thước lớn cho phòng khách rộng.', 'Bàn trà lớn gỗ óc chó, mặt gỗ bóng loáng, ngăn hộc kín đáo, phù hợp phòng khách diện tích lớn.', 'Gỗ óc chó', '120 x 70 x 45', 24, 8900000.00, 0.00, 4900000.00, 11, 24, 35.00, 125.00, 75.00, 50.00, NULL, NULL, 0.00, 'assets/images/shop/table-wood.jpg', NULL, 1, 1, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(46, 4, 1, 3, 'Kệ gỗ nhiều ngăn Decalist', 'ke-go-nhieu-ngan-decalist', 'WC-KG-001', NULL, 'Kệ gỗ nhiều ngăn hình lục giác, tạo điểm nhấn sắp đặt độc đáo.', 'Kệ lục giác nhiều ngăn gỗ thông, tự cắt sắp xếp nghệ thuật, điểm nhấn mạnh cho góc trang trí.', 'Gỗ thông', '100 x 30 x 120', 12, 2600000.00, 0.00, 1300000.00, 27, 38, 18.00, 105.00, 35.00, 125.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-2.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(47, 20, 2, 3, 'Bộ bàn ghế xếp sân vườn', 'bo-ban-ghe-xep-san-vuon', 'WC-NT-004', NULL, 'Bộ bàn ghế xếp gọn màu trắng công nghiệp cho sân vườn.', 'Bộ bàn ghế xếp 3 món, khung thép sơn tĩnh điện trắng, mặt gỗ giả vân, gấp gọn sau khi dùng.', 'Thép + gỗ giả', '120 x 60 x 70', 12, 4200000.00, 3800000.00, 2300000.00, 13, 63, 40.00, 125.00, 65.00, 75.00, NULL, NULL, 0.00, 'assets/images/shop/outdoor-4.jpg', NULL, 1, 0, 0, 1, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(48, 18, 1, 3, 'Tranh gỗ treo tường 3 tấm', 'tranh-go-treo-tuong-3-tam', 'WC-TT-001', NULL, 'Bộ tranh gỗ treo tường 3 tấm, họa tiết núi non trừu tượng.', 'Bộ tranh gỗ nổi 3 tấm họa tiết núi non, phong cách trừu tượng, dễ treo kết hợp.', 'Gỗ thông sơn màu', '60 x 5 x 80 (mỗi tấm)', 6, 1900000.00, 1690000.00, 890000.00, 33, 59, 9.00, 185.00, 6.00, 85.00, NULL, NULL, 0.00, 'assets/images/shop/shelf-minimal.jpg', NULL, 1, 0, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(49, 1, 1, 3, 'Sofa bộ 7 món phòng khách cao cấp', 'sofa-bo-7-mon-phong-khach-cao-cap', 'WC-SF-006', NULL, 'Sofa bộ 7 món cao cấp: 1 sofa 3 chỗ, 1 sofa 1 chỗ, 2 ghế, 2 bàn trà.', 'Bộ sofa 7 món sang trọng dành cho phòng khách lớn: văng 3 chỗ, văng 1 chỗ, 2 ghế đôn, 2 bàn trà. Khung gỗ sồi vững chắc, da EPS.', 'Gỗ sồi + da EPS', '320 x 180 x 85', 36, 65900000.00, 59900000.00, 43000000.00, 2, 8, 220.00, 330.00, 190.00, 90.00, NULL, NULL, 0.00, 'assets/images/shop/sofa-set.jpg', NULL, 1, 1, 0, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02'),
(50, 12, 7, 3, 'Bàn làm việc cực gọn 90cm', 'ban-lam-viec-cuc-gon-90cm', 'WC-BL-005', NULL, 'Bàn làm việc 90cm tiết kiệm diện tích, gỗ sồi tự nhiên.', 'Bàn làm việc 90cm gọn nhẹ, phù hợp phòng trọ, chung cư nhỏ, ngăn kéo đa năng, gỗ sồi chân thật.', 'Gỗ sồi', '90 x 60 x 75', 12, 2800000.00, 0.00, 1500000.00, 42, 67, 20.00, 95.00, 65.00, 80.00, NULL, NULL, 0.00, 'assets/images/shop/desk-small.jpg', NULL, 1, 0, 1, 0, 0, '2026-09-14 11:49:35', '2026-09-14 11:53:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `created_at`) VALUES
(64, 1, 'assets/images/shop/sofa-green.jpg', 0, '2026-09-14 12:06:20'),
(65, 2, 'assets/images/shop/sofa-dark.jpg', 0, '2026-09-14 12:06:20'),
(66, 3, 'assets/images/shop/sofa-beige.jpg', 0, '2026-09-14 12:06:20'),
(67, 4, 'assets/images/shop/armchair-navy.jpg', 0, '2026-09-14 12:06:20'),
(68, 5, 'assets/images/shop/lounge-chair.jpg', 0, '2026-09-14 12:06:20'),
(69, 6, 'assets/images/shop/desk-office.jpg', 0, '2026-09-14 12:06:20'),
(70, 7, 'assets/images/shop/table-wood.jpg', 0, '2026-09-14 12:06:20'),
(71, 8, 'assets/images/shop/table-2.jpg', 0, '2026-09-14 12:06:20'),
(72, 9, 'assets/images/shop/dining-wood.jpg', 0, '2026-09-14 12:06:20'),
(73, 10, 'assets/images/shop/dining-modern.jpg', 0, '2026-09-14 12:06:20'),
(74, 11, 'assets/images/shop/table-glass.jpg', 0, '2026-09-14 12:06:20'),
(75, 12, 'assets/images/shop/desk-modern.jpg', 0, '2026-09-14 12:06:20'),
(76, 13, 'assets/images/shop/desk-loft.jpg', 0, '2026-09-14 12:06:20'),
(77, 14, 'assets/images/shop/bed-king.jpg', 0, '2026-09-14 12:06:20'),
(78, 15, 'assets/images/shop/bed-child.jpg', 0, '2026-09-14 12:06:20'),
(79, 16, 'assets/images/shop/wardrobe-2.jpg', 0, '2026-09-14 12:06:20'),
(80, 17, 'assets/images/shop/wardrobe-3.jpg', 0, '2026-09-14 12:06:20'),
(81, 18, 'assets/images/shop/shelf-console.jpg', 0, '2026-09-14 12:06:20'),
(82, 19, 'assets/images/shop/shelf-wall.jpg', 0, '2026-09-14 12:06:20'),
(83, 20, 'assets/images/shop/shelf-2.jpg', 0, '2026-09-14 12:06:20'),
(84, 21, 'assets/images/shop/shelf-minimal.jpg', 0, '2026-09-14 12:06:20'),
(85, 22, 'assets/images/shop/lamp-1.jpg', 0, '2026-09-14 12:06:20'),
(86, 23, 'assets/images/shop/lamp-3.jpg', 0, '2026-09-14 12:06:20'),
(87, 24, 'assets/images/shop/mirror-brick.jpg', 0, '2026-09-14 12:06:20'),
(88, 25, 'assets/images/shop/vanity-deco.jpg', 0, '2026-09-14 12:06:20'),
(89, 26, 'assets/images/shop/outdoor-1.jpg', 0, '2026-09-14 12:06:20'),
(90, 27, 'assets/images/shop/outdoor-2.jpg', 0, '2026-09-14 12:06:20'),
(91, 28, 'assets/images/shop/sofa-set.jpg', 0, '2026-09-14 12:06:20'),
(92, 29, 'assets/images/shop/desk-2.jpg', 0, '2026-09-14 12:06:20'),
(93, 30, 'assets/images/shop/bed-linen.jpg', 0, '2026-09-14 12:06:20'),
(94, 31, 'assets/images/shop/sofa-grey.jpg', 0, '2026-09-14 12:06:20'),
(95, 32, 'assets/images/shop/shelf-wall.jpg', 0, '2026-09-14 12:06:20'),
(96, 33, 'assets/images/shop/lamp-2.jpg', 0, '2026-09-14 12:06:20'),
(97, 34, 'assets/images/shop/table-glass.jpg', 0, '2026-09-14 12:06:20'),
(98, 35, 'assets/images/shop/shelf-wall.jpg', 0, '2026-09-14 12:06:20'),
(99, 36, 'assets/images/shop/shelf-minimal.jpg', 0, '2026-09-14 12:06:20'),
(100, 37, 'assets/images/shop/shelf-wall.jpg', 0, '2026-09-14 12:06:20'),
(101, 38, 'assets/images/shop/vanity-2.jpg', 0, '2026-09-14 12:06:20'),
(102, 39, 'assets/images/shop/bed-modern.jpg', 0, '2026-09-14 12:06:20'),
(103, 40, 'assets/images/shop/desk-small.jpg', 0, '2026-09-14 12:06:20'),
(104, 41, 'assets/images/shop/outdoor-3.jpg', 0, '2026-09-14 12:06:20'),
(105, 42, 'assets/images/shop/dining-modern.jpg', 0, '2026-09-14 12:06:20'),
(106, 43, 'assets/images/shop/wardrobe-3.jpg', 0, '2026-09-14 12:06:20'),
(107, 44, 'assets/images/shop/lamp-2.jpg', 0, '2026-09-14 12:06:20'),
(108, 45, 'assets/images/shop/table-wood.jpg', 0, '2026-09-14 12:06:20'),
(109, 46, 'assets/images/shop/shelf-2.jpg', 0, '2026-09-14 12:06:20'),
(110, 47, 'assets/images/shop/outdoor-4.jpg', 0, '2026-09-14 12:06:20'),
(111, 48, 'assets/images/shop/shelf-minimal.jpg', 0, '2026-09-14 12:06:20'),
(112, 49, 'assets/images/shop/sofa-set.jpg', 0, '2026-09-14 12:06:20'),
(113, 50, 'assets/images/shop/desk-small.jpg', 0, '2026-09-14 12:06:20');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL COMMENT 'Tên thuộc tính: Kích thước, Chất liệu, Màu...',
  `value` varchar(120) NOT NULL,
  `price_adjust` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Chênh lệch giá so với giá gốc',
  `stock` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(60) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `name`, `value`, `price_adjust`, `stock`, `sku`, `created_at`) VALUES
(1, 1, 'Màu', 'Be', 0.00, 5, 'WC-SF-001-BE', '2026-09-14 11:49:35'),
(2, 1, 'Màu', 'Xám', 0.00, 4, 'WC-SF-001-XAM', '2026-09-14 11:49:35'),
(3, 1, 'Màu', 'Xanh rêu', 300000.00, 3, 'WC-SF-001-XR', '2026-09-14 11:49:35'),
(4, 7, 'Kích thước', 'Bàn tròn Ø90', 0.00, 10, 'WC-BT-001-90', '2026-09-14 11:49:35'),
(5, 7, 'Kích thước', 'Bàn tròn Ø100', 600000.00, 12, 'WC-BT-001-100', '2026-09-14 11:49:35'),
(6, 9, 'Chất liệu', 'Ghế vải nỉ', 0.00, 6, 'WC-BA-001-VN', '2026-09-14 11:49:35'),
(7, 9, 'Chất liệu', 'Ghế bọc da lộn', 1200000.00, 2, 'WC-BA-001-DL', '2026-09-14 11:49:35'),
(8, 13, 'Chất liệu', 'Gỗ sồi', 1200000.00, 10, 'WC-GN-001-SOI', '2026-09-14 11:49:35'),
(9, 13, 'Chất liệu', 'Gỗ óc chó', 2500000.00, 6, 'WC-GN-001-OC', '2026-09-14 11:49:35'),
(10, 21, 'Kích thước', '150cm', 0.00, 12, 'WC-KT-002-150', '2026-09-14 11:49:35'),
(11, 21, 'Kích thước', '180cm', 700000.00, 6, 'WC-KT-002-180', '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `product_videos`
--

CREATE TABLE `product_videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `video_url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_videos`
--

INSERT INTO `product_videos` (`id`, `product_id`, `video_url`, `created_at`) VALUES
(1, 1, 'https://www.youtube.com/watch?v=3DqZ7auW6Ak', '2026-09-14 11:49:35'),
(2, 9, 'https://www.youtube.com/watch?v=3DqZ7auW6Ak', '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `province_distances`
--

CREATE TABLE `province_distances` (
  `id` int(10) UNSIGNED NOT NULL,
  `province` varchar(100) NOT NULL,
  `distance_km` decimal(8,1) NOT NULL DEFAULT 0.0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `province_distances`
--

INSERT INTO `province_distances` (`id`, `province`, `distance_km`, `sort_order`) VALUES
(1, 'Hồ Chí Minh', 0.0, 1),
(2, 'Bình Dương', 30.0, 2),
(3, 'Đồng Nai', 40.0, 3),
(4, 'Tiền Giang', 75.0, 4),
(5, 'Bến Tre', 90.0, 5),
(6, 'Bà Rịa - Vũng Tàu', 95.0, 6),
(7, 'Tây Ninh', 95.0, 7),
(8, 'Long An', 60.0, 8),
(9, 'Vĩnh Long', 120.0, 9),
(10, 'Bình Phước', 130.0, 10),
(11, 'Trà Vinh', 150.0, 11),
(12, 'Cần Thơ', 170.0, 12),
(13, 'Đồng Tháp', 180.0, 13),
(14, 'Hậu Giang', 200.0, 14),
(15, 'Bình Thuận', 200.0, 15),
(16, 'An Giang', 230.0, 16),
(17, 'Sóc Trăng', 230.0, 17),
(18, 'Kiên Giang', 250.0, 18),
(19, 'Lâm Đồng', 250.0, 19),
(20, 'Bạc Liêu', 300.0, 20),
(21, 'Ninh Thuận', 380.0, 21),
(22, 'Đắk Nông', 430.0, 22),
(23, 'Khánh Hòa', 450.0, 23),
(24, 'Đắk Lắk', 520.0, 24),
(25, 'Gia Lai', 610.0, 25),
(26, 'Kon Tum', 700.0, 26),
(27, 'Phú Yên', 800.0, 27),
(28, 'Quảng Ngãi', 820.0, 28),
(29, 'Quảng Nam', 880.0, 29),
(30, 'Bình Định', 900.0, 30),
(31, 'Đà Nẵng', 960.0, 31),
(32, 'Cà Mau', 360.0, 32),
(33, 'Quảng Trị', 1100.0, 33),
(34, 'Thừa Thiên Huế', 1050.0, 34),
(35, 'Quảng Bình', 1200.0, 35),
(36, 'Hà Tĩnh', 1300.0, 36),
(37, 'Nghệ An', 1400.0, 37),
(38, 'Thanh Hóa', 1550.0, 38),
(39, 'Hà Nam', 1600.0, 39),
(40, 'Ninh Bình', 1650.0, 40),
(41, 'Hà Nội', 1650.0, 41),
(42, 'Nam Định', 1650.0, 42),
(43, 'Hưng Yên', 1650.0, 43),
(44, 'Bắc Ninh', 1650.0, 44),
(45, 'Hải Dương', 1700.0, 45),
(46, 'Hòa Bình', 1700.0, 46),
(47, 'Vĩnh Phúc', 1700.0, 47),
(48, 'Thái Bình', 1750.0, 48),
(49, 'Phú Thọ', 1750.0, 49),
(50, 'Bắc Giang', 1650.0, 50),
(51, 'Hải Phòng', 1800.0, 51),
(52, 'Lạng Sơn', 1800.0, 52),
(53, 'Thái Nguyên', 1800.0, 53),
(54, 'Bắc Kạn', 1750.0, 54),
(55, 'Tuyên Quang', 1850.0, 55),
(56, 'Quảng Ninh', 1900.0, 56),
(57, 'Cao Bằng', 1900.0, 57),
(58, 'Yên Bái', 1900.0, 58),
(59, 'Sơn La', 2000.0, 59),
(60, 'Lào Cai', 2000.0, 60),
(61, 'Điện Biên', 2100.0, 61),
(62, 'Lai Châu', 2200.0, 62),
(63, 'Hà Giang', 2050.0, 63);

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` enum('cod','bank','qr','wallet') NOT NULL DEFAULT 'bank',
  `reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON list ảnh',
  `status` enum('pending','approved','hidden') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `description`, `is_system`, `created_at`) VALUES
(1, 'superadmin', 'Chủ hệ thống', 'Toàn quyền, có quyền Hard Delete & cấu hình hệ thống', 1, '2026-09-14 11:50:20'),
(2, 'accountant', 'Kế toán', 'Quản lý hóa đơn, báo cáo tài chính, thuế', 0, '2026-09-14 11:50:20'),
(3, 'warehouse', 'Nhân viên kho', 'Xử lý đơn, nhập/xuất kho, bảo hành', 0, '2026-09-14 11:50:20'),
(4, 'cskh', 'CSKH', 'Chăm sóc khách hàng, kiểm duyệt hủy/hoàn', 0, '2026-09-14 11:50:20'),
(5, 'hr', 'Quản lý Nhân sự', 'Quản lý tài khoản nhân viên', 0, '2026-09-14 11:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`) VALUES
(19, 1, 1),
(30, 1, 2),
(26, 1, 3),
(28, 1, 4),
(27, 1, 5),
(29, 1, 6),
(21, 1, 7),
(20, 1, 8),
(34, 1, 9),
(31, 1, 10),
(33, 1, 11),
(32, 1, 12),
(13, 1, 13),
(10, 1, 14),
(12, 1, 15),
(11, 1, 16),
(45, 1, 17),
(42, 1, 18),
(44, 1, 19),
(43, 1, 20),
(40, 1, 21),
(39, 1, 22),
(36, 1, 23),
(35, 1, 24),
(1, 1, 25),
(41, 1, 26),
(18, 1, 27),
(16, 1, 28),
(17, 1, 29),
(53, 1, 30),
(50, 1, 31),
(52, 1, 32),
(51, 1, 33),
(5, 1, 34),
(2, 1, 35),
(4, 1, 36),
(3, 1, 37),
(25, 1, 38),
(22, 1, 39),
(24, 1, 40),
(23, 1, 41),
(9, 1, 42),
(6, 1, 43),
(8, 1, 44),
(7, 1, 45),
(49, 1, 46),
(46, 1, 47),
(48, 1, 48),
(47, 1, 49),
(55, 1, 50),
(54, 1, 51),
(15, 1, 52),
(14, 1, 53),
(38, 1, 54),
(37, 1, 55),
(90, 1, 85),
(93, 1, 86),
(69, 2, 2),
(65, 2, 7),
(66, 2, 8),
(67, 2, 23),
(68, 2, 24),
(74, 3, 2),
(75, 3, 4),
(72, 3, 9),
(73, 3, 11),
(91, 3, 85),
(94, 3, 86),
(80, 4, 2),
(78, 4, 54),
(79, 4, 55),
(86, 5, 1),
(87, 5, 17),
(88, 5, 18),
(89, 5, 19);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `group_name` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `group_name`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'WoodCon', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(2, 'site_slogan', 'Nội thất gỗ - Tinh tế cho ngôi nhà của bạn', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(3, 'site_logo', '', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(4, 'site_phone', '1900 8686', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(5, 'site_email', 'lienhe@woodcon.vn', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(6, 'site_address', '123 Đường Lê Quý Đôn, Quận 3, TP. Hồ Chí Minh', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(7, 'site_facebook', 'https://facebook.com/woodcon', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(8, 'site_instagram', 'https://instagram.com/woodcon', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(9, 'site_youtube', 'https://youtube.com/woodcon', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(10, 'site_zalo', '1900 8686', 'general', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(11, 'site_bank_name', 'Ngân hàng Vietcombank', 'payment', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(12, 'site_bank_account', '0123 4567 8901', 'payment', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(13, 'site_bank_holder', 'CTY TNHH WoodCon', 'payment', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(14, 'site_qr_note', 'WOODCON - Chuyển khoản đơn hàng', 'payment', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(15, 'vat_percent', '8', 'tax', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(16, 'ship_divisor', '5000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(17, 'ship_min_fee', '20000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(18, 'ship_fee_noithanh', '25000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(19, 'ship_fee_lientinh', '45000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(20, 'ship_fee_lienmien', '65000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(21, 'ship_min_km', '1', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(22, 'free_ship_threshold', '2000000', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(23, 'order_wait_confirm_minutes', '20', 'order', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(24, 'max_delivery_fail', '3', 'order', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(25, 'delivery_auto_confirm_days', '3', 'order', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(26, 'cod_high_value_threshold', '1000000', 'cod', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(27, 'cod_max_orders_per_hour', '3', 'cod', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(28, 'point_rate', '10000', 'member', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(29, 'point_value', '1000', 'member', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(30, 'refund_processing_days', '3', 'order', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(31, 'return_period_days', '30', 'warranty', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(32, 'quality_claim_hours', '48', 'warranty', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(33, 'free_ship_note', 'Miễn phí vận chuyển cho đơn hàng từ 2.000.000đ', 'shipping', '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(34, 'ai_provider', 'gemini', 'ai', '2026-09-14 11:49:35', '2026-09-15 16:31:05'),
(35, 'ai_models', 'gemini-3.1-flash-lite,gemini-3.5-flash,gemini-3.8-flash', 'ai', '2026-09-14 11:49:35', '2026-09-15 16:31:05'),
(36, 'ai_api_key', '', 'ai', '2026-09-14 11:49:35', '2026-09-15 16:31:05'),
(37, 'invoice_company_name', 'WoodCon Furniture', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(38, 'invoice_tax_code', '', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(39, 'invoice_address', '', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(40, 'invoice_phone', '', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(41, 'invoice_email', '', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(42, 'invoice_number_prefix', 'HD', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(43, 'invoice_last_number', '0', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(44, 'invoice_footer', 'Cảm ơn quý khách đã mua hàng!', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(45, 'invoice_legal_note', 'Hóa đơn nội bộ — không phải hóa đơn GTGT', 'invoice', '2026-09-14 11:53:03', '2026-09-14 11:53:03'),
(61, 'goong_api_key', '', 'maps', '2026-09-14 17:11:50', '2026-09-14 17:11:50'),
(62, 'goong_autocomplete_enabled', '0', 'maps', '2026-09-14 17:11:50', '2026-09-14 17:11:50'),
(63, 'goong_location_lat', '', 'maps', '2026-09-14 17:11:50', '2026-09-14 17:11:50'),
(64, 'goong_location_lng', '', 'maps', '2026-09-14 17:11:50', '2026-09-14 17:11:50'),
(65, 'invoice_hd_seq', '48', 'invoice', '2026-09-14 18:34:23', '2026-09-14 18:34:23'),
(66, 'vtp_cat_synced_at', '2026-09-15 01:50:03', 'shipping', '2026-09-14 18:50:03', '2026-09-14 18:50:03'),
(67, 'vtp_enabled', '1', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(68, 'vtp_sender_province_id', '5', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(69, 'vtp_sender_province_name', 'Cần Thơ', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(70, 'vtp_sender_district_name', 'QUẬN BÌNH THỦY', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(71, 'vtp_service', '', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(72, 'vtp_product_type', 'HH', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(73, 'vtp_cod_enabled', '1', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(74, 'vtp_surcharge', '0', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(75, 'vtp_freeship_threshold', '0', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(76, 'ship_volumetric_divisor', '6000', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(77, 'ship_label_type2', 'Vận chuyển qua Viettel Post', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:33'),
(78, 'vtp_last_call', '2026-09-15 20:33:35', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:35'),
(79, 'vtp_last_status', 'ok', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:35'),
(80, 'vtp_last_message', 'Kết nối thành công. Cước thử (1kg, cùng tỉnh): 18.150 đ.', 'shipping', '2026-09-15 13:30:31', '2026-09-15 13:33:35'),
(92, 'vtp_token', '', 'shipping', '2026-09-15 13:33:04', '2026-09-15 13:33:33'),
(99, 'vtp_sender_district_id', '91', 'shipping', '2026-09-15 13:33:33', '2026-09-15 13:33:33');

-- --------------------------------------------------------

--
-- Table structure for table `ship_fee_brackets`
--

CREATE TABLE `ship_fee_brackets` (
  `id` int(10) UNSIGNED NOT NULL,
  `min_km` int(11) NOT NULL DEFAULT 0,
  `max_km` int(11) DEFAULT NULL COMMENT 'NULL = không giới hạn trên',
  `fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `free_ship_threshold` decimal(15,2) DEFAULT NULL COMMENT 'Ngưỡng miễn phí ship riêng cho bậc; NULL/0 = dùng ngưỡng chung',
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ship_fee_brackets`
--

INSERT INTO `ship_fee_brackets` (`id`, `min_km`, `max_km`, `fee`, `free_ship_threshold`, `sort_order`) VALUES
(1, 0, 30, 150000.00, 3000000.00, 1),
(2, 31, 100, 350000.00, 5000000.00, 2),
(3, 101, 300, 650000.00, 10000000.00, 3),
(4, 301, NULL, 950000.00, 20000000.00, 4);

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Thuế suất %',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`id`, `name`, `rate`, `is_default`, `status`, `note`, `description`, `created_at`, `updated_at`) VALUES
(1, 'VAT 0% (không chịu thuế)', 0.00, 0, 1, 'Hàng không chịu VAT', NULL, '2026-09-14 11:53:02', '2026-09-14 11:53:02'),
(2, 'VAT 5%', 5.00, 0, 1, 'Theo NĐ 15/2022/NĐ-CP', NULL, '2026-09-14 11:53:02', '2026-09-14 11:53:02'),
(3, 'VAT 8%', 8.00, 1, 1, 'Mức mặc định cho đồ gỗ nội thất', NULL, '2026-09-14 11:53:02', '2026-09-14 11:53:02'),
(4, 'VAT 10%', 10.00, 0, 1, 'Theo NĐ 15/2022/NĐ-CP', NULL, '2026-09-14 11:53:02', '2026-09-14 11:53:02');

-- --------------------------------------------------------

--
-- Table structure for table `trust_logs`
--

CREATE TABLE `trust_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `event` varchar(80) NOT NULL COMMENT 'delivered / refused / cancelled',
  `tier_before` enum('green','yellow','red') DEFAULT NULL,
  `tier_after` enum('green','yellow','red') DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `google_id` varchar(191) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `birthday` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `email_verify` tinyint(1) NOT NULL DEFAULT 0,
  `phone_verify` tinyint(1) NOT NULL DEFAULT 0,
  `trust_level` enum('green','yellow','red') NOT NULL DEFAULT 'yellow',
  `trust_score` int(11) NOT NULL DEFAULT 0,
  `success_orders` int(11) NOT NULL DEFAULT 0,
  `failed_orders` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `points` int(11) NOT NULL DEFAULT 0,
  `membership_tier_id` int(10) UNSIGNED DEFAULT NULL,
  `membership_expired` date DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_purpose` varchar(30) DEFAULT NULL,
  `otp_expired` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `phone`, `google_id`, `password`, `avatar`, `gender`, `birthday`, `address`, `ward`, `district`, `city`, `status`, `email_verify`, `phone_verify`, `trust_level`, `trust_score`, `success_orders`, `failed_orders`, `total_spent`, `points`, `membership_tier_id`, `membership_expired`, `otp_code`, `otp_purpose`, `otp_expired`, `created_at`, `updated_at`) VALUES
(1, 'Nguyễn Văn An', NULL, 'an@example.com', '0912345670', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'male', NULL, '12 Nguyễn Huệ', 'Phường Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', 1, 0, 0, 'green', 100, 5, 0, 108676000.00, 0, 3, '2027-09-15', NULL, NULL, NULL, '2026-09-14 11:49:35', '2026-09-14 18:34:23'),
(2, 'Trần Thị Bích', NULL, 'bich@example.com', '0912345671', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'female', NULL, '234 Lê Lợi', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', 1, 0, 0, 'yellow', 0, 0, 0, 68100000.00, 0, 3, '2027-09-15', NULL, NULL, NULL, '2026-09-14 11:49:35', '2026-09-14 18:34:23'),
(3, 'Lê Hoàng Cương', NULL, 'cuong@example.com', '0912345672', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'male', NULL, '56 Trần Phú', 'Phường Mỹ Đình', 'Quận Nam Từ Liêm', 'Hà Nội', 1, 0, 0, 'red', -50, 1, 0, 19800000.00, 0, 2, '2027-09-15', NULL, NULL, NULL, '2026-09-14 11:49:35', '2026-09-14 18:34:23'),
(4, 'Phạm Minh Đức', NULL, 'duc@example.com', '0912345673', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'male', NULL, '89 Hai Bà Trưng', 'Phường Đa Kao', 'Quận 1', 'TP. Hồ Chí Minh', 1, 0, 0, 'green', 120, 7, 0, 177278000.00, 0, 3, '2027-09-15', NULL, NULL, NULL, '2026-09-14 11:49:35', '2026-09-14 18:34:23'),
(5, 'Võ Thị Em', NULL, 'em@example.com', '0912345674', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'female', NULL, '345 CMT8', 'Phường 10', 'Quận 3', 'TP. Hồ Chí Minh', 1, 0, 0, 'yellow', 20, 1, 0, 249850000.00, 0, 3, '2027-09-15', NULL, NULL, NULL, '2026-09-14 11:49:35', '2026-09-14 18:34:23'),
(6, 'Phan Tuấn Anh', NULL, 'khach.tuananh@example.com', '0909000008', NULL, '$2y$10$2//r08lyaxq1MQtXT4tAhO35qKGdlxgE9n3kpWfQ9h6EyLNFhQh/O', NULL, 'other', NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 'yellow', 0, 0, 0, 0.00, 0, 1, '2027-09-16', NULL, NULL, NULL, '2026-09-15 17:17:25', '2026-09-15 17:17:25');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `type` enum('discount','freeship') NOT NULL DEFAULT 'discount',
  `name` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(15,2) DEFAULT NULL COMMENT 'Giảm tối đa (cho loại percent)',
  `min_order_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `used_quantity` int(11) NOT NULL DEFAULT 0,
  `per_user_limit` int(11) NOT NULL DEFAULT 1,
  `scope_type` enum('all','category','product') NOT NULL DEFAULT 'all',
  `scope_ids` text DEFAULT NULL COMMENT 'JSON danh sách id phạm vi',
  `required_tier_id` int(10) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `type`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `min_order_value`, `start_date`, `end_date`, `total_quantity`, `used_quantity`, `per_user_limit`, `scope_type`, `scope_ids`, `required_tier_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'discount', 'Giảm 10% cho khách mới', 'Nhập mã khi đặt hàng đầu tiên', 'percent', 10.00, 300000.00, 500000.00, '2026-09-14 18:49:35', '2026-12-13 18:49:35', 1000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(2, 'WOODCON5', 'discount', 'Giảm 5% tổng đơn', 'Ưu đãi chung cho thương hiệu WoodCon', 'percent', 5.00, 500000.00, 200000.00, '2026-09-14 18:49:35', '2026-11-13 18:49:35', 2000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(3, 'SALE50', 'discount', 'Giảm 50k cho đơn từ 1 triệu', 'Flash sale mừng khai trương', 'fixed', 50000.00, NULL, 1000000.00, '2026-09-14 18:49:35', '2026-10-14 18:49:35', 500, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(4, 'FO100', 'discount', 'Freeship đối với đơn nội thành', 'Giảm hết phí ship nội thành', 'percent', 100.00, 25000.00, 100000.00, '2026-09-14 18:49:35', '2026-12-13 18:49:35', 3000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(5, 'NGAYMO', 'discount', 'Giảm 15% tất cả sofa', 'Khuyến mãi chào hè trên sofa', 'percent', 15.00, 1000000.00, 1500000.00, '2026-09-14 18:49:35', '2026-10-29 18:49:35', 200, 0, 1, 'category', '1', NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(6, 'GOCCHO', 'discount', 'Giảm 300k bàn ăn gỗ óc chó', 'Ưu đãi sản phẩm bàn ăn óc chó', 'fixed', 300000.00, NULL, 5000000.00, '2026-09-14 18:49:35', '2026-11-13 18:49:35', 100, 0, 1, 'product', '9', NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(7, 'GIUONG8', 'discount', 'Giảm 8% giường ngủ', 'Ưu đãi giường ngủ mùa hè', 'percent', 8.00, 700000.00, 3000000.00, '2026-09-14 18:49:35', '2026-11-28 18:49:35', 150, 0, 1, 'category', '13', NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(8, 'TET2026', 'discount', 'Giảm 12% toàn bộ đơn Tết', 'Khuyến mãi dịp lễ', 'percent', 12.00, 1500000.00, 2000000.00, '2026-10-14 18:49:35', '2027-01-12 18:49:35', 800, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(9, 'REVIEW10', 'discount', 'Giảm 10k khi đánh giá', 'Cảm ơn phản hồi', 'fixed', 10000.00, NULL, 0.00, '2026-09-14 18:49:35', '2027-09-14 18:49:35', 5000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(10, 'FREESHIP2M', 'freeship', 'Freeship đơn từ 2 triệu', 'Miễn phí vận chuyển toàn quốc', 'percent', 100.00, 1000000.00, 2000000.00, '2026-09-14 18:49:35', '2027-01-12 18:49:35', 1500, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(11, 'SHIP1M', 'freeship', 'Freeship đơn từ 1 triệu', 'Miễn phí vận chuyển', 'percent', 100.00, 500000.00, 1000000.00, '2026-09-14 18:49:35', '2026-12-13 18:49:35', 2000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35'),
(12, 'VIP3', 'discount', 'Giảm 3% cho hội viên VIP', 'Quyền lợi hạng VIP', 'percent', 3.00, 500000.00, 0.00, '2026-09-14 18:49:35', '2027-09-14 18:49:35', 10000, 0, 1, 'all', NULL, NULL, 1, '2026-09-14 11:49:35', '2026-09-14 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `vtp_categories`
--

CREATE TABLE `vtp_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` int(11) NOT NULL COMMENT 'Mã ID theo Viettel Post',
  `name` varchar(120) NOT NULL COMMENT 'Tên Tỉnh/Thành hoặc Quận/Huyện',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = Tỉnh/Thành; nếu > 0 = mã Tỉnh chứa Quận/Huyện',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache danh mục Tỉnh/Quận Viettel Post (đồng bộ từ API, TTL 24h)';

--
-- Dumping data for table `vtp_categories`
--

INSERT INTO `vtp_categories` (`id`, `code`, `name`, `parent_id`, `updated_at`) VALUES
(1, 1, 'Hà Nội', 0, '2026-09-15 01:50:03'),
(2, 2, 'Hồ Chí Minh', 0, '2026-09-15 01:50:03'),
(3, 3, 'Hải Phòng', 0, '2026-09-15 01:50:03'),
(4, 4, 'Đà Nẵng', 0, '2026-09-15 01:50:03'),
(5, 5, 'Cần Thơ', 0, '2026-09-15 01:50:03'),
(6, 6, 'Tiền Giang', 0, '2026-09-15 01:50:03'),
(7, 8, 'Hậu Giang', 0, '2026-09-15 01:50:03'),
(8, 9, 'Đăk Nông', 0, '2026-09-15 01:50:03'),
(9, 10, 'Vĩnh Phúc', 0, '2026-09-15 01:50:03'),
(10, 11, 'Bắc Ninh', 0, '2026-09-15 01:50:03'),
(11, 12, 'Hải Dương', 0, '2026-09-15 01:50:03'),
(12, 13, 'Hưng Yên', 0, '2026-09-15 01:50:03'),
(13, 14, 'Hà Nam', 0, '2026-09-15 01:50:03'),
(14, 15, 'Nam Ðịnh', 0, '2026-09-15 01:50:03'),
(15, 16, 'Thái Bình', 0, '2026-09-15 01:50:03'),
(16, 17, 'Ninh Bình', 0, '2026-09-15 01:50:03'),
(17, 18, 'Hà Giang', 0, '2026-09-15 01:50:03'),
(18, 19, 'Cao Bằng', 0, '2026-09-15 01:50:03'),
(19, 20, 'Lào Cai', 0, '2026-09-15 01:50:03'),
(20, 21, 'Bắc Kạn', 0, '2026-09-15 01:50:03'),
(21, 22, 'Lạng Sơn', 0, '2026-09-15 01:50:03'),
(22, 23, 'Tuyên Quang', 0, '2026-09-15 01:50:03'),
(23, 24, 'Yên Bái', 0, '2026-09-15 01:50:03'),
(24, 25, 'Thái Nguyên', 0, '2026-09-15 01:50:03'),
(25, 26, 'Phú Thọ', 0, '2026-09-15 01:50:03'),
(26, 27, 'Bắc Giang', 0, '2026-09-15 01:50:03'),
(27, 28, 'Quảng Ninh', 0, '2026-09-15 01:50:03'),
(28, 29, 'Lai Châu', 0, '2026-09-15 01:50:03'),
(29, 30, 'Sơn La', 0, '2026-09-15 01:50:03'),
(30, 31, 'Hòa Bình', 0, '2026-09-15 01:50:03'),
(31, 32, 'Thanh Hóa', 0, '2026-09-15 01:50:03'),
(32, 33, 'Nghệ An', 0, '2026-09-15 01:50:03'),
(33, 34, 'Hà Tĩnh', 0, '2026-09-15 01:50:03'),
(34, 35, 'Quảng Bình', 0, '2026-09-15 01:50:03'),
(35, 36, 'Quảng Trị', 0, '2026-09-15 01:50:03'),
(36, 37, 'Thừa Thiên - Huế', 0, '2026-09-15 01:50:03'),
(37, 38, 'Quảng Nam', 0, '2026-09-15 01:50:03'),
(38, 39, 'Quảng Ngãi', 0, '2026-09-15 01:50:03'),
(39, 40, 'Bình Ðịnh', 0, '2026-09-15 01:50:03'),
(40, 41, 'Phú Yên', 0, '2026-09-15 01:50:03'),
(41, 42, 'Khánh Hòa', 0, '2026-09-15 01:50:03'),
(42, 43, 'Kon Tum', 0, '2026-09-15 01:50:03'),
(43, 44, 'Gia Lai', 0, '2026-09-15 01:50:03'),
(44, 45, 'Đăk Lăk', 0, '2026-09-15 01:50:03'),
(45, 46, 'Lâm Ðồng', 0, '2026-09-15 01:50:03'),
(46, 47, 'Ninh Thuận', 0, '2026-09-15 01:50:03'),
(47, 48, 'Bình Phước', 0, '2026-09-15 01:50:03'),
(48, 49, 'Tây Ninh', 0, '2026-09-15 01:50:03'),
(49, 50, 'Bình Dương', 0, '2026-09-15 01:50:03'),
(50, 51, 'Đồng Nai', 0, '2026-09-15 01:50:03'),
(51, 52, 'Bình Thuận', 0, '2026-09-15 01:50:03'),
(52, 53, 'Bà rịa - Vũng tàu', 0, '2026-09-15 01:50:03'),
(53, 54, 'Long An', 0, '2026-09-15 01:50:03'),
(54, 55, 'Đồng Tháp', 0, '2026-09-15 01:50:03'),
(55, 56, 'An Giang', 0, '2026-09-15 01:50:03'),
(56, 57, 'Vĩnh Long', 0, '2026-09-15 01:50:03'),
(57, 58, 'Bến Tre', 0, '2026-09-15 01:50:03'),
(58, 59, 'Kiên Giang', 0, '2026-09-15 01:50:03'),
(59, 60, 'Trà Vinh', 0, '2026-09-15 01:50:03'),
(60, 61, 'Sóc Trăng', 0, '2026-09-15 01:50:03'),
(61, 62, 'Bạc Liêu', 0, '2026-09-15 01:50:03'),
(62, 63, 'Cà Mau', 0, '2026-09-15 01:50:03'),
(63, 64, 'Điện Biên', 0, '2026-09-15 01:50:03'),
(64, 80, 'QUẬN Ô MÔN', 5, '2026-09-15 20:33:17'),
(65, 81, 'HUYỆN THỚI LAI', 5, '2026-09-15 20:33:17'),
(66, 82, 'QUẬN NINH KIỀU', 5, '2026-09-15 20:33:17'),
(67, 83, 'QUẬN THỐT NỐT', 5, '2026-09-15 20:33:17'),
(68, 85, 'HUYỆN VĨNH THẠNH', 5, '2026-09-15 20:33:17'),
(69, 87, 'HUYỆN PHONG ĐIỀN', 5, '2026-09-15 20:33:17'),
(70, 89, 'QUẬN CÁI RĂNG', 5, '2026-09-15 20:33:17'),
(71, 90, 'HUYỆN CỜ ĐỎ', 5, '2026-09-15 20:33:17'),
(72, 91, 'QUẬN BÌNH THỦY', 5, '2026-09-15 20:33:17'),
(73, 100000005, 'Bỏ qua - Sử dụng địa chỉ 2 cấp', 5, '2026-09-15 20:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

CREATE TABLE `warranties` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `order_item_id` int(10) UNSIGNED NOT NULL,
  `serial_no` varchar(60) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `warranty_months` int(11) NOT NULL DEFAULT 12,
  `warranty_end` date NOT NULL COMMENT 'Tự tính = ngày mua + số tháng',
  `status` enum('active','expired','used','rejected') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warranties`
--

INSERT INTO `warranties` (`id`, `order_id`, `order_item_id`, `serial_no`, `purchase_date`, `warranty_months`, `warranty_end`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'WC-00001-FFE3', '2026-04-21', 12, '2027-04-21', 'active', '2026-04-21 03:33:12', '2026-09-14 18:34:23'),
(2, 1, 2, 'WC-00002-0DBF', '2026-04-21', 18, '2027-10-21', 'active', '2026-04-21 03:33:12', '2026-09-14 18:34:23'),
(3, 1, 3, 'WC-00003-FD0E', '2026-04-21', 12, '2027-04-21', 'active', '2026-04-21 03:33:12', '2026-09-14 18:34:23'),
(4, 2, 4, 'WC-00004-6C27', '2026-04-14', 24, '2028-04-14', 'active', '2026-04-14 03:37:52', '2026-09-14 18:34:23'),
(5, 3, 5, 'WC-00005-34F4', '2026-04-18', 24, '2028-04-18', 'active', '2026-04-18 05:05:16', '2026-09-14 18:34:23'),
(6, 3, 6, 'WC-00006-8E7B', '2026-04-18', 36, '2029-04-18', 'active', '2026-04-18 05:05:16', '2026-09-14 18:34:23'),
(7, 3, 7, 'WC-00007-C667', '2026-04-18', 24, '2028-04-18', 'active', '2026-04-18 05:05:16', '2026-09-14 18:34:23'),
(8, 4, 8, 'WC-00008-5664', '2026-04-28', 24, '2028-04-28', 'active', '2026-04-28 02:36:29', '2026-09-14 18:34:23'),
(9, 4, 9, 'WC-00009-C79D', '2026-04-28', 36, '2029-04-28', 'active', '2026-04-28 02:36:29', '2026-09-14 18:34:23'),
(10, 4, 10, 'WC-00010-944D', '2026-04-28', 24, '2028-04-28', 'active', '2026-04-28 02:36:29', '2026-09-14 18:34:23'),
(11, 5, 11, 'WC-00011-565D', '2026-04-24', 24, '2028-04-24', 'active', '2026-04-24 10:32:09', '2026-09-14 18:34:23'),
(12, 5, 12, 'WC-00012-703D', '2026-04-24', 6, '2026-10-24', 'active', '2026-04-24 10:32:09', '2026-09-14 18:34:23'),
(13, 5, 13, 'WC-00013-CEBA', '2026-04-24', 24, '2028-04-24', 'active', '2026-04-24 10:32:09', '2026-09-14 18:34:23'),
(14, 6, 14, 'WC-00014-E597', '2026-04-24', 12, '2027-04-24', 'active', '2026-04-24 10:05:13', '2026-09-14 18:34:23'),
(15, 6, 15, 'WC-00015-84B4', '2026-04-24', 24, '2028-04-24', 'active', '2026-04-24 10:05:13', '2026-09-14 18:34:23'),
(16, 6, 16, 'WC-00016-37E5', '2026-04-24', 24, '2028-04-24', 'active', '2026-04-24 10:05:13', '2026-09-14 18:34:23'),
(17, 8, 18, 'WC-00018-3C0C', '2026-05-19', 6, '2026-11-19', 'active', '2026-05-19 06:42:07', '2026-09-14 18:34:23'),
(18, 9, 19, 'WC-00019-BFB1', '2026-05-25', 12, '2027-05-25', 'active', '2026-05-25 02:23:22', '2026-09-14 18:34:23'),
(19, 9, 20, 'WC-00020-5F59', '2026-05-25', 6, '2026-11-25', 'active', '2026-05-25 02:23:22', '2026-09-14 18:34:23'),
(20, 9, 21, 'WC-00021-0B72', '2026-05-25', 6, '2026-11-25', 'active', '2026-05-25 02:23:22', '2026-09-14 18:34:23'),
(21, 10, 22, 'WC-00022-CC05', '2026-05-14', 12, '2027-05-14', 'active', '2026-05-14 05:44:29', '2026-09-14 18:34:23'),
(22, 11, 23, 'WC-00023-BA37', '2026-05-10', 12, '2027-05-10', 'active', '2026-05-10 06:07:48', '2026-09-14 18:34:23'),
(23, 11, 24, 'WC-00024-0736', '2026-05-10', 12, '2027-05-10', 'active', '2026-05-10 06:07:48', '2026-09-14 18:34:23'),
(24, 12, 25, 'WC-00025-9A26', '2026-05-22', 12, '2027-05-22', 'active', '2026-05-22 02:10:52', '2026-09-14 18:34:23'),
(25, 13, 26, 'WC-00026-E8A6', '2026-05-10', 12, '2027-05-10', 'active', '2026-05-10 05:03:49', '2026-09-14 18:34:23'),
(26, 13, 27, 'WC-00027-54A4', '2026-05-10', 12, '2027-05-10', 'active', '2026-05-10 05:03:49', '2026-09-14 18:34:23'),
(27, 14, 28, 'WC-00028-9859', '2026-05-24', 12, '2027-05-24', 'active', '2026-05-24 08:05:29', '2026-09-14 18:34:23'),
(28, 14, 29, 'WC-00029-DD92', '2026-05-24', 24, '2028-05-24', 'active', '2026-05-24 08:05:29', '2026-09-14 18:34:23'),
(29, 15, 30, 'WC-00030-FDD5', '2026-06-14', 12, '2027-06-14', 'active', '2026-06-14 10:40:08', '2026-09-14 18:34:23'),
(30, 16, 31, 'WC-00031-4ED8', '2026-06-06', 12, '2027-06-06', 'active', '2026-06-06 07:00:21', '2026-09-14 18:34:23'),
(31, 16, 32, 'WC-00032-F6E4', '2026-06-06', 24, '2028-06-06', 'active', '2026-06-06 07:00:21', '2026-09-14 18:34:23'),
(32, 17, 33, 'WC-00033-698F', '2026-06-13', 24, '2028-06-13', 'active', '2026-06-13 02:49:08', '2026-09-14 18:34:23'),
(33, 17, 34, 'WC-00034-CFD7', '2026-06-13', 6, '2026-12-13', 'active', '2026-06-13 02:49:08', '2026-09-14 18:34:23'),
(34, 17, 35, 'WC-00035-838B', '2026-06-13', 24, '2028-06-13', 'active', '2026-06-13 02:49:08', '2026-09-14 18:34:23'),
(35, 18, 36, 'WC-00036-B53F', '2026-06-28', 12, '2027-06-28', 'active', '2026-06-28 09:35:54', '2026-09-14 18:34:23'),
(36, 19, 37, 'WC-00037-67D3', '2026-06-09', 18, '2027-12-09', 'active', '2026-06-09 09:17:02', '2026-09-14 18:34:23'),
(37, 20, 38, 'WC-00038-3010', '2026-06-10', 24, '2028-06-10', 'active', '2026-06-10 07:49:24', '2026-09-14 18:34:23'),
(38, 20, 39, 'WC-00039-0845', '2026-06-10', 24, '2028-06-10', 'active', '2026-06-10 07:49:24', '2026-09-14 18:34:23'),
(39, 21, 40, 'WC-00040-0BAB', '2026-06-20', 12, '2027-06-20', 'active', '2026-06-20 05:45:18', '2026-09-14 18:34:23'),
(40, 22, 41, 'WC-00041-2798', '2026-06-05', 24, '2028-06-05', 'active', '2026-06-05 03:20:34', '2026-09-14 18:34:23'),
(41, 22, 42, 'WC-00042-251F', '2026-06-05', 24, '2028-06-05', 'active', '2026-06-05 03:20:34', '2026-09-14 18:34:23'),
(42, 24, 44, 'WC-00044-DA46', '2026-07-24', 12, '2027-07-24', 'active', '2026-07-24 10:04:17', '2026-09-14 18:34:23'),
(43, 25, 45, 'WC-00045-47D6', '2026-07-26', 12, '2027-07-26', 'active', '2026-07-26 07:28:42', '2026-09-14 18:34:23'),
(44, 26, 46, 'WC-00046-9F96', '2026-07-21', 12, '2027-07-21', 'active', '2026-07-21 11:20:57', '2026-09-14 18:34:23'),
(45, 26, 47, 'WC-00047-ACDF', '2026-07-21', 12, '2027-07-21', 'active', '2026-07-21 11:20:57', '2026-09-14 18:34:23'),
(46, 26, 48, 'WC-00048-2FF7', '2026-07-21', 12, '2027-07-21', 'active', '2026-07-21 11:20:57', '2026-09-14 18:34:23'),
(47, 27, 49, 'WC-00049-29AB', '2026-07-10', 12, '2027-07-10', 'active', '2026-07-10 08:39:07', '2026-09-14 18:34:23'),
(48, 27, 50, 'WC-00050-FC82', '2026-07-10', 24, '2028-07-10', 'active', '2026-07-10 08:39:07', '2026-09-14 18:34:23'),
(49, 27, 51, 'WC-00051-4677', '2026-07-10', 12, '2027-07-10', 'active', '2026-07-10 08:39:07', '2026-09-14 18:34:23'),
(50, 28, 52, 'WC-00052-8D0B', '2026-07-12', 12, '2027-07-12', 'active', '2026-07-12 07:29:19', '2026-09-14 18:34:23'),
(51, 29, 53, 'WC-00053-63CC', '2026-07-07', 36, '2029-07-07', 'active', '2026-07-07 07:18:28', '2026-09-14 18:34:23'),
(52, 30, 54, 'WC-00054-3DE9', '2026-07-27', 18, '2028-01-27', 'active', '2026-07-27 09:54:26', '2026-09-14 18:34:23'),
(53, 31, 55, 'WC-00055-739C', '2026-07-10', 24, '2028-07-10', 'active', '2026-07-10 09:07:40', '2026-09-14 18:34:23'),
(54, 31, 56, 'WC-00056-E0DB', '2026-07-10', 24, '2028-07-10', 'active', '2026-07-10 09:07:40', '2026-09-14 18:34:23'),
(55, 32, 57, 'WC-00057-23CD', '2026-07-27', 12, '2027-07-27', 'active', '2026-07-27 06:51:53', '2026-09-14 18:34:23'),
(56, 32, 58, 'WC-00058-FEDA', '2026-07-27', 12, '2027-07-27', 'active', '2026-07-27 06:51:53', '2026-09-14 18:34:23'),
(57, 32, 59, 'WC-00059-06A6', '2026-07-27', 24, '2028-07-27', 'active', '2026-07-27 06:51:53', '2026-09-14 18:34:23'),
(58, 33, 60, 'WC-00060-AAB0', '2026-08-10', 24, '2028-08-10', 'active', '2026-08-10 11:49:04', '2026-09-14 18:34:23'),
(59, 34, 61, 'WC-00061-4340', '2026-08-18', 36, '2029-08-18', 'active', '2026-08-18 04:13:46', '2026-09-14 18:34:23'),
(60, 34, 62, 'WC-00062-1D75', '2026-08-18', 12, '2027-08-18', 'active', '2026-08-18 04:13:46', '2026-09-14 18:34:23'),
(61, 35, 63, 'WC-00063-A9F4', '2026-08-05', 24, '2028-08-05', 'active', '2026-08-05 08:35:46', '2026-09-14 18:34:23'),
(62, 35, 64, 'WC-00064-37E4', '2026-08-05', 24, '2028-08-05', 'active', '2026-08-05 08:35:46', '2026-09-14 18:34:23'),
(63, 35, 65, 'WC-00065-E80A', '2026-08-05', 12, '2027-08-05', 'active', '2026-08-05 08:35:46', '2026-09-14 18:34:23'),
(64, 36, 66, 'WC-00066-12B2', '2026-08-27', 12, '2027-08-27', 'active', '2026-08-27 08:43:30', '2026-09-14 18:34:23'),
(65, 36, 67, 'WC-00067-1FB9', '2026-08-27', 12, '2027-08-27', 'active', '2026-08-27 08:43:30', '2026-09-14 18:34:23'),
(66, 36, 68, 'WC-00068-A7D3', '2026-08-27', 24, '2028-08-27', 'active', '2026-08-27 08:43:30', '2026-09-14 18:34:23'),
(67, 37, 69, 'WC-00069-3850', '2026-08-16', 24, '2028-08-16', 'active', '2026-08-16 03:39:26', '2026-09-14 18:34:23'),
(68, 37, 70, 'WC-00070-251D', '2026-08-16', 12, '2027-08-16', 'active', '2026-08-16 03:39:26', '2026-09-14 18:34:23'),
(69, 37, 71, 'WC-00071-7CBC', '2026-08-16', 24, '2028-08-16', 'active', '2026-08-16 03:39:26', '2026-09-14 18:34:23'),
(70, 38, 72, 'WC-00072-5C05', '2026-08-21', 12, '2027-08-21', 'active', '2026-08-21 08:57:50', '2026-09-14 18:34:23'),
(71, 38, 73, 'WC-00073-881A', '2026-08-21', 24, '2028-08-21', 'active', '2026-08-21 08:57:50', '2026-09-14 18:34:23'),
(72, 38, 74, 'WC-00074-DD17', '2026-08-21', 12, '2027-08-21', 'active', '2026-08-21 08:57:50', '2026-09-14 18:34:23'),
(73, 39, 75, 'WC-00075-5D6B', '2026-08-11', 24, '2028-08-11', 'active', '2026-08-11 03:35:29', '2026-09-14 18:34:23'),
(74, 40, 76, 'WC-00076-A789', '2026-08-12', 12, '2027-08-12', 'active', '2026-08-12 07:05:42', '2026-09-14 18:34:23'),
(75, 41, 77, 'WC-00077-666E', '2026-08-04', 12, '2027-08-04', 'active', '2026-08-04 03:53:42', '2026-09-14 18:34:23'),
(76, 42, 78, 'WC-00078-B41E', '2026-08-10', 24, '2028-08-10', 'active', '2026-08-10 06:49:23', '2026-09-14 18:34:23'),
(77, 44, 80, 'WC-00080-0B82', '2026-09-10', 12, '2027-09-10', 'active', '2026-09-10 11:19:48', '2026-09-14 18:34:23'),
(78, 45, 81, 'WC-00081-B6E2', '2026-09-08', 36, '2029-09-08', 'active', '2026-09-08 09:10:08', '2026-09-14 18:34:23'),
(79, 46, 82, 'WC-00082-64F9', '2026-09-12', 6, '2027-03-12', 'active', '2026-09-12 10:41:35', '2026-09-14 18:34:23'),
(80, 46, 83, 'WC-00083-7707', '2026-09-12', 12, '2027-09-12', 'active', '2026-09-12 10:41:35', '2026-09-14 18:34:23'),
(81, 47, 84, 'WC-00084-F594', '2026-09-08', 12, '2027-09-08', 'active', '2026-09-08 08:29:31', '2026-09-14 18:34:23'),
(82, 47, 85, 'WC-00085-BBC0', '2026-09-08', 12, '2027-09-08', 'active', '2026-09-08 08:29:31', '2026-09-14 18:34:23'),
(83, 47, 86, 'WC-00086-3D43', '2026-09-08', 36, '2029-09-08', 'active', '2026-09-08 08:29:31', '2026-09-14 18:34:23'),
(84, 48, 87, 'WC-00087-8A42', '2026-09-13', 24, '2028-09-13', 'active', '2026-09-13 06:09:44', '2026-09-14 18:34:23'),
(85, 48, 88, 'WC-00088-8D94', '2026-09-13', 12, '2027-09-13', 'active', '2026-09-13 06:09:44', '2026-09-14 18:34:23'),
(86, 49, 89, 'WC-00089-C7AD', '2026-09-14', 24, '2028-09-14', 'active', '2026-09-14 04:07:12', '2026-09-14 18:34:23'),
(87, 49, 90, 'WC-00090-D2D2', '2026-09-14', 24, '2028-09-14', 'active', '2026-09-14 04:07:12', '2026-09-14 18:34:23'),
(88, 50, 91, 'WC-00091-DAA5', '2026-09-13', 12, '2027-09-13', 'active', '2026-09-13 08:30:09', '2026-09-14 18:34:23'),
(89, 51, 92, 'WC-00092-24C7', '2026-09-07', 24, '2028-09-07', 'active', '2026-09-07 06:27:29', '2026-09-14 18:34:23'),
(90, 51, 93, 'WC-00093-814E', '2026-09-07', 36, '2029-09-07', 'active', '2026-09-07 06:27:29', '2026-09-14 18:34:23'),
(91, 51, 94, 'WC-00094-5C1A', '2026-09-07', 18, '2028-03-07', 'active', '2026-09-07 06:27:29', '2026-09-14 18:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `warranty_history`
--

CREATE TABLE `warranty_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `warranty_id` int(10) UNSIGNED NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_admin_email` (`email`);

--
-- Indexes for table `ai_error_log`
--
ALTER TABLE `ai_error_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_log_time` (`thoi_gian`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_module` (`module`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banner_pos` (`position`),
  ADD KEY `idx_banner_status` (`status`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_brand_slug` (`slug`);

--
-- Indexes for table `carrier_rates`
--
ALTER TABLE `carrier_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_carrier_zone` (`zone`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_cart_item` (`session_id`,`user_id`,`product_id`,`variant_id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `fk_cart_prod` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_cat_slug` (`slug`),
  ADD KEY `idx_cat_parent` (`parent_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_session` (`session_id`,`id`),
  ADD KEY `idx_chat_sender` (`sender_type`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cmt_prod` (`product_id`),
  ADD KEY `idx_cmt_parent` (`parent_id`),
  ADD KEY `fk_cmt_user` (`user_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_status` (`status`);

--
-- Indexes for table `deleted_order_logs`
--
ALTER TABLE `deleted_order_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_del_order` (`order_id`),
  ADD KEY `idx_del_at` (`deleted_at`);

--
-- Indexes for table `goong_cache`
--
ALTER TABLE `goong_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_goong_cache_key` (`cache_key`),
  ADD KEY `idx_goong_created` (`created_at`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_inv_code` (`invoice_code`),
  ADD UNIQUE KEY `unq_inv_number` (`invoice_number`),
  ADD KEY `idx_inv_order` (`order_id`),
  ADD KEY `idx_inv_original` (`original_invoice_id`),
  ADD KEY `idx_inv_date` (`invoice_date`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invitem_invoice` (`invoice_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_log_created` (`created_at`);

--
-- Indexes for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mb_tier` (`tier_id`);

--
-- Indexes for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_news_slug` (`slug`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`),
  ADD KEY `idx_notif_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_order_code` (`order_code`),
  ADD UNIQUE KEY `unq_order_invoice` (`invoice_number`),
  ADD KEY `idx_order_user` (`user_id`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `idx_order_phone` (`customer_phone`),
  ADD KEY `idx_order_payment` (`payment_status`),
  ADD KEY `idx_order_invoice` (`invoice_number`);

--
-- Indexes for table `order_cancel_requests`
--
ALTER TABLE `order_cancel_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cancel_order` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oi_order` (`order_id`),
  ADD KEY `idx_oi_prod` (`product_id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_phone` (`phone`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions` (`module`,`action`);

--
-- Indexes for table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pt_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_prod_slug` (`slug`),
  ADD UNIQUE KEY `unq_prod_sku` (`sku`),
  ADD KEY `idx_prod_cat` (`category_id`),
  ADD KEY `idx_prod_brand` (`brand_id`),
  ADD KEY `idx_prod_status` (`status`),
  ADD KEY `idx_prod_tu_khoa` (`tu_khoa_tim_kiem`),
  ADD KEY `idx_prod_featured` (`is_featured`),
  ADD KEY `idx_prod_sold` (`sold_count`),
  ADD KEY `idx_prod_tax` (`tax_rate_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pimg_prod` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pvar_prod` (`product_id`);

--
-- Indexes for table `product_videos`
--
ALTER TABLE `product_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pvid_prod` (`product_id`);

--
-- Indexes for table `province_distances`
--
ALTER TABLE `province_distances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_province` (`province`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refund_order` (`order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rev_prod` (`product_id`),
  ADD KEY `idx_rev_user` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_code` (`code`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_perm` (`role_id`,`permission_id`),
  ADD KEY `idx_perm` (`permission_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_setting_key` (`setting_key`);

--
-- Indexes for table `ship_fee_brackets`
--
ALTER TABLE `ship_fee_brackets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tax_status` (`status`),
  ADD KEY `idx_tax_default` (`is_default`);

--
-- Indexes for table `trust_logs`
--
ALTER TABLE `trust_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tl_phone` (`phone`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_user_email` (`email`),
  ADD UNIQUE KEY `unq_user_phone` (`phone`),
  ADD UNIQUE KEY `unq_user_google_id` (`google_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_trust_level` (`trust_level`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `fk_users_tier` (`membership_tier_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_voucher_code` (`code`),
  ADD KEY `idx_voucher_type` (`type`),
  ADD KEY `idx_voucher_status` (`status`),
  ADD KEY `idx_voucher_required_tier` (`required_tier_id`);

--
-- Indexes for table `vtp_categories`
--
ALTER TABLE `vtp_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_code_parent` (`code`,`parent_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_parent_name` (`parent_id`,`name`);

--
-- Indexes for table `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_warranty_serial` (`serial_no`),
  ADD KEY `idx_warranty_order` (`order_id`),
  ADD KEY `fk_warranty_item` (`order_item_id`);

--
-- Indexes for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wh_warranty` (`warranty_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_wish` (`user_id`,`product_id`),
  ADD KEY `idx_wish_prod` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ai_error_log`
--
ALTER TABLE `ai_error_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `carrier_rates`
--
ALTER TABLE `carrier_rates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_order_logs`
--
ALTER TABLE `deleted_order_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goong_cache`
--
ALTER TABLE `goong_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `order_cancel_requests`
--
ALTER TABLE `order_cancel_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `points_transactions`
--
ALTER TABLE `points_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_videos`
--
ALTER TABLE `product_videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `province_distances`
--
ALTER TABLE `province_distances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `ship_fee_brackets`
--
ALTER TABLE `ship_fee_brackets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `trust_logs`
--
ALTER TABLE `trust_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vtp_categories`
--
ALTER TABLE `vtp_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `warranties`
--
ALTER TABLE `warranties`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `warranty_history`
--
ALTER TABLE `warranty_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_cart_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_cmt_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cmt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_inv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_inv_original` FOREIGN KEY (`original_invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invitem_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  ADD CONSTRAINT `fk_mb_tier` FOREIGN KEY (`tier_id`) REFERENCES `membership_tiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_cancel_requests`
--
ALTER TABLE `order_cancel_requests`
  ADD CONSTRAINT `fk_cancel_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_prod_tax` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pimg_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_pvar_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_videos`
--
ALTER TABLE `product_videos`
  ADD CONSTRAINT `fk_pvid_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rev_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_tier` FOREIGN KEY (`membership_tier_id`) REFERENCES `membership_tiers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `warranties`
--
ALTER TABLE `warranties`
  ADD CONSTRAINT `fk_warranty_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_warranty_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD CONSTRAINT `fk_wh_warranty` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wish_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
