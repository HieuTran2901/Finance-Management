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
if ($transaction_id <= 0) die("Thiếu ID giao dịch.");

/* =========================
  1. LẤY GIAO DỊCH
========================= */
$stmt = $conn->prepare("
  SELECT t.*, c.name AS category_name
  FROM Transactions t
  JOIN Categories c ON t.category_id = c.id
  WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) die("Không tìm thấy giao dịch.");

/* =========================
  2. TAG HIỆN TẠI (1 TAG)
========================= */
$current_tag = '';
$stmt = $conn->prepare("
  SELECT T.name
  FROM Tags T
  JOIN Transaction_Tags TT ON TT.tag_id = T.id
  WHERE TT.transaction_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$stmt->bind_result($current_tag);
$stmt->fetch();
$stmt->close();

/* =========================
  3. VÍ
========================= */
$wallets_result = $conn->query("SELECT id, name FROM Wallets WHERE user_id = $user_id");

/* =========================
  4. UPDATE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $wallet_id = intval($_POST["wallet_id"]);
  $category_name = trim($_POST["category"]);
  $amount = floatval($_POST["amount"]);
  $type = $_POST["type"];
  $note = $_POST["note"];
  $date = $_POST["date"];
  $emotion = intval($_POST["emotion_level"] ?? 1);
  $tag = trim($_POST["tag"] ?? '');

  $receipt_url = $transaction['photo_receipt_url'];

  /* ===== UPLOAD ẢNH ===== */
  if (!empty($_FILES["photo_receipt"]["name"])) {
    $filename = time() . '_' . basename($_FILES["photo_receipt"]["name"]);
    $dir = __DIR__ . "/uploads/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (move_uploaded_file($_FILES["photo_receipt"]["tmp_name"], $dir . $filename)) {
      $receipt_url = "uploads/" . $filename;
    } else {
      $errors[] = "Không thể lưu ảnh mới.";
    }
  }

  /* ===== CATEGORY ===== */
  $stmt = $conn->prepare("SELECT id FROM Categories WHERE name=? AND user_id=?");
  $stmt->bind_param("si", $category_name, $user_id);
  $stmt->execute();
  $stmt->bind_result($category_id);

  if (!$stmt->fetch()) {
    $stmt->close();
    $stmt = $conn->prepare("
      INSERT INTO Categories (name, icon, type, user_id)
      VALUES (?, '❓', ?, ?)
    ");
    $stmt->bind_param("ssi", $category_name, $type, $user_id);
    $stmt->execute();
    $category_id = $stmt->insert_id;
  }
  $stmt->close();

  /* ===== KIỂM TRA TAG LIMIT ===== */
  if ($tag) {
    $stmt = $conn->prepare("SELECT id, limit_amount FROM Tags WHERE name=? AND user_id=?");
    $stmt->bind_param("si", $tag, $user_id);
    $stmt->execute();
    $tag_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($tag_data) {
      $stmt = $conn->prepare("
        SELECT COALESCE(SUM(t.amount),0)
        FROM Transactions t
        JOIN Transaction_Tags tt ON t.id = tt.transaction_id
        WHERE tt.tag_id=? AND t.wallet_id=? AND t.id != ?
      ");
      $stmt->bind_param("iii", $tag_data['id'], $wallet_id, $transaction_id);
      $stmt->execute();
      $stmt->bind_result($used_amount);
      $stmt->fetch();
      $stmt->close();

      if (($used_amount + $amount) > $tag_data['limit_amount']) {
        $errors[] = "Giao dịch vượt giới hạn tag: {$tag}";
      }
    }
  }

  /* ===== UPDATE ===== */
  if (empty($errors)) {

    $stmt = $conn->prepare("
      UPDATE Transactions
      SET wallet_id=?, category_id=?, amount=?, type=?, date=?, note=?,
          photo_receipt_url=?, emotion_level=?, edit_at=NOW()
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

    /* TAG */
    $conn->query("DELETE FROM Transaction_Tags WHERE transaction_id = $transaction_id");

    if ($tag && $tag_data) {
      $stmt = $conn->prepare("
        INSERT INTO Transaction_Tags (transaction_id, tag_id)
        VALUES (?, ?)
      ");
      $stmt->bind_param("ii", $transaction_id, $tag_data['id']);
      $stmt->execute();
      $stmt->close();
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
<body class="flex items-center justify-center min-h-screen">

<div class="bg-white p-6 rounded shadow w-full max-w-lg">

<h2 class="text-xl font-bold mb-4 text-center">CHỈNH SỬA GIAO DỊCH</h2>

<?php if ($errors): ?>
  <div class="bg-red-100 text-red-700 p-3 rounded mb-3">
    <?php foreach ($errors as $e): ?>
      <div>- <?= htmlspecialchars($e) ?></div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<form method="POST" enctype="multipart/form-data">

<input name="category" class="w-full border p-2 mb-3 rounded"
 value="<?= htmlspecialchars($transaction['category_name']) ?>">

<select name="wallet_id" class="w-full border p-2 mb-3 rounded" id="wallet">
<?php while ($w = $wallets_result->fetch_assoc()): ?>
  <option value="<?= $w['id'] ?>" <?= $w['id']==$transaction['wallet_id']?'selected':'' ?>>
    <?= htmlspecialchars($w['name']) ?>
  </option>
<?php endwhile ?>
</select>

<select name="type" id="type" class="w-full border p-2 mb-3 rounded">
  <option value="expense" <?= $transaction['type']=='expense'?'selected':'' ?>>Chi</option>
  <option value="income" <?= $transaction['type']=='income'?'selected':'' ?>>Thu</option>
</select>

<input type="number" name="amount" class="w-full border p-2 mb-3 rounded"
 value="<?= $transaction['amount'] ?>">

<input type="datetime-local" name="date"
 value="<?= date('Y-m-d\TH:i', strtotime($transaction['date'])) ?>"
 class="w-full border p-2 mb-3 rounded">

<input name="note" class="w-full border p-2 mb-3 rounded"
 value="<?= htmlspecialchars($transaction['note']) ?>">

<input type="file" name="photo_receipt" class="w-full border p-2 mb-3 rounded">

<select name="tag" id="tag" class="w-full border p-2 mb-4 rounded">
  <option value="">-- Chọn tag --</option>
</select>

<div class="flex justify-end gap-3">
<button type="button" onclick="window.parent.closeEditTransactionModal()"
 class="px-4 py-2 bg-red-600 text-white rounded">Huỷ</button>
<button type="submit"
 class="px-4 py-2 bg-blue-600 text-white rounded">Cập nhật</button>
</div>

</form>
</div>

<script>
const wallet = document.getElementById('wallet');
const tagSelect = document.getElementById('tag');
const currentTag = <?= json_encode($current_tag) ?>;

function loadTags(walletId) {
  tagSelect.innerHTML = '<option value="">-- Chọn tag --</option>';
  fetch(`get_tags_by_wallet.php?wallet_id=${walletId}`)
    .then(r=>r.json())
    .then(tags=>{
      tags.forEach(t=>{
        const o=document.createElement('option');
        o.value=t;
        o.textContent=t;
        if(t===currentTag) o.selected=true;
        tagSelect.appendChild(o);
      });
    });
}

wallet.addEventListener('change',()=>loadTags(wallet.value));
loadTags(wallet.value);
</script>

</body>
</html>
