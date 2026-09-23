<?php
/**
 * WoodCon - Kết nối PDO (singleton)
 * Sử dụng PDO Prepared Statement chống SQL Injection ở mọi truy vấn.
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+07:00'",
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Không lộ thông tin kết nối ra ngoài
                http_response_code(500);
                die('Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra DB_* trong file .env và đã import file database/schema.sql.');
            }
        }
        return self::$instance;
    }
}