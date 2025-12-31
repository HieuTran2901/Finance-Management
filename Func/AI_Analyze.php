<?php
require_once '../Api/Apiconfig.php';
require_once './SQL_Cmd.php';
require_once './Get_Session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$sessionData = Get_Session('../module/config.php', '../dangkydangnhap/login.php');
$users_id = $sessionData['user_id'];
$conn = $sessionData['conn'];

if (!$apiKey) {
    echo json_encode(['message' => '⚠️ Thiếu API Key.']);
    exit;
}

// ==========================
// LẤY DỮ LIỆU TỪ DATABASE
// ==========================

// Lấy tất cả ví
$wallets = SQL_Select($conn, "SELECT * FROM Wallets WHERE user_id = ?", "i", [$users_id]);

// Lấy tất cả mục tiêu
$goals = SQL_Select($conn, "SELECT * FROM goals WHERE user_id = ?", "i", [$users_id]);

if (empty($wallets) || empty($goals)) {
    echo json_encode(['message' => '📭 Bạn chưa có ví hoặc mục tiêu nào để AI phân tích.']);
    exit;
}

// ==========================
// TÍNH SỐ DƯ THỰC TẾ CỦA TỪNG VÍ
// ==========================
$wallets_str = "";
$sql_balance = "
    SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
    FROM Transactions 
    WHERE wallet_id = ? AND user_id = ?
";

foreach ($wallets as $wallet) {
    $wallet_id = $wallet['id'];
    $result = SQL_Select($conn, $sql_balance, "ii", [$wallet_id, $users_id]);

    $income = floatval($result[0]['total_income'] ?? 0);
    $expense = floatval($result[0]['total_expense'] ?? 0);
    $real_balance = floatval($wallet['balance']) + $income - $expense;

    $wallets_str .= "{$wallet['name']} (số dư hiện tại: " . number_format($real_balance, 0, ',', '.') . " {$wallet['currency']}), ";
}

// ==========================
// CHUẨN BỊ CHUỖI MỤC TIÊU
// ==========================
$goals_str = "";
foreach ($goals as $goal) {
    $percent = ($goal['target_amount'] > 0)
        ? round(($goal['saved_amount'] / $goal['target_amount']) * 100, 2)
        : 0;
    $goals_str .= "{$goal['goal_name']} (đã đạt {$percent}%, tiết kiệm {$goal['saved_amount']}/{$goal['target_amount']} {$wallets[0]['currency']}, hạn: {$goal['end_date']}), ";
}

// ==========================
// PROMPT CHO AI
// ==========================
$prompt = "Bạn là một huấn luyện viên tài chính thông minh. 
Người dùng có các ví: $wallets_str 
và các mục tiêu tài chính: $goals_str
Hãy đưa ra kế hoạch chi tiêu và phân bổ hợp lý để đạt được các mục tiêu, viết chi tiết, dễ hiểu, tích cực và kèm emoji.";

// ==========================
// GỌI API OPENROUTER
// ==========================
$payload = [
    "model" => $modal_AI_FREE,
    "messages" => [["role" => "user", "content" => $prompt]],
    "max_tokens" => 1000,
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
if ($response === false) {
    echo json_encode(["error" => "Không thể kết nối tới AI: " . curl_error($ch)]);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);
$message = $result['choices'][0]['message']['content'] ?? "💪 Hãy tiếp tục quản lý tài chính của bạn thật tốt!";

echo json_encode(['message' => $message]);
exit;
