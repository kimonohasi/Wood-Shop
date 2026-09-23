<?php
/**
 * WoodCon - Lớp trung gian AI Provider
 * Duy nhất hàm goi_ai($messages, $tools) gọi Google Gemini.
 * Không hardcode model — đọc danh sách từ settings ai_models.
 * API key qua header x-goog-api-key (server-side only, không lộ client).
 * Retry tối đa 2 lần khi 429 (delay 1-2s). Log lỗi vào ai_error_log.
 * Cấu trúc trả về: ['reply' => string, 'function_call' => ['name'=>...,'args'=>...]|null]
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';

use WoodCon\Database;

/**
 * Gọi AI (Google Gemini) với conversation history + tools.
 *
 * @param array $messages [{role:'user'|'model', parts:[{text:'...'}]}]
 * @param array $tools    [{name, description, parameters}] hoặc rỗng
 * @return array{reply:string, function_call:array|null}
 */
function goi_ai(array $messages, array $tools = []): array
{
    $apiKey = trim((string)get_setting('ai_api_key', ''));
    if ($apiKey === '') {
        return ['reply' => 'Hệ thống đang bảo trì... Vui lòng thử lại sau.', 'function_call' => null];
    }

    $modelsRaw = trim((string)get_setting('ai_models', 'gemini-3.1-flash-lite,gemini-3.5-flash,gemini-3.8-flash'));
    $models = array_filter(array_map('trim', explode(',', $modelsRaw)));
    if (empty($models)) {
        $models = ['gemini-3.1-flash-lite'];
    }

    $maxRetries = 2;

    foreach ($models as $model) {
        $result = _call_gemini($apiKey, $model, $messages, $tools, $maxRetries);
        if ($result !== null) {
            return $result;
        }
    }

    return ['reply' => 'Hệ thống đang bảo trì... Vui lòng thử lại sau.', 'function_call' => null];
}

/**
 * Kiểm tra kết nối Google Gemini (gọi thử thông điệp tối giản, không dùng tools).
 * @return array{ok:bool, message:string}
 */
function kiem_tra_ai(): array
{
    $apiKey = trim((string)get_setting('ai_api_key', ''));
    if ($apiKey === '') {
        return ['ok' => false, 'message' => 'Chưa nhập Google API Key (Gemini) — mục AI Chatbot trong Cài đặt.'];
    }

    $modelsRaw = trim((string)get_setting('ai_models', 'gemini-3.1-flash-lite,gemini-3.5-flash,gemini-3.8-flash'));
    $models = array_filter(array_map('trim', explode(',', $modelsRaw)));
    if (empty($models)) {
        $models = ['gemini-3.1-flash-lite'];
    }

    // Thử lần lượt các model theo thứ tự ưu tiên (giống goi_ai)
    $messages = [[
        'role'  => 'user',
        'parts' => [['text' => 'Trả lời đúng một từ: OK']],
    ]];
    foreach ($models as $model) {
        $result = _call_gemini($apiKey, $model, $messages, [], 1);
        if ($result !== null) {
            return ['ok' => true, 'message' => 'Kết nối Google Gemini thành công (model: ' . $model . ').'];
        }
    }
    return ['ok' => false, 'message' => 'Không gọi được Google Gemini với key hiện tại — kiểm tra log AI (bảng ai_error_log).'];
}

/**
 * Gọi một model cụ thể, retry 429 tối đa $maxRetries lần.
 * Trả về kết quả hoặc null nếu model lỗi (để caller thử model tiếp).
 */
