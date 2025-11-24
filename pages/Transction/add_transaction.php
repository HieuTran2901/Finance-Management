<?php
require_once __DIR__ . '/../../module/config.php';
session_start();
// 🔐 Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php"); // hoặc trang đăng nhập bạn đang dùng
  exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wallet_id = $_POST["wallet_id"];
    $category_name = trim($_POST["category"]);
    $amount = floatval($_POST["amount"]);
    $type = $_POST["type"];
    $note = $_POST["note"];
    $date = $_POST["date"];
    $emotion = intval($_POST["emotion_level"]);
    $created_at = date("Y-m-d H:i:s");
    $tags = array_filter(array_map("trim", explode(",", $_POST["tags"])));

    // Xử lý ảnh
    $receipt_url = null;
    if (isset($_FILES["photo_receipt"]) && $_FILES["photo_receipt"]["error"] == 0) {
        $filename = time() . '_' . basename($_FILES["photo_receipt"]["name"]);
        $target_path = "uploads/" . $filename;
        move_uploaded_file($_FILES["photo_receipt"]["tmp_name"], $target_path);
        $receipt_url = $conn->real_escape_string($target_path);
    }

    // Kiểm tra danh mục
    $sql = "SELECT id FROM Categories WHERE name = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $category_name, $user_id);
    $stmt->execute();
    $stmt->bind_result($category_id);
    if (!$stmt->fetch()) {
        $stmt->close();
        $icon = "❓";
        $stmt = $conn->prepare("INSERT INTO Categories (name, icon, type, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $category_name, $icon, $type, $user_id);
        $stmt->execute();
        $category_id = $stmt->insert_id;
    } else {
        $stmt->close();
    }

    // Cập nhật số dư
    $amount_signed = $type === "expense" ? -$amount : $amount;
    $stmt = $conn->prepare("UPDATE Wallets SET balance = balance + ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dii", $amount_signed, $wallet_id, $user_id);
    $stmt->execute();

    // Thêm giao dịch
    $stmt = $conn->prepare("INSERT INTO Transactions 
        (user_id, wallet_id, category_id, amount, type, date, note, photo_receipt_url, emotion_level, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidssssss", $user_id, $wallet_id, $category_id, $amount, $type, $date, $note, $receipt_url, $emotion, $created_at);
    $stmt->execute();
    $transaction_id = $stmt->insert_id;

    // Xử lý tags
    // Lấy tổng chi tiêu theo từng tag (expense) trong cùng ví
            foreach ($tags as $tag_name) {
                // Lấy ID của tag
                $stmt = $conn->prepare("SELECT id, limit_amount FROM Tags WHERE name = ? AND user_id = ?");
                $stmt->bind_param("si", $tag_name, $user_id);
                $stmt->execute();
                $stmt->bind_result($tag_id, $tag_limit);
                if (!$stmt->fetch()) {
                    $stmt->close();
                    continue; // hoặc báo lỗi tag không tồn tại
                }
                $stmt->close();

                // Tổng đã dùng cho tag đó
                $stmt = $conn->prepare("
                    SELECT COALESCE(SUM(t.amount), 0) 
                    FROM Transactions t
                    INNER JOIN Transaction_Tags tt ON t.id = tt.transaction_id
                    WHERE tt.tag_id = ? AND t.wallet_id = ? AND t.type = 'expense'
                ");
                $stmt->bind_param("ii", $tag_id, $wallet_id);
                $stmt->execute();
                $stmt->bind_result($used_amount);
                $stmt->fetch();
                $stmt->close();

                if ($type === "expense" && ($used_amount + $amount) > $tag_limit) {
                    die("Giao dịch vượt quá giới hạn của tag '{$tag_name}'. Đã dùng: " . number_format($used_amount) . "₫, giới hạn: " . number_format($tag_limit) . "₫.");
                }
            }


    header('Location: Transaction.php');
    exit;
}

// Lấy danh sách ví
$wallets_result = $conn->query("SELECT id, name FROM Wallets WHERE user_id = $user_id");

// Lấy danh sách tag gợi ý
$tags_result = $conn->query("SELECT name FROM Tags WHERE user_id = $user_id");
$tags_suggest = [];
while ($row = $tags_result->fetch_assoc()) {
    $tags_suggest[] = $row['name'];
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm Giao Dịch</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
  <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow w-full max-w-2xl space-y-4">
    <h2 class="text-2xl font-bold text-center mb-4">Thêm Giao Dịch</h2>

    <div>
      <label class="block font-medium mb-1">Tên danh mục</label>
      <input name="category" required class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Chọn ví</label>
      <select name="wallet_id" required class="w-full border p-2 rounded">
        <?php while ($wallet = $wallets_result->fetch_assoc()): ?>
          <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="flex gap-4">
      <div class="flex-1">
        <label class="block font-medium mb-1">Loại giao dịch</label>
        <select name="type" class="w-full border p-2 rounded">
          <option value="expense">Chi</option>
          <option value="income">Thu</option>
        </select>
      </div>
      <div class="flex-1">
        <label class="block font-medium mb-1">Số tiền</label>
        <input type="number" name="amount" step="0.01" required class="w-full border p-2 rounded">
      </div>
    </div>

    <div>
      <label class="block font-medium mb-1">Ngày giao dịch</label>
      <input type="datetime-local" name="date" required class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Ghi chú</label>
      <input type="text" name="note" class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Ảnh hóa đơn</label>
      <input type="file" name="photo_receipt" class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Cảm xúc</label>
      <input type="range" min="1" max="5" name="emotion_level" class="w-full">
    </div>

    <div>
      <label class="block font-medium mb-1">Tags (ngăn cách bằng dấu phẩy)</label>
      <input type="text" name="tags" class="w-full border p-2 rounded" list="tags-list">
      <datalist id="tags-list">
        <?php foreach ($tags_suggest as $tag): ?>
          <option value="<?= htmlspecialchars($tag) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="flex justify-between">
        <a href="Transaction.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Huỷ</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Lưu giao dịch</button>
    </div>
  </form>
</body>
</html>
