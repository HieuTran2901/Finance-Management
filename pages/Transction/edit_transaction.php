<?php
require_once __DIR__ . '/../../module/config.php';
session_start();
$errors = [];

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $transaction_id = intval($_GET['id']);

    // Lấy thông tin giao dịch để điền vào form
    $stmt = $conn->prepare("
      SELECT t.*, c.name AS category_name 
      FROM Transactions t 
      JOIN Categories c ON t.category_id = c.id 
      WHERE t.id = ? AND t.user_id = ?
    ");

    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    $categories_result = $conn->query("SELECT id, name FROM Categories WHERE user_id = $user_id");

    if (!$transaction) {
        die("Không tìm thấy giao dịch.");
    }

    // Lấy các tag đã gán
    $stmt = $conn->prepare("SELECT t.name FROM Tags t JOIN Transaction_Tags tt ON t.id = tt.tag_id WHERE tt.transaction_id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $tags = [];
    while ($row = $res->fetch_assoc()) {
        $tags[] = $row['name'];
    }
    $stmt->close();

    // Xử lý cập nhật giao dịch nếu có POST
   if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_name = trim($_POST['category']);
    $wallet_id = intval($_POST['wallet_id']);
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];
    $note = $_POST['note'];
    $tags_input = $_POST['tags'];
    $tags = array_filter(array_map('trim', explode(',', $tags_input)));

    $receipt_url = $transaction['photo_receipt_url'];
    if (isset($_FILES["photo_receipt"]) && $_FILES["photo_receipt"]["error"] == 0) {
        $filename = time() . '_' . basename($_FILES["photo_receipt"]["name"]);
        $target_path = "uploads/" . $filename;
        move_uploaded_file($_FILES["photo_receipt"]["tmp_name"], $target_path);
        $receipt_url = $conn->real_escape_string($target_path);
    }

    // Sửa tên danh mục hiện tại (Categories.name)
    $category_id = $transaction['category_id'];
    $stmt = $conn->prepare("UPDATE Categories SET name = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $category_name, $category_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Kiểm tra giới hạn tag nếu là chi tiêu
    if ($type === 'expense') {
        foreach ($tags as $tag_name) {
            $stmt = $conn->prepare("SELECT id, limit_amount FROM Tags WHERE name = ? AND user_id = ?");
            $stmt->bind_param("si", $tag_name, $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $tag_data = $res->fetch_assoc();
            $stmt->close();

            if (!$tag_data) continue;

            $tag_id = $tag_data['id'];
            $tag_limit = $tag_data['limit_amount'];

            $stmt = $conn->prepare("SELECT COALESCE(SUM(t.amount), 0) FROM Transactions t JOIN Transaction_Tags tt ON t.id = tt.transaction_id WHERE tt.tag_id = ? AND t.wallet_id = ? AND t.type = 'expense' AND t.id != ?");
            $stmt->bind_param("iii", $tag_id, $wallet_id, $transaction_id);
            $stmt->execute();
            $stmt->bind_result($used_amount);
            $stmt->fetch();
            $stmt->close();

            if (($used_amount + $amount) > $tag_limit) {
                $errors[] = " Giao dịch vượt quá giới hạn của tag '{$tag_name}'. \nĐã dùng: " 
                  . number_format($used_amount, 0, ',', '.') . "₫, giới hạn: " 
                  . number_format($tag_limit, 0, ',', '.') . "₫.";

                break; // Dừng kiểm tra tag tiếp theo

                
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Transactions SET category_id=?, wallet_id=?, amount=?, type=?, date=?, note=?, photo_receipt_url=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iisssssii", $category_id, $wallet_id, $amount, $type, $date, $note, $receipt_url, $transaction_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->query("DELETE FROM Transaction_Tags WHERE transaction_id = $transaction_id");

        foreach ($tags as $tag_name) {
            $stmt = $conn->prepare("SELECT id FROM Tags WHERE name = ? AND user_id = ?");
            $stmt->bind_param("si", $tag_name, $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $tag_data = $res->fetch_assoc();
            $stmt->close();

            if ($tag_data) {
                $tag_id = $tag_data['id'];
                $stmt = $conn->prepare("INSERT INTO Transaction_Tags (transaction_id, tag_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $transaction_id, $tag_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: Transaction.php");
        exit;
    }
}


} else {
    die("Thiếu ID giao dịch để chỉnh sửa.");
}

$wallets_result = $conn->query("SELECT id, name FROM Wallets WHERE user_id = $user_id");
$tags_result = $conn->query("SELECT name FROM Tags WHERE user_id = $user_id");
$tags_suggest = [];
while ($row = $tags_result->fetch_assoc()) {
    $tags_suggest[] = $row['name'];
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chỉnh sửa Giao Dịch</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<!-- thông báo -->
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
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
  <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow w-full max-w-2xl space-y-4">
    <h2 class="text-2xl font-bold text-center mb-4">Chỉnh sửa Giao Dịch</h2>




    <div>
      <input name="category" value="<?= htmlspecialchars($transaction['category_name']) ?>" list="category-list" required class="w-full border p-2 rounded"
        oninvalid="this.setCustomValidity('Vui lòng nhập danh mục hợp lệ.')"
        oninput="this.setCustomValidity('')">

      <datalist id="category-list">
        <?php
          $categories_result2 = $conn->query("SELECT name FROM Categories WHERE user_id = $user_id");
          while ($cat = $categories_result2->fetch_assoc()):
        ?>
          <option value="<?= htmlspecialchars($cat['name']) ?>">
        <?php endwhile; ?>
      </datalist>

    </div>

    <div>
      <label class="block font-medium mb-1">Chọn ví</label>
      <select name="wallet_id" id="wallet_id" required class="w-full border p-2 rounded" 
       oninvalid="this.setCustomValidity('Vui lòng chọn ví .')"  
        oninput="this.setCustomValidity('')">
        <?php while ($wallet = $wallets_result->fetch_assoc()): ?>
          <option value="<?= $wallet['id'] ?>" <?= $wallet['id'] == $transaction['wallet_id'] ? 'selected' : '' ?>><?= htmlspecialchars($wallet['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="flex gap-4">
      <div class="flex-1">
        <label class="block font-medium mb-1">Loại giao dịch</label>
        <select name="type" id="type"class="w-full border p-2 rounded">
          <option value="expense" <?= $transaction['type'] == 'expense' ? 'selected' : '' ?>>Chi</option>
          <option value="income" <?= $transaction['type'] == 'income' ? 'selected' : '' ?>>Thu</option>
        </select>
      </div>
      <div class="flex-1">
        <label class="block font-medium mb-1">Số tiền</label>
        <input type="number" name="amount" value="<?= htmlspecialchars($transaction['amount']) ?>" step="0.01" required class="w-full border p-2 rounded"
         oninvalid="this.setCustomValidity('Vui lòng nhập số tiền phù hợp.')"
        oninput="checkAmount(this)" >
        
      </div>
    </div>

    <div>
      <label class="block font-medium mb-1">Ngày giao dịch</label>
      <input type="datetime-local" name="date" value="<?= date('Y-m-d\TH:i', strtotime($transaction['date'])) ?>" required class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Ghi chú</label>
      <input type="text" name="note" value="<?= htmlspecialchars($transaction['note']) ?>" class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Ảnh hóa đơn</label>
      <input type="file" name="photo_receipt" class="w-full border p-2 rounded">
    </div>

    <div>
      <label class="block font-medium mb-1">Tags (ngăn cách bằng dấu phẩy)</label>
      <input type="text" name="tags" id="tags" value="<?= htmlspecialchars(implode(',', $tags)) ?>" class="w-full border p-2 rounded" list="tags-list">
      <datalist id="tags-list">
        <?php foreach ($tags_suggest as $tag): ?>
          <option value="<?= htmlspecialchars($tag) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="flex justify-between">
      <a href="Transaction.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Huỷ</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cập nhật giao dịch</button>
    </div>
  </form>
</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const walletSelect = document.getElementById("wallet_id");
  const datalist = document.getElementById("tags-list");

  walletSelect.addEventListener("change", function () {
    const walletId = walletSelect.value;
    fetch(`get_tags_by_wallet.php?wallet_id=${walletId}`)
      .then(response => response.json())
      .then(tags => {
        datalist.innerHTML = "";
        tags.forEach(tag => {
          const option = document.createElement("option");
          option.value = tag;
          datalist.appendChild(option);
        });
      })
      .catch(error => console.error("Lỗi khi load tag:", error));
  });
});
</script>
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
document.addEventListener("DOMContentLoaded", function () {
  const walletSelect = document.getElementById("wallet_id");
  const datalist = document.getElementById("tags-list");
  const typeSelect = document.getElementById("type");
  const tagsInput = document.getElementById("tags");

  // Cập nhật tag gợi ý theo ví
  walletSelect.addEventListener("change", function () {
    const walletId = walletSelect.value;
    fetch(`get_tags_by_wallet.php?wallet_id=${walletId}`)
      .then(response => response.json())
      .then(tags => {
        datalist.innerHTML = ""; // Clear old options
        tags.forEach(tag => {
          const option = document.createElement("option");
          option.value = tag;
          datalist.appendChild(option);
        });
      })
      .catch(error => console.error("Lỗi khi load tag:", error));
  });

  // Xử lý bật/tắt required cho tags
  function toggleTagsRequired() {
    if (typeSelect.value === "income") {
      tagsInput.required = false;
      tagsInput.setCustomValidity("");
    } else {
      tagsInput.required = true;
    }
  }

  typeSelect.addEventListener("change", toggleTagsRequired);
  toggleTagsRequired(); // Gọi lần đầu để set theo mặc định
});
</script>