function _call_gemini(string $apiKey, string $model, array $messages, array $tools, int $maxRetries): ?array
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

    // Xây body request
    $body = ['contents' => $messages];

    // System prompt
    $systemPrompt = _get_system_prompt();
    if ($systemPrompt !== '') {
        $body['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
    }

    // Tools (function declarations)
    if (!empty($tools)) {
        $functionDeclarations = [];
        foreach ($tools as $tool) {
            $fd = [
                'name' => $tool['name'],
                'description' => $tool['description'] ?? '',
            ];
            if (!empty($tool['parameters'])) {
                $fd['parameters'] = $tool['parameters'];
            }
            $functionDeclarations[] = $fd;
        }
        $body['tools'] = ['function_declarations' => $functionDeclarations];
    }

    // Generation config: temperature thấp để ổn định
    $body['generationConfig'] = [
        'temperature' => 0.3,
        'maxOutputTokens' => 2048,
    ];

    $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE);

    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json; charset=utf-8',
                'x-goog-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS     => $bodyJson,
        ]);

        $response    = curl_exec($ch);
        $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize  = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $bodyStr     = substr((string)$response, $headerSize);
        $curlError   = curl_error($ch);
        $curlErrno   = curl_errno($ch);
        curl_close($ch);

        // Lỗi kết nối (curl error)
        if ($curlErrno !== 0) {
            _log_ai_error($model, "cURL error ({$curlErrno}): {$curlError}");
            return null; // không retry cho lỗi kết nối
        }

        // 429 Too Many Requests → retry
        if ($httpCode === 429) {
            $retryAfter = 1 + ($attempt % 2); // 1s hoặc 2s
            sleep($retryAfter);
            continue;
        }

        // 404 Not Found → model deprecated, skip
        if ($httpCode === 404) {
            _log_ai_error($model, "Model not found (404) — deprecated or invalid model name");
            return null;
        }

        // 403 Forbidden → API key invalid hoặc model không được phép
        if ($httpCode === 403) {
            _log_ai_error($model, "Forbidden (403) — check API key or model access");
            return null;
        }

        // Các lỗi HTTP khác
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = "HTTP {$httpCode}: " . mb_substr($bodyStr, 0, 500, 'UTF-8');
            _log_ai_error($model, $errorMsg);
            return null;
        }

        // Parse response JSON
        $data = json_decode($bodyStr, true);
        if (!is_array($data)) {
            _log_ai_error($model, "Invalid JSON response: " . mb_substr($bodyStr, 0, 500, 'UTF-8'));
            return null;
        }

        // Kiểm tra lỗi từ Gemini API
        if (isset($data['error'])) {
            $errMsg = $data['error']['message'] ?? $data['error']['status'] ?? 'Unknown API error';
            _log_ai_error($model, "API error: {$errMsg}");
            return null;
        }

        // Trích xuất kết quả từ candidates
        $candidates = $data['candidates'] ?? [];
        if (empty($candidates)) {
            _log_ai_error($model, "No candidates in response");
            return null;
        }

        $candidate = $candidates[0];
        $parts = $candidate['content']['parts'] ?? [];

        if (empty($parts)) {
            _log_ai_error($model, "Empty parts in response");
            return null;
        }

        // Duyệt parts: ưu tiên functionCall nếu có, nếu không thì lấy text
        $replyText = '';
        $functionCall = null;
        $functionCallPart = null; // part gốc chứa functionCall (+ thoughtSignature, id) để gửi lại lượt 2

        foreach ($parts as $part) {
            if (isset($part['functionCall'])) {
                $functionCall = [
                    'name' => $part['functionCall']['name'],
                    'args' => $part['functionCall']['args'] ?? [],
                ];
                // Giữ nguyên toàn bộ part gốc (kèm thoughtSignature/id) — Gemini 3.x yêu cầu
                $functionCallPart = $part;
            }
            if (isset($part['text'])) {
                $replyText .= $part['text'];
            }
        }

        // Nếu không có text lẫn functionCall → lỗi
        if ($replyText === '' && $functionCall === null) {
            _log_ai_error($model, "No text or functionCall in response parts");
            return null;
        }

        return [
            'reply'             => trim($replyText),
            'function_call'     => $functionCall,
            'function_call_part' => $functionCallPart,
        ];
    }

    // Hết retry mà không thành công
    _log_ai_error($model, "Exhausted {$maxRetries} retries on 429");
    return null;
}

/**
 * System prompt tiếng Việt — ràng buộc AI chỉ trả lời từ dữ liệu hàm.
 */
