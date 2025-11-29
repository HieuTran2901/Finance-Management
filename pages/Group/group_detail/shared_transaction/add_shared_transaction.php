<?php
session_start();
require_once __DIR__ . '/../../../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($group_id <= 0) die("ID nhóm không hợp lệ.");

$errors = [];
$amount = '';
$description = '';
$split_type = 'equal';

// Lấy thành viên
$stmt = $conn->prepare("SELECT u.id, u.username FROM Group_Members gm JOIN Users u ON gm.user_id = u.id WHERE gm.group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = floatval($_POST['amount'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  $split_type = $_POST['split_type'] ?? 'equal';

  if ($amount <= 0) $errors[] = 'Số tiền không hợp lệ.';
  if ($description === '') $errors[] = 'Mô tả không được để trống.';

  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO Shared_Transactions (group_id, created_by, amount, description, date, split_type) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("iisss", $group_id, $user_id, $amount, $description, $split_type);
    $stmt->execute();
    $transaction_id = $conn->insert_id;
    $stmt->close();

    if ($split_type === 'equal') {
    $share = $amount / count($members);
    foreach ($members as $m) {
      $stmt = $conn->prepare("INSERT INTO Shared_Transaction_Participants (shared_transaction_id, user_id, share_amount) VALUES (?, ?, ?)");
      $stmt->bind_param("iid", $transaction_id, $m['id'], $share);
      $stmt->execute();
      $stmt->close();
    }
  } elseif ($split_type === 'custom') {
      $custom_share = $_POST['custom_share'] ?? [];
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
      }
    }

    header("Location: ../group_detail.php?id=$group_id");
    exit;

  }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm giao dịch nhóm</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php if (!empty($errors)): ?>
  <div id="comingSoonModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center relative animate-fade-in">
      <h2 class="text-2xl font-semibold text-red-600 mb-3">Lỗi vui lòng sửa lại</h2>
      <p id="modalMessage" class="text-gray-700 mb-6">
        <?php foreach ($errors as $error): ?>
           <?= htmlspecialchars($error) ?><br>
        <?php endforeach ?>
      </p>
      <button id="closeModal" class="bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 transition">Đóng</button>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const modal = document.getElementById("comingSoonModal");
      const closeBtn = document.getElementById("closeModal");

      closeBtn.addEventListener("click", function () {
        modal.classList.add("hidden");
      });
    });
  </script>
<?php endif ?>
<body class="bg-gray-100 p-6 font-sans">
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-xl font-semibold mb-4">Thêm giao dịch nhóm</h1>
  

  <form method="POST">
    <label class="block font-medium mb-1">Số tiền</label>
    <input name="amount" type="number" step="0.01" required value="<?= htmlspecialchars($amount) ?>" class="w-full border p-2 rounded mb-4">

    <label class="block font-medium mb-1">Mô tả</label>
    <input name="description" type="text" required 
    oninvalid="this.setCustomValidity('Vui lòng nhập mô tả.')"
     oninput="this.setCustomValidity('')"
    
    value="<?= htmlspecialchars($description) ?>" class="w-full border p-2 rounded mb-4">

   <div class="mb-4">
      <label class="block font-medium mb-1">Kiểu chia</label>
      <div class="flex gap-4">
        <button type="button" id="btn-equal" class="split-btn bg-blue-500 text-white px-4 py-2 rounded" data-type="equal">
          Chia đều
        </button>
        <button type="button" id="btn-custom" class="split-btn bg-gray-300 text-black px-4 py-2 rounded" data-type="custom">
          Chia từng người
        </button>
      </div>
      <input type="hidden" name="split_type" id="split_type" value="equal">
    </div>
    <div id="custom-split-section" class="hidden mt-4">
    <label class="block font-medium mb-2">Phân chia từng người:</label>
      <?php foreach ($members as $m): ?>
        <div class="flex items-center gap-2 mb-2">
          <label class="w-32"><?= htmlspecialchars($m['username']) ?></label>
          <input type="number" step="0.01" name="custom_share[<?= $m['id'] ?>]" class="border p-2 rounded w-full" placeholder="Nhập số tiền">
        </div>
      <?php endforeach; ?>
    </div>


    <div class="flex justify-between">
      <a href="../group_detail.php?id=<?= $group_id ?>" class="bg-gray-500 text-white px-4 py-2 rounded">Huỷ</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Lưu giao dịch</button>
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
  }

  btnEqual.addEventListener("click", () => activate("equal"));
  btnCustom.addEventListener("click", () => activate("custom"));
});
</script>
