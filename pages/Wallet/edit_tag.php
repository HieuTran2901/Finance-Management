<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

//  Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id']; //  Lấy user_id từ session

$tag_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$name = '';
$amount = '';
$wallet_id = '';
$errors = [];
$icon = '';

//  Lấy danh sách ví của user đang đăng nhập
$wallets = [];
$stmt = $conn->prepare("SELECT id, name FROM Wallets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wallets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

//  Lấy thông tin tag và transaction liên quan
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
    $errors[] = "Không tìm thấy tag hoặc giao dịch liên quan.";
  }
  $stmt->close();
} else {
  $errors[] = "Thiếu ID tag để chỉnh sửa.";
}

//  Xử lý khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $icon = $_POST['icon'] ?? '';
  $amount = floatval($_POST['amount'] ?? 0);
  $wallet_id = intval($_POST['wallet_id'] ?? 0);

  if ($name === '') {
    $errors[] = "Tên tag không được để trống.";
  }

  if ($wallet_id <= 0) {
    $errors[] = "Vui lòng chọn ví hợp lệ.";
  }

  if ($amount <= 0) {
    $errors[] = "Số tiền phải lớn hơn 0.";
  }

  if (empty($errors)) {
    //  Kiểm tra ví thuộc user
    $stmt = $conn->prepare("SELECT balance FROM Wallets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wallet_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $wallet = $res->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
      $errors[] = 'Ví không tồn tại hoặc không thuộc về bạn.';
    } else {
      $balance = floatval($wallet['balance']);

      //  Tổng chi trừ giao dịch hiện tại
      $stmt = $conn->prepare("
        SELECT SUM(t.amount) AS total_tag_amount
        FROM Transactions t
        JOIN Transaction_Tags tt ON t.id = tt.transaction_id
        WHERE t.wallet_id = ? AND t.user_id = ? AND t.id != ?
      ");
      $stmt->bind_param("iii", $wallet_id, $user_id, $transaction_id);
      $stmt->execute();
      $res = $stmt->get_result();
      $data = $res->fetch_assoc();
      $total_tag_amount = floatval($data['total_tag_amount'] ?? 0);
      $stmt->close();

      $new_total = $total_tag_amount + $amount;

      if ($new_total > $balance) {
        $remaining = $balance - $total_tag_amount;
        $errors[] = "Số dư ví không đủ. Bạn chỉ còn lại " . number_format($remaining, 0, ',', '.') . "₫ để cập nhật tag này.";
      }
    }
  }

  if (empty($errors)) {
    //  Cập nhật Tag
    $stmt = $conn->prepare("UPDATE Tags SET name = ?, icon = ?, edit_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $name, $icon, $tag_id, $user_id);

    $stmt->execute();
    $stmt->close();

    //  Cập nhật Transaction
    $stmt = $conn->prepare("UPDATE Transactions SET amount = ?, wallet_id = ?, edit_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("diii", $amount, $wallet_id, $transaction_id, $user_id);
    $stmt->execute();
    $stmt->close();

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
</head>
<body class="flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded shadow w-full max-w-lg">
    <h1 class="text-xl font-bold mb-1 text-center">
      CHỈNH SỬA TAG
    </h1>
     <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-1">
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
                 oninput="this.setCustomValidity('')" >
              
        </div>
        
        <div class="flex flex-col gap-1 mb-3 w-full">
          <label class="font-medium text-gray-700">Chọn Icon</label>

          <div class="grid grid-cols-6 gap-2 mb-4">
            <?php
              $icons = ['🏷️','💸','🍔','🎁','🚗','🎓','🏡','📱','💻','📚','💳','🥦','🍎','🥤','⚡','💧'];
              foreach ($icons as $opt_icon):
            ?>
            <label class="cursor-pointer">
              <input type="radio" name="icon" value="<?= $opt_icon ?>" 
                    class="hidden peer"
                    <?= ($icon == $opt_icon ? 'checked' : '') ?>>

              <span class="inline-block text-2xl border rounded-md p-2 w-full text-center
                          transition-all
                          peer-checked:bg-blue-500 peer-checked:text-white 
                          peer-checked:border-blue-600 peer-checked:shadow-lg
                          hover:bg-gray-100">
                <?= $opt_icon ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>


        
         <div class="mb-4">
        <label for="amount" class="block font-medium mb-1">Tổng tiền giao dịch</label>
        <input type="number" name="amount" id="amount" value="<?= htmlspecialchars($amount) ?>"
               class="w-full border border-gray-300 rounded px-3 py-2" step="500" required
              oninvalid="this.setCustomValidity('Vui lòng nhập số tiền hợp lệ.')"
              oninput="checkAmount(this)">
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
          <button type="button"
            onclick="window.parent.closeEditTagModal()"
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
            Cập nhật
          </button>
        </div>

      </form>
    
  </div>
            <script>
                function checkAmount(input) {
                  if (parseFloat(input.value) < 0) {
                    input.setCustomValidity("Không được nhập số âm.");
                  } else {
                    input.setCustomValidity("");
                  }
                }
               </script>
</body>
</html>

<script>
  const walletSelect = document.getElementById("wallet_id");
  const amountInput = document.getElementById("amount");

  function updateStep() {
    const selectedOption = walletSelect.options[walletSelect.selectedIndex];
    const walletName = selectedOption.text.toLowerCase();
    const specialNames = ["visa", "ngân hàng"];

    if (specialNames.some(keyword => walletName.includes(keyword))) {
      amountInput.step = "1";
    } else {
      amountInput.step = "500";
    }
  }

  walletSelect.addEventListener("change", updateStep);
  updateStep(); // Gọi ngay khi load trang
</script>