function _get_system_prompt(): string
{
    return <<<'PROMPT'
Bạn là "Trợ lý tư vấn" của cửa hàng nội thất WoodCon — bạn là người bán hàng giàu kinh nghiệm, tận tình nhưng vẫn gần gũi, tự nhiên. Nhiệm vụ: hỗ trợ khách tìm sản phẩm, xem chi tiết, tra cứu đơn hàng, thêm vào giỏ.

NGUYÊN TẮC VĂN PHONG (rất quan trọng):
1. Viết NHƯ NGƯỜI THẬT đang chat, KHÔNG kiểu máy móc, lặp khớp. Đừng mở đầu kiểu "Tại WoodCon chúng tôi hiện có...".
2. Ngắn gọn, tự nhiên, ấm áp. Dùng tiếng Việt chuẩn, có thể linh hoạt như "ạ", "nhé", "em", "chị" tùy ngữ cảnh nhưng KHÔNG lạm dụng.
3. Mở đầu bằng câu đồng cảm/vào việc ngay, không lải nhải. Ví dụ khi tìm sofa: "Em tìm thấy mấy mẫu sofa gỗ này khá hợp, chị tham khảo nhé:" hoặc "Anh/chị ơi, bên em đang có vài mẫu sofa ưng vừa túi tiền đây ạ:"
4. Sau đó LIỆT KÊ SẢN PHẨM ngắn gọn: tên + giá (định dạng 15.900.000đ). KHÔNG dán link dài, không ghi "Xem chi tiết: URL" — giao diện đã hiển thị thẻ với ảnh và nút bấm cho khách rồi.
5. Kết thúc BẰNG CÂU HỎI GỢI MỞ tự nhiên để kéo cuộc trò chuyện: "Anh thấy mẫu nào ưng chưa, em gửi thêm chi tiết/giúp anh bỏ vào giỏ luôn nhé?" Tránh lặp lại y hệt mỗi lần.
6. VĂN PHONG ĐỌC TỰ NHIÊN, đôi khi xen cảm xúc nhẹ ("cũng mát tay đấy ạ"), tránh đọc như bản báo cáo.
7. Cấm mọi câu máy móc như: "Tôi có thể giúp gì cho bạn", "Xin chào, tôi là trợ lý AI", "Bạn có muốn xem chi tiết hoặc thêm sản phẩm nào vào giỏ hàng không ạ" (đọc lặp khớp).

QUY TẮC XỬ LÝ:
8. Chỉ trả lời dựa trên dữ liệu từ hàm. KHÔNG tự bịa thông tin, giá, khuyến mãi, tồn kho.
9. Không tìm thấy sản phẩm → nói tự nhiên: "Kho hiện chưa có mẫu nào khớp từ khóa của anh ạ, anh thử từ khóa khác nhé, hoặc để em tư vấn theo nhu cầu." (không chép y như trên nếu khác ngữ cảnh)
10. Hỏi giá → ghi đúng định dạng VNĐ (16.500.000đ).
11. Hỏi về sản phẩm → gọi tim_san_pham. Hỏi chi tiết 1 sản phẩm → gọi xem_chi_tiet_san_pham. Hỏi đơn hàng → yêu cầu mã đơn rồi gọi tra_cuu_don_hang. Yêu cầu thêm vào giỏ → gọi them_vao_gio_hang.
12. Câu ngoài phạm vi (thời tiết, tin tức, bài tập...) → nhẹ nhàng: "Em chỉ giúp được về sản phẩm và đơn hàng của WoodCon thôi anh/chị nhé."
13. TUYỆT ĐỐI không đọc như AI: không nói "Tôi là AI", không "theo dữ liệu của tôi", không lặp template mỗi lượt.

CÁC HÀM CÓ THỂ GỌI:
- tim_san_pham: tìm sản phẩm theo từ khóa
- xem_chi_tiet_san_pham: xem thông tin chi tiết một sản phẩm
- tra_cuu_don_hang: tra cứu trạng thái đơn hàng bằng mã đơn
- them_vao_gio_hang: thêm sản phẩm vào giỏ hàng
PROMPT;
}

/**
 * Ghi lỗi AI vào bảng ai_error_log.
 */
function _log_ai_error(string $model, string $message): void
{
    try {
        Database::connect()->prepare(
            'INSERT INTO ai_error_log (model, loi, thoi_gian) VALUES (?, ?, NOW())'
        )->execute([$model, mb_substr($message, 0, 2000, 'UTF-8')]);
    } catch (\Throwable) {
        // Không để lỗi logging phá vỡ luồng chính
    }
}