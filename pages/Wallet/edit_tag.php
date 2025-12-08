<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

// 🔐 Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$tag_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Biến chứa dữ liệu
$name = '';
$amount = '';
$wallet_id = '';
$icon = '';
$transaction_id = 0;
$errors = [];

// 🧾 Lấy danh sách ví của user
$wallets = [];
$stmt = $conn->prepare("SELECT id, name FROM Wallets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wallets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 🏷️ Lấy thông tin Tag + giao dịch liên kết
if ($tag_id > 0) {

  $stmt = $conn->prepare("
    SELECT T.name, T.icon, TR.amount, TR.wallet_id, TR.id AS transaction_id
    FROM Tags T
    JOIN Transaction_Tags TT ON TT.tag_id = T.id
    JOIN Transactions TR ON TR.id = TT.transaction_id
    WHERE T.id = ? AND T.user_id = ?
  ");

  $stmt->bind_param("ii", $tag_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    $name = $row['name'];
    $icon = $row['icon'];
    $amount = $row['amount'];
    $wallet_id = $row['wallet_id'];
    $transaction_id = $row['transaction_id'];

  } else {
    $errors[] = "Không tìm thấy tag.";
  }

  $stmt->close();
} else {
  $errors[] = "Thiếu ID tag.";
}

// 📝 Khi người dùng bấm nút cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name = trim($_POST['name'] ?? '');
  $amount = floatval($_POST['amount'] ?? 0);
  $wallet_id = intval($_POST['wallet_id'] ?? 0);
  $icon = $_POST['icon'] ?? '';

  // Validate
  if ($name === '') $errors[] = "Tên tag không được để trống.";
  if ($icon === '') $errors[] = "Vui lòng chọn icon.";
  if ($wallet_id <= 0) $errors[] = "Vui lòng chọn ví.";
  if ($amount <= 0) $errors[] = "Số tiền phải lớn hơn 0.";

  if (empty($errors)) {

    // 🟦 Cập nhật TAG
    $stmt = $conn->prepare("
      UPDATE Tags 
      SET name = ?, icon = ?, edit_at = NOW() 
      WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ssii", $name, $icon, $tag_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 🟪 Cập nhật TRANSACTION
    $stmt = $conn->prepare("
      UPDATE Transactions 
      SET amount = ?, wallet_id = ?, edit_at = NOW() 
      WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("diii", $amount, $wallet_id, $transaction_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 🔄 Reload & đóng modal
    echo "
      <script>
        window.parent.closeEditTagModal();
        window.parent.location.reload();
      </script>
    ";
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chỉnh sửa Tag</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../css/khung.css">
</head>

<body class="font-sans min-h-screen flex items-center justify-center">

<div class="image-form-container relative w-full max-w-lg mx-auto rounded-lg shadow-lg overflow-hidden">

  <!-- ẢNH NỀN -->
  <img src="../../css/img/khung.png" class="w-full h-64 object-cover">

  <!-- FORM EDIT -->
  <form method="POST" class="absolute inset-0 flex flex-col justify-center items-center px-6 py-4">

    <h1 class="text-2xl font-bold mb-4">CHỈNH SỬA TAG</h1>

    <!-- TÊN TAG -->
    <div class="w-full mb-3">
      <label class="font-medium text-gray-700">Tên Tag</label>
      <input type="text" name="name" value="<?= htmlspecialchars($name) ?>"
        class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition"
        required>
    </div>

    <!-- ICON -->
    <div class="w-full mb-3">
      <label class="font-medium text-gray-700">Chọn Icon</label>

      <div class="grid grid-cols-6 gap-2 mt-2">
        <?php
        $icons = ['🏷️','💸','🍔','🎁','🚗','🎓','🏡','📱','💻','📚','💳','🥦','🍎','🥤','⚡','💧'];

        foreach ($icons as $opt_icon): ?>
          <label class="cursor-pointer">
            <input type="radio" name="icon" value="<?= $opt_icon ?>"
              class="hidden peer" <?= ($icon === $opt_icon ? 'checked' : '') ?>>

            <span class="inline-block text-2xl border rounded-md p-2 w-full text-center
                         peer-checked:bg-white peer-checked:border-blue-500
                         hover:bg-gray-100 transition">
              <?= $opt_icon ?>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- SỐ TIỀN -->
    <div class="w-full mb-3">
      <label class="font-medium text-gray-700">Tổng tiền giao dịch</label>
      <input type="number" name="amount" value="<?= htmlspecialchars($amount) ?>"
        class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition"
        step="500" required>
    </div>

    <!-- CHỌN VÍ -->
    <div class="w-full mb-3">
      <label class="font-medium text-gray-700">Chọn ví</label>
      <select name="wallet_id"
        class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition"
        required>
        <option value="">-- Chọn ví --</option>

        <?php foreach ($wallets as $wallet): ?>
          <option value="<?= $wallet['id'] ?>"
            <?= ($wallet_id == $wallet['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($wallet['name']) ?>
          </option>
        <?php endforeach; ?>

      </select>
    </div>

    <!-- HIỂN THỊ LỖI -->
    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded w-full mb-4">
        <?php foreach ($errors as $error): ?>
          <div>- <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif ?>

    <!-- NÚT -->
    <div class="flex gap-4 justify-end w-full mt-2">

      <button type="button" onclick="window.parent.closeEditTagModal()"
        class="px-4 py-2 rounded text-white bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800">
        Huỷ
      </button>

      <button type="submit"
        class="px-4 py-2 rounded text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800">
        Cập nhật
      </button>

    </div>

  </form>
</div>

</body>
</html>
