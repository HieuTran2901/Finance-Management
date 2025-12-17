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
<body class="font-sans min-h-screen flex items-center justify-center bg-gray-100">

  <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-lg">
    <h1 class="text-2xl font-bold mb-6 text-center tracking-wide text-gray-900 drop-shadow-sm">
      THÊM GIAO DỊCH NHÓM
    </h1>

    <form method="POST">

      <!-- Số tiền -->
      <div class="mb-4">
        <label class="block font-medium mb-1">Số tiền</label>
        <input
          name="amount"
          type="number"
          step="0.01"
          required
          value="<?= htmlspecialchars($amount) ?>"
          class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
        >
      </div>

      <!-- Mô tả -->
      <div class="mb-4">
        <label class="block font-medium mb-1">Mô tả</label>
        <input
          name="description"
          type="text"
          required
          value="<?= htmlspecialchars($description) ?>"
          oninvalid="this.setCustomValidity('Vui lòng nhập mô tả.')"
          oninput="this.setCustomValidity('')"
          class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
        >
      </div>

      <!-- Kiểu chia -->
      <div class="mb-4">
        <label class="block font-medium mb-2">Kiểu chia</label>

        <div class="flex gap-4">
          <button
            type="button"
            id="btn-equal"
            data-type="equal"
            class="split-btn flex-1 py-2 rounded font-semibold text-white
                   bg-gradient-to-r from-blue-500 to-blue-700
                   hover:from-blue-600 hover:to-blue-800 transition">
            Chia đều
          </button>

          <button
            type="button"
            id="btn-custom"
            data-type="custom"
            class="split-btn flex-1 py-2 rounded font-semibold
                   bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
            Chia từng người
          </button>
        </div>

        <input type="hidden" name="split_type" id="split_type" value="equal">
      </div>

      <!-- Chia custom -->
      <div id="custom-split-section" class="hidden mt-4 border rounded-lg p-4 bg-gray-50">
        <label class="block font-medium mb-3">Phân chia từng người</label>

        <?php foreach ($members as $m): ?>
          <div class="flex items-center gap-3 mb-2">
            <span class="w-28 text-sm font-medium">
              <?= htmlspecialchars($m['username']) ?>
            </span>
            <input
              type="number"
              step="0.01"
              name="custom_share[<?= $m['id'] ?>]"
              class="flex-1 border p-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
              placeholder="Nhập số tiền"
            >
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Nút -->
      <div class="flex gap-4 justify-end mt-6">
        <a
          href="../group_detail.php?id=<?= $group_id ?>"
          class="px-4 py-2 rounded text-white font-semibold
                 bg-gradient-to-r from-red-500 to-red-700
                 hover:from-red-600 hover:to-red-800 transition">
          Huỷ
        </a>

        <button
          type="submit"
          class="px-4 py-2 rounded text-white font-semibold
                 bg-gradient-to-r from-blue-500 to-blue-700
                 hover:from-blue-600 hover:to-blue-800 transition">
          Lưu giao dịch
        </button>
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
    btnEqual.className =
      "split-btn flex-1 py-2 rounded font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-700";
    btnCustom.className =
      "split-btn flex-1 py-2 rounded font-semibold bg-gray-200 text-gray-700";
    customSection.classList.add("hidden");
  } else {
    btnCustom.className =
      "split-btn flex-1 py-2 rounded font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-700";
    btnEqual.className =
      "split-btn flex-1 py-2 rounded font-semibold bg-gray-200 text-gray-700";
    customSection.classList.remove("hidden");
  }
}


  btnEqual.addEventListener("click", () => activate("equal"));
  btnCustom.addEventListener("click", () => activate("custom"));
});
</script>
