<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

// 🔐 Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php"); // hoặc trang đăng nhập bạn đang dùng
  exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Lấy danh sách ví của user hiện tại
$wallets = [];
$stmt = $conn->prepare("SELECT id, name FROM Wallets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$errors = [];
$name = '';
$amount = '';
$wallet_id = '';
$icon = $_POST['icon'] ?? '🏷️'; // default icon

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $amount = 0;

  $wallet_id = intval($_POST['wallet_id'] ?? 0);
  $limit_amount = floatval($_POST['limit_amount'] ?? 0);

  if ($name === '') {
    $errors[] = 'Tên tag không được để trống.';
  }

  if ($wallet_id <= 0) {
    $errors[] = 'Vui lòng chọn ví.';
  }


  if ($limit_amount <= 0) {
    $errors[] = 'Giới hạn số tiền phải lớn hơn 0.';
  }
  // Kiểm tra trùng tên tag
    $stmt = $conn->prepare("SELECT id FROM Tags WHERE name = ? AND user_id = ?");
    $stmt->bind_param("si", $name, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $errors[] = 'Tên tag đã tồn tại. Vui lòng chọn tên khác.';
    }
    $stmt->close();

  if (empty($errors)) {
    // ✅ Kiểm tra số dư thực tế
    $stmt = $conn->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wallet_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
      $errors[] = 'Ví không tồn tại hoặc không thuộc về người dùng.';
    } else {
      $current_balance = floatval($wallet['balance']);

      $stmt = $conn->prepare("
        SELECT SUM(Tags.limit_amount) AS total_tag_amount
        FROM Tags
        JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
        JOIN Transactions ON Transactions.id = Transaction_Tags.transaction_id
        WHERE Transactions.wallet_id = ? AND Tags.user_id = ?
      ");

      $stmt->bind_param("ii", $wallet_id, $user_id);
      $stmt->execute();
      $res = $stmt->get_result();
      $data = $res->fetch_assoc();
      $total_tag_amount = floatval($data['total_tag_amount'] ?? 0);
      $stmt->close();

      $new_total = $total_tag_amount + $limit_amount;


      if ($new_total > $current_balance) {
      $remaining = $current_balance - $total_tag_amount;
      $errors[] = "Ví không đủ để tạo giới hạn tag. Bạn chỉ còn lại " . number_format($remaining, 0, ',', '.') . "₫ để tạo tag mới.";
      }
    }
  }
    
  if (empty($errors)) {
    // 1. Thêm tag
    $stmt = $conn->prepare("INSERT INTO Tags (name, user_id,icon, created_at,edit_at,limit_amount) VALUES (?, ?,?, NOW(), NOW(),?)");
    $stmt->bind_param("sisd", $name, $user_id, $icon,  $limit_amount);
    $stmt->execute();
    $tag_id = $conn->insert_id;
    $stmt->close();

    // 2. Thêm giao dịch
    $stmt = $conn->prepare("INSERT INTO Transactions (user_id, wallet_id, amount, type, date, created_at) VALUES (?, ?, ?, 'expense', NOW(), NOW())");
    $stmt->bind_param("iid", $user_id, $wallet_id, $amount);
    $stmt->execute();
    $transaction_id = $conn->insert_id;
    $stmt->close();

    // 3. Gắn tag vào giao dịch
    $stmt = $conn->prepare("INSERT INTO Transaction_Tags (transaction_id, tag_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $transaction_id, $tag_id);
    $stmt->execute();
    $stmt->close();
  
  echo "<script>
      window.parent.closeAddTagModal();
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
  <title>Thêm Tag</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class=" font-sans min-h-screen flex items-center justify-center m-0 p-0">
  <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg">
    <h1 class="text-2xl font-bold mb-6 text-center tracking-wide text-gray-900 drop-shadow-sm">
      THÊM TAG MỚI
    </h1>


    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?php foreach ($errors as $error): ?>
          <div>- <?= htmlspecialchars($error) ?></div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <form method="POST">
      <div class="mb-4">
        <label for="name" class="block font-medium mb-1">Tên Tag</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>"
               class="w-full border border-gray-300 rounded px-3 py-2" required
               oninvalid="this.setCustomValidity('Vui lòng nhập tên tag.')"
               oninput="this.setCustomValidity('')">
      </div>

      <div class="mb-4">
          <label for="icon" class="block font-medium mb-1">Chọn Icon</label>
          <div class="grid grid-cols-6 gap-2">
            <?php
              $icons = ['🏷️', '💸', '🍔', '🎁', '🚗', '🎓', '🏡', '📱', '💻', '📚', '💳','🥦','🍎','🥤','⚡','💧'];
              $selected_icon = $_POST['icon'] ?? ''; // hoặc giá trị mặc định
              foreach ($icons as $opt_icon):
            ?>
              <label class="cursor-pointer">
                <input type="radio" name="icon" value="<?= $opt_icon ?>" class="hidden peer" <?= $selected_icon === $opt_icon ? 'checked' : '' ?>>
                <span class="inline-block text-2xl border rounded-md p-2 w-full text-center peer-checked:bg-blue-200 hover:bg-gray-100">
                  <?= $opt_icon ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

      <div class="mb-4">
        <label for="limit_amount" class="block font-medium mb-1">Giới hạn số tiền của tag</label>
        <input type="number" name="limit_amount" id="limit_amount" value="<?= htmlspecialchars($_POST['limit_amount'] ?? '') ?>"
              class="w-full border border-gray-300 rounded px-3 py-2" step="500" required
              oninvalid="this.setCustomValidity('Vui lòng nhập giới hạn số tiền.')"
              oninput="this.setCustomValidity('')">
      </div>


      <div class="mb-6">
        <label for="wallet_id" class="block font-medium mb-1">Chọn ví</label>
        <select name="wallet_id" id="wallet_id" class="w-full border border-gray-300 rounded px-3 py-2" required
                oninvalid="this.setCustomValidity('Vui lòng chọn ví.')"
                oninput="this.setCustomValidity('')">
          <option value="">-- Chọn ví --</option>
          <?php foreach ($wallets as $wallet): ?>
            <option value="<?= $wallet['id'] ?>" <?= $wallet_id == $wallet['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($wallet['name']) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="flex gap-4 justify-end">
        <button type="button" onclick="window.parent.closeAddTagModal()"
          class="px-4 py-2 rounded text-white font-semibold
                bg-gradient-to-r from-red-500 to-red-700
                hover:from-red-600 hover:to-red-800
                transition-colors duration-300">
          Huỷ
        </button>

        <button type="submit"
          class="px-4 py-2 rounded text-white font-semibold
                bg-gradient-to-r from-blue-500 to-blue-700
                hover:from-blue-600 hover:to-blue-800
                transition-colors duration-300">
          Lưu
        </button>
      </div>

    </form>
  </div>
</body>
</html>


<script>
  function checkAmount(input) {
    if (parseFloat(input.value) < 0) {
      input.setCustomValidity("Không được nhập số âm.");
    } else {
      input.setCustomValidity("");
    }
  }
</script>

<script>
  const walletSelect = document.getElementById("wallet_id");
  const amountInput = document.getElementById("amount");

  walletSelect.addEventListener("change", function () {
    const selectedOption = walletSelect.options[walletSelect.selectedIndex];
    const walletName = selectedOption.text.toLowerCase();
    const specialNames = ["visa", "ngân hàng"];

    if (specialNames.some(keyword => walletName.includes(keyword))) {
      amountInput.step = "1";
    } else {
      amountInput.step = "500";
    }
  });
</script>
