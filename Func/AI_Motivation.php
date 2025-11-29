<?php

require_once '../Api/Apiconfig.php';
// require_once dirname(__DIR__, 1) . '/Api/Apiconfig.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!$apiKey) {
    echo json_encode(['message' => '⚠️ Thiếu API Key.']);
    exit;
}

// ✅ Chỉ nhận POST JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $goal_name = $data['goal_name'] ?? 'mục tiêu tài chính';
    $percentage = (int)($data['percentage'] ?? 0);
    $days_left = (int)($data['days_left'] ?? 0);

    // Nếu quá hạn mà chưa đạt mục tiêu, gửi prompt an ủi
    if ($days_left <= 0 && $percentage < 100) {
        $prompt = "Bạn là một huấn luyện viên tài chính thân thiện. "
                . "Người dùng chưa đạt được mục tiêu '$goal_name' và đã quá hạn. "
                . "Hãy tạo một câu an ủi ngắn gọn, khích lệ tinh thần, tích cực, nhẹ nhàng, bằng tiếng Việt."
                . "Vui lòng kèm ít nhất 1 emoji cảm xúc hoặc icon để tăng tính sinh động."
                . "Nội dung giới hạn 75 từ.";
    } else {
        $prompt = "Bạn là một huấn luyện viên tài chính thân thiện. "
                . "Người dùng đang có mục tiêu '$goal_name'. "
                . "Tiến độ hiện tại là $percentage% và còn $days_left ngày. "
                . "Hãy tạo một câu động viên ngắn gọn, tích cực, vui vẻ bằng tiếng Việt."
                . "Vui lòng kèm ít nhất 1 emoji cảm xúc hoặc icon để tăng tính sinh động."
                . "Nội dung giới hạn 75 từ.";
    }

    $payload = [
        "model" => $modal_AI,
        "messages" => [["role" => "user", "content" => $prompt]],
        "max_tokens" => 70,
        "temperature" => 0.8
    ];

    $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $message = $result['choices'][0]['message']['content'] ?? "💪 Hãy tiếp tục cố gắng nhé!";

    echo json_encode(['message' => $message]);
    exit;
}

echo json_encode(['message' => '⚠️ Yêu cầu không hợp lệ.']);
