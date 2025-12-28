<?php
session_start();
require_once '../Api/Apiconfig.php';

// 🔒 Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Bạn chưa đăng nhập.";
    exit;
}

if (!isset($_POST['message'])) {
    http_response_code(400);
    echo "Không có dữ liệu.";
    exit;
}

$userId = $_SESSION['user_id'];
$userInput = trim($_POST['message']);

// Gửi yêu cầu đến AI
$data = [
    "model" => $modal_AI,
    "temperature" => 0.7,
    "max_tokens" => 3000,
    "messages" => [
        [
            "role" => "system",
            "content" => "
                Bạn là một trợ lý tài chính cá nhân thông minh và thân thiện, có khả năng:

                1. **Khi người dùng yêu cầu truy vấn dữ liệu:
                    - CHỈ TRẢ VỀ duy nhất một câu lệnh SQL `SELECT`, không bao gồm bất kỳ văn bản, markdown, mô tả nào khác.
                    - KHÔNG được thêm dấu ```sql hoặc giải thích.
                    - KHÔNG thêm tiêu đề như “Final Result:” hoặc bất kỳ đoạn mô tả nào.
                    - Kết quả đầu ra PHẢI là dòng SQL DUY NHẤT bắt đầu bằng SELECT và kết thúc bằng dấu chấm phẩy (;).

                2. **Tương tác văn bản:** Nếu không phải giao dịch thì trả lời như trợ lý thân thiện.

                3. **Không hiểu:** Phản hồi thân thiện nếu không rõ ý định người dùng.

                4. **Sinh SQL:** Nếu người dùng yêu cầu thống kê tài chính, trả về **duy nhất câu lệnh SQL SELECT**, không kèm văn bản, markdown, mô tả.

                    - Giả sử có bảng `transactions` gồm: amount, currency, category, date, description
                    - Trả về đúng cú pháp ANSI SQL (cho MySQL)
                    - VD: 'Tổng chi ăn uống tháng 5' => SELECT SUM(t.amount) FROM transactions t JOIN transaction_tags tt ON tt.transaction_id = t.id JOIN tags tg ON tg.id = tt.tag_id WHERE tg.name = 'Ăn uống' AND MONTH(t.date) = 6 AND t.type = 'expense' AND t.user_id = {{user_id}};;
                              ** Nếu người dùng yêu cầu so sánh thu nhập và chi tiêu giữa hai tháng, hãy trả về truy vấn SELECT với tổng `income` và `expense` cho từng tháng, dùng `CASE WHEN` và `GROUP BY MONTH(date)`.
                    - VD: 'So sánh chi tiêu và thu nhập giữa tháng 4 và tháng 5 giúp tôi' => SELECT MONTH(date) AS month, SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense FROM transactions WHERE user_id = {{user_id}} AND MONTH(date) IN (4, 5) GROUP BY MONTH(date);
                              ** Nếu người dùng yêu cầu thống kê các thẻ (tag) sắp vượt quá giới hạn chi tiêu thì quá 80% được xem là sắp vượt quá giới hạn, 60-79% nằm trong ngưỡng trong giới hạn, dưới 60% thì có thể chi tiêu thoải mái
                    - VD: 'Các thẻ nào sắp vượt quá giới hạn chi tiêu trong tháng 6 năm 2025' => SELECT t.id AS tag_id, t.name AS tag_name, t.limit_amount, SUM(tr.amount) AS total_spent, ROUND(SUM(tr.amount) / t.limit_amount *100, 2) AS percent_used FROM tags t JOIN transaction_tags tt ON t.id = tt.tag_id JOIN transactions tr ON tr.id =tt.transaction_id WHERE tr.type = 'expense' AND t.limit_amount > 0 AND t.user_id = {{user_id}} AND MONTH(tr.date) = 6 AND YEAR(tr.date) =2025 GROUP BY t.id, t.name, t.limit_amount HAVING total_spent >= 0.8 * t.limit_amount;
                              ** Nếu người dùng muốn biết thẻ (tag) cụ thể đã chi tiêu ở tháng này thì hãy trả về truy vấn như sau:
                    - VD: 'Tôi muốn biết thẻ(tag) Hóa đơn của tôi đã chi tiêu những gì' => SELECT tg.name AS tag_name, ct.name AS transaction_name, tr.amount, tr.date FROM transactions tr JOIN categories ct ON tr.category_id = ct.id JOIN transaction_tags tt ON tr.id = tt.transaction_id JOIN tags tg ON tg.id = tt.tag_id WHERE tr.type = 'expense' AND MONTH(tr.date) = MONTH(CURRENT_DATE()) AND YEAR(tr.date) = YEAR(CURRENT_DATE()) AND tr.user_id = 5 AND tg.name = 'hóa đơn' ORDER BY tr.date;
                    
                             **Nếu người dùng nói đã chi tiền hoặc nhận tiền → Trả về JSON như sau:**
                    -VD: 'Tôi đã chi 100000 cho cà phê cho thẻ ăn uống vào Tiền mặt'
                         {
                        \"type\": \"expense\", 
                        \"category\": \"mua ly cà phê\", 
                        \"amount\": 100000, 
                        \"date\": \"2025-07-18\", // nếu người dùng không nói rõ ngày, mặc định dùng ngày hôm nay
                        \"tag\": \"Ăn uống\",
                        \"wallet\": \"Tiền mặt\" // 
                        }
                        'Tôi đã thu được 1000000 tiền lương hãy thêm vào ngân hàng'
                         {
                        \"type\": \"income\", 
                        \"category\": \"Tiền lương\", 
                        \"amount\": 1000000, 
                        \"date\": \"2025-07-18\", // nếu người dùng không nói rõ ngày, mặc định dùng ngày hôm nay
                        \"wallet\": \"Ngân hàng\"
                        }
                    - Nếu người dùng không nói rõ ngày thực hiện, hãy tự động sử dụng ngày hiện tại (today) và ghi trường 'date' đầy đủ trong phản hồi JSON, luôn ghi đầy đủ trường 'date' dưới dạng 'YYYY-MM-DD' trong JSON phản hồi.   
                        
                            **Nếu người dùng yêu cầu liệt kê các thẻ có trong các ví thì hãy trả về như sau:
                    -VD: 'Tôi muốn xem các thẻ có trong các ví' => SELECT w.name AS wallet_name, tg.name AS tag_name FROM wallets w JOIN transactions t ON t.wallet_id = w.id JOIN transaction_tags tt ON tt.transaction_id = t.id JOIN tags tg ON tg.id = tt.tag_id WHERE w.user_id = {{user_id}} GROUP BY w.id, tg.id ORDER BY w.name, tg.name;
                
                5. KHÔNG được trả về DELETE, UPDATE, INSERT, không được trả về json trên giao diện chat của người dùng
                 "
        ],
        ["role" => "user", "content" => $userInput],
    ]
];

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json",
        "HTTP-Referer: $referer"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Lỗi kết nối AI: $error";
    exit;
}

$json = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON không hợp lệ: " . json_last_error_msg();
    exit;
}
$aiContent = $json['choices'][0]['message']['content'] ?? "Không hiểu yêu cầu.";



// 🧾 1. Nếu AI trả về JSON để thêm giao dịch
$parsed = json_decode($aiContent, true);
if (is_array($parsed) && isset($parsed['amount']) && isset($parsed['category'])) {
    $type = $parsed['type'];
    $categoryName = trim($parsed['category']);
    $amount = floatval($parsed['amount']);
    $date = $parsed['date'] ?? date('Y-m-d');
    $tagName = $parsed['tag'] ?? null;
    $walletName = $parsed['wallet'] ?? null;

    // Chỉ cho phép expense hoặc income
    if (!in_array($type, ['expense', 'income'])) {
        echo "❌ Loại giao dịch không hợp lệ.";
        exit;
    }

    // 🔍 Tìm hoặc tạo danh mục
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ?");
    $stmt->execute([$categoryName, $userId]);
    $categoryId = $stmt->fetchColumn();

    if (!$categoryId) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
        $stmt->execute([$categoryName, $userId]);
        $categoryId = $pdo->lastInsertId();
    }

    // 🔍 Tìm ví nếu có
    $walletId = null;
    if ($walletName) {
        $stmt = $pdo->prepare("SELECT id FROM wallets WHERE name = ? AND user_id = ?");
        $stmt->execute([$walletName, $userId]);
        $walletId = $stmt->fetchColumn();

        if (!$walletId) {
            echo "❌ Không tìm thấy ví: $walletName.";
            exit;
        }
    }

    // ➕ Thêm giao dịch
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, type, date, wallet_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $categoryId, $amount, $type, $date, $walletId]);
    $transactionId = $pdo->lastInsertId();

    // 🔗 Nếu là expense thì mới thêm tag
if ($type === 'expense' && $tagName) {
    // Tìm hoặc tạo tag
    $stmt = $pdo->prepare("SELECT id, limit_amount FROM tags WHERE name = ? AND user_id = ?");
    $stmt->execute([$tagName, $userId]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        $stmt = $pdo->prepare("INSERT INTO tags (name, user_id) VALUES (?, ?)");
        $stmt->execute([$tagName, $userId]);
        $tagId = $pdo->lastInsertId();
        $limitAmount = 0; // Chưa đặt giới hạn
    } else {
        $tagId = $tag['id'];
        $limitAmount = floatval($tag['limit_amount']);
    }

    // Gắn tag với giao dịch
    $stmt = $pdo->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, ?)");
    $stmt->execute([$transactionId, $tagId]);

    // 💡 Kiểm tra nếu vượt quá giới hạn
    if ($limitAmount > 0) {
        // Tổng chi hiện tại của tag đó
        $stmt = $pdo->prepare("
            SELECT SUM(t.amount) 
            FROM transactions t
            JOIN transaction_tags tt ON t.id = tt.transaction_id
            WHERE tt.tag_id = ? AND t.user_id = ? AND t.type = 'expense'
        ");
        $stmt->execute([$tagId, $userId]);
        $totalSpent = floatval($stmt->fetchColumn());

        if ($totalSpent > $limitAmount) {
            echo "⚠️ Giao dịch đã vượt giới hạn chi tiêu cho thẻ *$tagName*. Tổng chi hiện tại: " . number_format($totalSpent, 0, ',', '.') . " / " . number_format($limitAmount, 0, ',', '.') . " VND.\n";
        } elseif ($totalSpent >= 0.7 * $limitAmount) {
            echo "🔔 Cảnh báo: Bạn sắp vượt giới hạn cho thẻ *$tagName*. Tổng chi hiện tại: " . number_format($totalSpent, 0, ',', '.') . " / " . number_format($limitAmount, 0, ',', '.') . " VND.\n";
        }
    }
}

    echo "✅ Đã lưu giao dịch trong mục *$categoryName* ngày $date.";
    exit;
}


// Nếu là SQL truy vấn
if (preg_match('/^SELECT\s/i', trim($aiContent))) {
    $sql = str_replace('{{user_id}}', $userId, $aiContent);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_NUM);

// Nếu không có dữ liệu
if (!$results || count($results) === 0) {
    echo "🔍 Không tìm thấy kết quả.";
    exit;
}

// Nếu chỉ có 1 dòng và 1 cột → kết quả đơn
if (count($results) === 1 && count($results[0]) === 1) {
    $sqlResult = $results[0][0];

    $data['messages'][] = ["role" => "assistant", "content" => $aiContent];
    $data['messages'][] = ["role" => "user", "content" => "Tôi vừa truy vấn SQL: `$aiContent`. Kết quả là: $sqlResult. Hãy phản hồi kết quả cho người dùng một cách tự nhiên và thân thiện."];
} else {
    // Trả về bảng kết quả
    $sqlResult = $results;

    // Chuyển mảng kết quả thành bảng văn bản để gửi lại AI
    $resultText = "Kết quả truy vấn:\n";
    foreach ($results as $row) {
        $resultText .= implode(" | ", $row) . "\n";
    }

    $data['messages'][] = ["role" => "assistant", "content" => $aiContent];
    $data['messages'][] = ["role" => "user", "content" => "Tôi vừa truy vấn SQL: `$aiContent`. Kết quả như sau:\n$resultText\nHãy tóm tắt và phản hồi kết quả một cách tự nhiên và thân thiện cho người dùng."];
}

        $ch2 = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json",
                "HTTP-Referer: $referer"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);
        $response2 = curl_exec($ch2);
        curl_close($ch2);
        $json2 = json_decode($response2, true);
        echo $json2['choices'][0]['message']['content'] ?? "✅ Kết quả: " . number_format($sqlResult, 0, ',', '.') . " VNĐ";
    } catch (PDOException $e) {
        echo "❌ Lỗi truy vấn: " . $e->getMessage();
    }
    exit;
}

// Nếu không phải SQL hoặc JSON
echo $aiContent;
