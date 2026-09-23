<?php
/**
 * WoodCon - Chatbot AJAX Endpoint
 * POST { message, session_id }
 * Trả về: { reply }
 * Xử lý function calling: goi_ai -> function_call -> chạy hàm -> goi_ai lần 2 với kết quả.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai-provider.php';

use WoodCon\Database;
use WoodCon\Product;
use WoodCon\Cart;
use WoodCon\Order;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$message   = trim((string)($input['message'] ?? ''));
$sessionId = trim((string)($input['session_id'] ?? ''));

// CSRF: request dạng JSON nên token gửi kèm trong body (_token)
// Dùng 403 thay vì 419 (verify_csrf): một số bản Apache/XAMPP map 419 -> 500 gây hiểu nhầm máy chủ lỗi.
if (!verify_csrf((string)($input['_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['error' => 'Phiên làm việc hết hạn. Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($message === '' || $sessionId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Thieu message hoac session_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message   = mb_substr($message, 0, 500, 'UTF-8');
$sessionId = mb_substr($sessionId, 0, 64, 'UTF-8');

$db = Database::connect();

// Luu tin nhan user
$db->prepare('INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, "user", ?)')
   ->execute([$sessionId, $message]);

// Lay lich su hoi thoai gan nhat (20 tin nhan)
$history = $db->prepare(
    'SELECT sender_type, message FROM chat_messages WHERE session_id = ? ORDER BY id DESC LIMIT 20'
);
$history->execute([$sessionId]);
$rawMessages = array_reverse($history->fetchAll());

// Chuyen sang dinh dang Gemini messages
$messages = [];
foreach ($rawMessages as $row) {
    $role = $row['sender_type'] === 'user' ? 'user' : 'model';
    $messages[] = ['role' => $role, 'parts' => [['text' => $row['message']]]];
}

// Define tools (function declarations) cho Gemini
$tools = [
    [
        'name'        => 'tim_san_pham',
        'description' => 'Tim kiem san pham noi that theo tu khoa. Tra ve danh sach san pham phu hop.',
        'parameters'  => [
            'type'       => 'OBJECT',
            'properties' => [
                'tu_khoa_tim_kiem' => [
                    'type'        => 'STRING',
                    'description' => 'Tu khoa tim kiem (VD: "sofa go soi", "ban an 4 nguoi")',
                ],
            ],
            'required' => ['tu_khoa_tim_kiem'],
        ],
    ],
    [
        'name'        => 'xem_chi_tiet_san_pham',
        'description' => 'Xem thong tin chi tiet cua mot san pham theo ma ID.',
        'parameters'  => [
            'type'       => 'OBJECT',
            'properties' => [
                'san_pham_id' => [
                    'type'        => 'INTEGER',
                    'description' => 'ID san pham can xem chi tiet',
                ],
            ],
            'required' => ['san_pham_id'],
        ],
    ],
    [
        'name'        => 'tra_cuu_don_hang',
        'description' => 'Tra cuu thong tin va trang thai don hang bang ma don hang (VD: WC250901AB12).',
        'parameters'  => [
            'type'       => 'OBJECT',
            'properties' => [
                'ma_don_hang' => [
                    'type'        => 'STRING',
                    'description' => 'Ma don hang can tra cuu',
                ],
            ],
            'required' => ['ma_don_hang'],
        ],
    ],
    [
        'name'        => 'them_vao_gio_hang',
        'description' => 'Them mot san pham vao gio hang.',
        'parameters'  => [
            'type'       => 'OBJECT',
            'properties' => [
                'san_pham_id' => [
                    'type'        => 'INTEGER',
                    'description' => 'ID san pham can them vao gio',
                ],
                'so_luong' => [
                    'type'        => 'INTEGER',
                    'description' => 'So luong muon them (mac dinh 1)',
                ],
            ],
            'required' => ['san_pham_id'],
        ],
    ],
];

// Goi AI lan 1
$result      = goi_ai($messages, $tools);
$functionCall = $result['function_call'];
$replyText    = $result['reply'];
$products     = [];   // san pham de render the ben phia frontend

// Xu ly function calling (chi cho phep 1 vong)
if ($functionCall !== null) {
    $fnName   = $functionCall['name'];
    $fnArgs   = $functionCall['args'];
    $fnResult = _execute_function($fnName, $fnArgs);

    // Neu ham tra ve san pham -> giu lai de render the co anh ben frontend
    if (!empty($fnResult['__products'])) {
        $products = $fnResult['__products'];
    }

    // Dung chinh part goc tu model (kèm thoughtSignature/id) — Gemini 3.x bat buoc,
    // khong tu build lai functionCall (thieu thought_signature se bi loi 400).
    $modelPart = $result['function_call_part'] ?? null;
    if ($modelPart === null) {
        $modelPart = ['functionCall' => ['name' => $fnName, 'args' => $fnArgs]];
    }

    $messages[] = ['role' => 'model', 'parts' => [$modelPart]];
    $messages[] = [
        'role'  => 'user',
        'parts' => [['functionResponse' => ['name' => $fnName, 'response' => $fnResult]]],
    ];

    $result2   = goi_ai($messages);
    $replyText = $result2['reply'] ?: $replyText;
}

// Luu tin nhan bot
if ($replyText !== '') {
    $db->prepare('INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, "bot", ?)')
       ->execute([$sessionId, $replyText]);
}

echo json_encode([
    'reply'    => $replyText,
    'products' => $products,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

// ---------------------------------------------------------------
// Function handlers
// ---------------------------------------------------------------

function _execute_function(string $name, array $args): array
{
    return match ($name) {
        'tim_san_pham'          => _fn_tim_san_pham($args),
        'xem_chi_tiet_san_pham' => _fn_xem_chi_tiet($args),
        'tra_cuu_don_hang'      => _fn_tra_cuu_don_hang($args),
        'them_vao_gio_hang'     => _fn_them_vao_gio_hang($args),
        default                 => ['error' => "Ham '{$name}' khong ton tai."],
    };
}

function _fn_tim_san_pham(array $args): array
{
    $q = trim((string)($args['tu_khoa_tim_kiem'] ?? ''));
    if ($q === '') {
        return ['error' => 'Vui long nhap tu khoa tim kiem.'];
    }

    $db  = Database::connect();
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $likeNoAccent = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], str_lower_no_accent($q)) . '%';

    $stmt = $db->prepare(
        'SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.cover_image, p.summary
         FROM products p
         WHERE p.status = 1
           AND (p.name LIKE ? OR p.summary LIKE ? OR p.sku LIKE ? OR p.tu_khoa_tim_kiem LIKE ?)
         ORDER BY p.is_best_seller DESC, p.sold_count DESC, p.view_count DESC, p.id DESC
         LIMIT 5'
    );
    $stmt->execute([$like, $like, $like, $likeNoAccent]);
    $products = $stmt->fetchAll();

    if (empty($products)) {
        return ['found' => false, 'message' => 'Khong tim thay san pham phu hop.'];
    }

    $list = [];
    foreach ($products as $p) {
        $price      = Product::effectivePrice($p);
        $hasSale    = !empty($p['sale_price']) && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price'];
        $list[] = [
            'id'        => (int)$p['id'],
            'name'      => $p['name'],
            'price'     => format_money($price),
            'has_sale'  => $hasSale,
            'original_price' => $hasSale ? format_money((float)$p['price']) : '',
            'url'       => BASE_URL . '/san-pham/' . $p['slug'],
            'image'     => image_url($p['cover_image']),
        ];
    }

    return [
        'found'        => true,
        'products'     => $list,          // cho AI đọc
        '__products'   => $list,          // cho frontend render thẻ
    ];
}

function _fn_xem_chi_tiet(array $args): array
{
    $id = (int)($args['san_pham_id'] ?? 0);
    if ($id <= 0) {
        return ['error' => 'Ma san pham khong hop le.'];
    }

    $product = Product::find($id);
    if (!$product || !$product['status']) {
        return ['error' => 'Khong tim thay san pham.'];
    }

    $price     = Product::effectivePrice($product);
    $salePrice = !empty($product['sale_price']) && (float)$product['sale_price'] > 0
        ? (float)$product['sale_price']
        : null;
    $stats = Product::reviewStats($id);

    return [
        'found'        => true,
        'name'         => $product['name'],
        'price'        => format_money($price),
        'original'     => format_money((float)$product['price']),
        'has_sale'     => $salePrice !== null,
        'material'     => $product['material'] ?? '',
        'dimension'    => $product['dimension'] ?? '',
        'warranty'     => $product['warranty_months'] . ' thang',
        'in_stock'     => (int)$product['quantity'] > 0,
        'sold_count'   => (int)$product['sold_count'],
        'rating'       => $stats['avg'],
        'review_count' => $stats['total'],
        'url'          => BASE_URL . '/san-pham/' . $product['slug'],
        'image'        => image_url($product['cover_image']),
    ];
}

function _fn_tra_cuu_don_hang(array $args): array
{
    $code = trim((string)($args['ma_don_hang'] ?? ''));
    if ($code === '') {
        return ['error' => 'Vui long nhap ma don hang.'];
    }

    $order = Order::byCode($code);
    if (!$order) {
        return ['found' => false, 'message' => "Khong tim thay don hang '{$code}'. Vui long kiem tra lai ma don."];
    }

    $statusLabel = Order::STATUS_LABEL[$order['order_status']] ?? $order['order_status'];

    return [
        'found'      => true,
        'ma_don'     => $order['order_code'],
        'trang_thai' => $statusLabel,
        'ngay_dat'   => format_date($order['created_at']),
        'tong_tien'  => format_money((float)($order['total_amount'] ?? 0)),
    ];
}

function _fn_them_vao_gio_hang(array $args): array
{
    $productId = (int)($args['san_pham_id'] ?? 0);
    $quantity  = max(1, (int)($args['so_luong'] ?? 1));

    if ($productId <= 0) {
        return ['error' => 'Ma san pham khong hop le.'];
    }

    $product = Product::find($productId);
    if (!$product || !$product['status']) {
        return ['error' => 'Khong tim thay san pham.'];
    }

    if ((int)$product['quantity'] <= 0) {
        return ['error' => 'San pham hien da het hang.'];
    }

    $ok = Cart::add($productId, $quantity);
    if (!$ok) {
        return ['error' => 'Khong the them vao gio hang. Vui long thu lai.'];
    }

    $price = Product::effectivePrice($product);

    return [
        'success'      => true,
        'message'      => "Da them \"{$product['name']}\" vao gio hang.",
        'san_pham'     => $product['name'],
        'gia'          => format_money($price),
        'so_luong'     => $quantity,
        'gio_hang_url' => BASE_URL . '/gio-hang',
    ];
}