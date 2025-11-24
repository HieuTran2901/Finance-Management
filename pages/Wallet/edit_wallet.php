<?php
// Kết nối database
require_once __DIR__ . '/../../module/config.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  die('ID ví không hợp lệ.');
}

// Lấy thông tin ví hiện tại
$sql = "SELECT * FROM wallets WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$wallet = $result->fetch_assoc();

if (!$wallet) {
  die('Ví không tồn tại.');
}

// Gán giá trị mặc định cho form
$name = $_POST['name'] ?? $wallet['name'];
$type = $_POST['type'] ?? $wallet['type'];
$balance = $_POST['balance'] ?? $wallet['balance'];
$currency = $_POST['currency'] ?? $wallet['currency'];


// Xử lý form khi submit
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate dữ liệu nếu cần

  if (empty($errors)) {
    $sql = "UPDATE Wallets SET name = ?, type = ?, balance = ?, currency = ?, edit_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsi", $name, $type, $balance, $currency, $wallet_id);
    $stmt->execute();

    header("Location: Wallet.php");
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
</head>
<body class="bg-gray-100 font-sans min-h-screen flex items-center justify-center p-4">
   <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg">
    <h1 class="text-xl font-semibold mb-6 text-center">CHỈNH SỬA VÍ</h1>

    <form method="POST">
      <div class="mb-4">
        <label for="name" class="block font-medium mb-1">Tên ví:</label>
        <input type="text" name="name" id="name" required
               class="w-full border border-gray-300 rounded px-3 py-2"
               value="<?= htmlspecialchars($name) ?>"
               oninvalid="this.setCustomValidity('Vui lòng nhập tên ví.')"
               oninput="this.setCustomValidity('')">
      </div>

      <div class="mb-4">
        <label for="type" class="block font-medium mb-1">Loại ví:</label>
        <input type="text" name="type" id="type" required
               class="w-full border border-gray-300 rounded px-3 py-2"
               value="<?= htmlspecialchars($type) ?>"
               oninvalid="this.setCustomValidity('Vui lòng nhập loại ví.')"
               oninput="this.setCustomValidity('')">
      </div>

      <div class="mb-4">
        <label for="balance" class="block font-medium mb-1">Số dư:</label>
        <input type="number" name="balance" id="balance"required
               class="w-full border border-gray-300 rounded px-3 py-2"
               value="<?= (intval($balance) == $balance) ? intval($balance) : htmlspecialchars($balance) ?>"
               oninvalid="this.setCustomValidity('Vui lòng nhập số dư hợp lệ.')"
               oninput="checkBalance(this)">
      </div>

      <div class="mb-6">
        <label for="currency" class="block font-medium mb-1">Tiền tệ:</label>
        <input type="text" name="currency" id="currency" required
               class="w-full border border-gray-300 rounded px-3 py-2"
               value="<?= htmlspecialchars($currency) ?>"
               oninvalid="this.setCustomValidity('Vui lòng nhập tiền tệ.')"
               oninput="this.setCustomValidity('')">
      </div>

      <div class="flex justify-between">
        <a href="Wallet.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Huỷ</a>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Cập nhật</button>
      </div>
    </form>
    <?php if (!empty($errors)): ?>
      <script>
        alert("<?= implode("\\n", array_map('addslashes', $errors)) ?>");
      </script>
    <?php endif; ?>
  </div>
</body>
</html>
<script>
  function checkBalance(input) {
    if (parseFloat(input.value) < 0) {
      input.setCustomValidity("Không được nhập số âm.");
    } else {
      input.setCustomValidity("");
    }
  }
</script>
