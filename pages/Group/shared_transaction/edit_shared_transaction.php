<?php
session_start();
require_once __DIR__ . '/../../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

if ($group_id <= 0 || $transaction_id <= 0) die("ID nhóm hoặc giao dịch không hợp lệ.");

$errors = [];
$amount = '';
$description = '';
$split_type = 'equal';
$custom_share = [];

// Lấy thành viên
$stmt = $conn->prepare("SELECT u.id, u.username FROM Group_Members gm JOIN Users u ON gm.user_id = u.id WHERE gm.group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy thông tin giao dịch hiện tại
$stmt = $conn->prepare("SELECT amount, description, split_type FROM Shared_Transactions WHERE id = ? AND group_id = ?");
$stmt->bind_param("ii", $transaction_id, $group_id);
$stmt->execute();
$stmt->bind_result($amount, $description, $split_type);
$stmt->fetch();
$stmt->close();

// Lấy dữ liệu chia từng người nếu là custom (LƯU Ý: LUÔN CHẠY KHI split_type là custom, kể cả GET)
if ($split_type === 'custom') {
  $stmt = $conn->prepare("SELECT user_id, share_amount FROM Shared_Transaction_Participants WHERE shared_transaction_id = ?");
  $stmt->bind_param("i", $transaction_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $custom_share[$row['user_id']] = $row['share_amount'];
  }
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = floatval($_POST['amount'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  $split_type = $_POST['split_type'] ?? 'equal';
  $custom_share = $_POST['custom_share'] ?? [];

  if ($amount <= 0) $errors[] = 'Số tiền không hợp lệ.';
  if ($description === '') $errors[] = 'Mô tả không được để trống.';

  if (empty($errors)) {
    $stmt = $conn->prepare("UPDATE Shared_Transactions SET amount = ?, description = ?, split_type = ?, date = NOW() WHERE id = ? AND group_id = ?");
    $stmt->bind_param("dssii", $amount, $description, $split_type, $transaction_id, $group_id);
    $stmt->execute();
    $stmt->close();

    $conn->query("DELETE FROM Shared_Transaction_Participants WHERE shared_transaction_id = $transaction_id");

    if ($split_type === 'equal') {
      $share = $amount / count($members);
      foreach ($members as $m) {
        $stmt = $conn->prepare("INSERT INTO Shared_Transaction_Participants (shared_transaction_id, user_id, share_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $transaction_id, $m['id'], $share);
        $stmt->execute();
        $stmt->close();
      }
      header("Location: /pages/Group/group_detail/group_detail.php?id=$group_id");
      exit;
    } elseif ($split_type === 'custom') {
      $sum_custom = array_sum(array_map('floatval', $custom_share));
      if (abs($sum_custom - $amount) > 0.01) {
        $errors[] = "Tổng chia không khớp với số tiền gốc.";
      } else {
        foreach ($custom_share as $uid => $share_amt) {
          $share_amt = floatval($share_amt);
          if ($share_amt > 0) {
            $stmt = $conn->prepare("INSERT INTO Shared_Transaction_Participants (shared_transaction_id, user_id, share_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $transaction_id, $uid, $share_amt);
            $stmt->execute();
            $stmt->close();
          }
        }
        if (empty($errors)) {
          header("Location: /pages/Group/group_detail/group_detail.php?id=$group_id");
          exit;

        }
      }
    } else {
      header("Location: /pages/Group/group_detail/group_detail.php?id=$group_id");
      exit;

    }
  }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chỉnh sửa giao dịch nhóm</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 font-sans">
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-xl font-semibold mb-4">Chỉnh sửa giao dịch nhóm</h1>
  <?php if (!empty($errors)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
      <?php foreach ($errors as $error): ?>
        <div>- <?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <label class="block font-medium mb-1">Số tiền</label>
    <input name="amount" type="number" step="0.01" required value="<?= htmlspecialchars($amount) ?>" class="w-full border p-2 rounded mb-4">

    <label class="block font-medium mb-1">Mô tả</label>
    <input name="description" type="text" required value="<?= htmlspecialchars($description) ?>" class="w-full border p-2 rounded mb-4">

    <div class="mb-4">
      <label class="block font-medium mb-1">Kiểu chia</label>
      <div class="flex gap-4">
        <button type="button" id="btn-equal" class="split-btn <?= $split_type === 'equal' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-black' ?> px-4 py-2 rounded" data-type="equal">Chia đều</button>
        <button type="button" id="btn-custom" class="split-btn <?= $split_type === 'custom' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-black' ?> px-4 py-2 rounded" data-type="custom">Chia từng người</button>
      </div>
      <input type="hidden" name="split_type" id="split_type" value="<?= $split_type ?>">
    </div>

    <div id="custom-split-section" class="<?= $split_type === 'custom' ? '' : 'hidden' ?> mt-4">
      <label class="block font-medium mb-2">Phân chia từng người:</label>
      <?php foreach ($members as $m): 
          $uid = $m['id'];
          $value = isset($custom_share[$uid]) ? htmlspecialchars($custom_share[$uid]) : '';
        ?>
          <div class="flex items-center gap-2 mb-2">
            <label class="w-32"><?= htmlspecialchars($m['username']) ?></label>
            <input type="number" step="0.01" name="custom_share[<?= $uid ?>]" class="border p-2 rounded w-full custom-input" placeholder="Nhập số tiền" value="<?= $value ?>">
          </div>
        <?php endforeach; ?>

      <div id="total-share" class="text-right font-semibold text-gray-700 mt-2"></div>
    </div>

    <div class="flex justify-between mt-6">
      <a href="/pages/Group/group_detail/group_detail.php?id=<?= $group_id ?>" class="bg-gray-500 text-white px-4 py-2 rounded">Huỷ</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Lưu chỉnh sửa</button>
    </div>
  </form>
</div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btnEqual = document.getElementById("btn-equal");
  const btnCustom = document.getElementById("btn-custom");
  const splitType = document.getElementById("split_type");
  const customSection = document.getElementById("custom-split-section");

  function activate(type) {
    splitType.value = type;
    if (type === "equal") {
      btnEqual.classList.add("bg-blue-500", "text-white");
      btnEqual.classList.remove("bg-gray-300", "text-black");
      btnCustom.classList.add("bg-gray-300", "text-black");
      btnCustom.classList.remove("bg-blue-500", "text-white");
      customSection.classList.add("hidden");
    } else {
      btnCustom.classList.add("bg-blue-500", "text-white");
      btnCustom.classList.remove("bg-gray-300", "text-black");
      btnEqual.classList.add("bg-gray-300", "text-black");
      btnEqual.classList.remove("bg-blue-500", "text-white");
      customSection.classList.remove("hidden");
    }
    calculateTotal();
  }

  btnEqual.addEventListener("click", () => activate("equal"));
  btnCustom.addEventListener("click", () => activate("custom"));

  const inputs = document.querySelectorAll('.custom-input');
  inputs.forEach(input => input.addEventListener('input', calculateTotal));

  function calculateTotal() {
    let total = 0;
    inputs.forEach(input => {
      total += parseFloat(input.value) || 0;
    });
    document.getElementById('total-share').textContent = 'Tổng tạm tính: ' + total.toFixed(0);
  }

  calculateTotal();
});
</script>
