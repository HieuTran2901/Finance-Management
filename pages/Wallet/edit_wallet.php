<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

// 🔐 Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập trước.");
}

$user_id = $_SESSION['user_id'];
$wallet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$errors = [];
$name = '';
$type = '';
$balance = '';
$currency = '';

// 🔹 Lấy thông tin ví
$stmt = $conn->prepare("SELECT * FROM Wallets WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $wallet_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallet = $result->fetch_assoc();
$stmt->close();

if (!$wallet) die("Ví không tồn tại hoặc không thuộc về bạn.");

$name = $_POST['name'] ?? $wallet['name'];
$type = $_POST['type'] ?? $wallet['type'];
$balance = $_POST['balance'] ?? $wallet['balance'];
$currency = strtoupper(trim($wallet['currency']));

// 🔹 Xử lý form khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $currency = trim($_POST['currency'] ?? '');

    if ($name === '') $errors[] = "Tên ví không được để trống.";
    if ($type === '') $errors[] = "Loại ví không được để trống.";
    if ($balance < 0) $errors[] = "Số dư không được âm.";
    if ($currency === '') $errors[] = "Tiền tệ không được để trống.";

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE Wallets 
            SET name = ?, type = ?, balance = ?, currency = ?, edit_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssdiii", $name, $type, $balance, $currency, $wallet_id, $user_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>
            window.parent.closeEditWalletModal?.();
            window.parent.location.reload();
        </script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa ví</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../css/khung.css">
</head>

<body class="font-sans min-h-screen flex items-center justify-center">

<div class="image-form-container relative w-full max-w-lg mx-auto rounded-lg shadow-lg overflow-hidden">

    <!-- ẢNH NỀN -->
    <img src="../../css/img/khung.png" class="w-full h-64 object-cover">

    <!-- FORM -->
    <form method="POST" class="absolute inset-0 flex flex-col justify-center items-center px-6 py-4">

      <h1 class="text-2xl font-bold mb-4">CHỈNH SỬA VÍ</h1>

      <!-- HIỂN THỊ LỖI -->
      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded w-full mb-4">
          <?php foreach ($errors as $error): ?>
            <div>- <?= htmlspecialchars($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- TÊN VÍ -->
      <div class="w-full mb-3">
        <label class="font-medium text-gray-700">Tên ví</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($name) ?>"
               class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition"
               required>
      </div>

      <!-- LOẠI VÍ -->
      <div class="w-full mb-3">
        <label class="font-medium text-gray-700">Loại ví</label>
        <select name="type"
                class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition">
          <option value="">Chọn loại ví</option>
          <option value="Cá nhân" <?= $type === 'Cá nhân' ? 'selected' : '' ?>>Cá nhân</option>
          <option value="Doanh nghiệp" <?= $type === 'Doanh nghiệp' ? 'selected' : '' ?>>Doanh nghiệp</option>
        </select>
      </div>

      <!-- ĐƠN VỊ TIỀN TỆ -->
      <div class="w-full mb-3">
        <label class="font-medium text-gray-700">Đơn vị tiền tệ</label>
        <select name="currency"
                class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition">
          <option value="">Chọn đơn vị tiền tệ</option>
          <option value="VND" <?= $currency === 'VND' ? 'selected' : '' ?>>VND</option>
          <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD</option>
        </select>
      </div>

      <!-- SỐ DƯ -->
      <div class="w-full mb-3">
        <label class="font-medium text-gray-700">Số dư</label>
        <input type="number" name="balance"
               value="<?= htmlspecialchars($balance) ?>"
               class="w-full border rounded px-3 py-2 bg-blue-100 focus:bg-white focus:border-blue-500 transition"
               step="1"
               oninput="checkBalance(this)">
      </div>

      <!-- BUTTONS -->
      <div class="flex gap-4 justify-end w-full mt-2">

        <button type="button"
            onclick="window.parent.closeEditWalletModal()"
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

<script>
function checkBalance(input) {
  const value = input.value;
  if (value.includes('-') || parseFloat(value) < 0) {
      input.setCustomValidity("Không được nhập số âm hoặc dấu '-'");
  } else {
      input.setCustomValidity("");
  }
}
</script>

</body>
</html>
