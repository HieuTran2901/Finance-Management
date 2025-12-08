<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

$errors = [];

if (!isset($_SESSION['user_id'])) {
  echo "<script>
    window.parent.closeEditTransactionModal?.();
    window.parent.location.reload();
  </script>";
  exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
  die("Thiếu ID giao dịch.");
}

/* ============================
  1. LẤY GIAO DỊCH
============================ */
$stmt = $conn->prepare("SELECT * FROM Transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) die("Không tìm thấy giao dịch.");

/* ============================
  2. TAG CỦA GIAO DỊCH
============================ */
$tags_current = [];
$stmt = $conn->prepare("
  SELECT T.name FROM Tags T
  JOIN Transaction_Tags TT ON TT.tag_id = T.id
  WHERE TT.transaction_id = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $tags_current[] = $row['name'];
}
$stmt->close();

$tags_value = implode(",", $tags_current);

/* ============================
  3. VÍ & TAG GỢI Ý
============================ */
$wallets_result = $conn->query("SELECT id, name FROM Wallets WHERE user_id = $user_id");
$tags_result = $conn->query("SELECT name FROM Tags WHERE user_id = $user_id");

$tags_suggest = [];
while ($row = $tags_result->fetch_assoc()) {
  $tags_suggest[] = $row['name'];
}

/* ============================
  4. UPDATE
============================ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $wallet_id = intval($_POST["wallet_id"]);
  $category_name = trim($_POST["category"]);
  $amount = floatval($_POST["amount"]);
  $type = $_POST["type"];
  $note = $_POST["note"];
  $date = $_POST["date"];
  $emotion = intval($_POST["emotion_level"] ?? 1);
  $tags = array_filter(array_map("trim", explode(",", $_POST["tags"] ?? "")));

  $receipt_url = $transaction['photo_receipt_url'];

  if (!empty($_FILES["photo_receipt"]["name"])) {
    $filename = time() . '_' . basename($_FILES["photo_receipt"]["name"]);
    $target_dir = __DIR__ . "/uploads/";
    $target_path = $target_dir . $filename;

    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    if (move_uploaded_file($_FILES["photo_receipt"]["tmp_name"], $target_path)) {
      $receipt_url = "uploads/" . $filename;
    } else {
      $errors[] = "Không thể lưu ảnh mới.";
    }
  }

  /* ===== CATEGORY ===== */
  $stmt = $conn->prepare("SELECT id FROM Categories WHERE name = ? AND user_id = ?");
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

  /* ===== KIỂM TRA TAG ===== */
  foreach ($tags as $tag_name) {
    $stmt = $conn->prepare("SELECT id, limit_amount FROM Tags WHERE name = ? AND user_id = ?");
    $stmt->bind_param("si", $tag_name, $user_id);
    $stmt->execute();
    $tag_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tag_data) continue;

    $stmt = $conn->prepare("
      SELECT COALESCE(SUM(t.amount), 0)
      FROM Transactions t
      JOIN Transaction_Tags tt ON t.id = tt.transaction_id
      WHERE tt.tag_id = ? AND t.wallet_id = ? AND t.id != ?
    ");
    $stmt->bind_param("iii", $tag_data['id'], $wallet_id, $transaction_id);
    $stmt->execute();
    $stmt->bind_result($used_amount);
    $stmt->fetch();
    $stmt->close();

    if (($used_amount + $amount) > $tag_data['limit_amount']) {
      $errors[] = "Giao dịch vượt giới hạn tag: {$tag_name}";
      break;
    }
  }

  /* ===== UPDATE ===== */
  if (empty($errors)) {

    $stmt = $conn->prepare("
      UPDATE Transactions 
      SET wallet_id=?, category_id=?, amount=?, type=?, date=?, note=?, photo_receipt_url=?, emotion_level=?, edit_at=NOW()
      WHERE id=? AND user_id=?
    ");
    $stmt->bind_param(
      "iiidssssii",
      $wallet_id,
      $category_id,
      $amount,
      $type,
      $date,
      $note,
      $receipt_url,
      $emotion,
      $transaction_id,
      $user_id
    );
    $stmt->execute();
    $stmt->close();

    $conn->query("DELETE FROM Transaction_Tags WHERE transaction_id = $transaction_id");

    foreach ($tags as $tag_name) {
      $stmt = $conn->prepare("SELECT id FROM Tags WHERE name = ? AND user_id = ?");
      $stmt->bind_param("si", $tag_name, $user_id);
      $stmt->execute();
      $tag = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if ($tag) {
        $stmt = $conn->prepare("INSERT INTO Transaction_Tags (transaction_id, tag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $transaction_id, $tag['id']);
        $stmt->execute();
        $stmt->close();
      }
    }

    echo "<script>
      window.parent.closeEditTransactionModal();
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
  <title>Chỉnh sửa Giao Dịch</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans min-h-screen flex items-center justify-center">
  <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg">

    <h1 class="text-2xl font-bold mb-6 text-center">Chỉnh sửa Giao Dịch</h1>

    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?php foreach ($errors as $e): ?>
          <div>- <?= htmlspecialchars($e) ?></div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <form method="POST" enctype="multipart/form-data">

      <div class="mb-3">
        <label>Tên</label>
        <input name="category" class="w-full border p-2 rounded" value="<?= htmlspecialchars($transaction['category_id']) ?>">
      </div>

      <div class="mb-3">
        <label>Chọn ví</label>
        <select name="wallet_id" class="w-full border p-2 rounded">
          <?php while ($wallet = $wallets_result->fetch_assoc()): ?>
            <option value="<?= $wallet['id'] ?>" <?= $transaction['wallet_id'] == $wallet['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($wallet['name']) ?>
            </option>
          <?php endwhile ?>
        </select>
      </div>

      <div class="flex gap-4 mb-3">
        <div class="flex-1">
          <label>Loại</label>
          <select name="type" id="type" class="w-full border p-2 rounded">
            <option value="expense" <?= $transaction['type'] == 'expense' ? 'selected' : '' ?>>Chi</option>
            <option value="income" <?= $transaction['type'] == 'income' ? 'selected' : '' ?>>Thu</option>
          </select>
        </div>

        <div class="flex-1">
          <label>Số tiền</label>
          <input type="number" name="amount" value="<?= $transaction['amount'] ?>" class="w-full border p-2 rounded">
        </div>
      </div>

      <div class="mb-3">
        <input type="datetime-local" name="date"
         value="<?= date('Y-m-d\TH:i', strtotime($transaction['date'])) ?>"
         class="w-full border p-2 rounded">
      </div>

      <div class="mb-3">
        <input type="text" name="note" class="w-full border p-2 rounded"
         value="<?= htmlspecialchars($transaction['note']) ?>">
      </div>

      <div class="mb-3">
        <label>Ảnh mới</label>
        <input type="file" name="photo_receipt" class="w-full border p-2 rounded">
      </div>

      <div class="mb-3" id="tags-section">
        <label>Tags</label>
        <input type="text" name="tags" list="tags-list"
         class="w-full border p-2 rounded" value="<?= htmlspecialchars($tags_value) ?>">
        <datalist id="tags-list">
          <?php foreach ($tags_suggest as $tag): ?>
            <option value="<?= htmlspecialchars($tag) ?>">
          <?php endforeach ?>
        </datalist>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="window.parent.closeEditTransactionModal()"
         class="bg-red-600 text-white px-4 py-2 rounded">Huỷ</button>

        <button type="submit"
         class="bg-blue-600 text-white px-4 py-2 rounded">Cập nhật</button>
      </div>

    </form>
  </div>
</body>
</html>
