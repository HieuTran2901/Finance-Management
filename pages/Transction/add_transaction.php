<?php
require_once __DIR__ . '/../../module/config.php';
session_start();
$errors = [];

if (!isset($_SESSION['user_id'])) {
  echo "<script>
    window.parent.closeAddTransactionModal?.();
    window.parent.location.reload();
  </script>";
  exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wallet_id = intval($_POST["wallet_id"]);
    $category_name = trim($_POST["category"]);
    $amount = floatval($_POST["amount"]);
    $type = $_POST["type"];
    $note = $_POST["note"];
    $date = $_POST["date"];
    $emotion = isset($_POST["emotion_level"]) ? intval($_POST["emotion_level"]) : 1;
    $created_at = date("Y-m-d H:i:s");
    $tags = array_filter(array_map("trim", explode(",", $_POST["tags"])));

    $receipt_url = null;
    if (isset($_FILES["photo_receipt"]) && $_FILES["photo_receipt"]["error"] == 0) {
        $filename = time() . '_' . basename($_FILES["photo_receipt"]["name"]);
        $target_dir = __DIR__ . "/uploads/";
        $target_path = $target_dir . $filename;

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES["photo_receipt"]["tmp_name"], $target_path)) {
            $receipt_url = "uploads/" . $filename;
        } else {
            $errors[] = "Không thể lưu ảnh hóa đơn. Vui lòng kiểm tra thư mục 'uploads/'.";
        }
    }

    // 1. Kiểm tra Category
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

    // 2. Kiểm tra tag trước khi insert giao dịch
    foreach ($tags as $tag_name) {
        $stmt = $conn->prepare("SELECT id, limit_amount FROM Tags WHERE name = ? AND user_id = ?");
        $stmt->bind_param("si", $tag_name, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tag_data = $result->fetch_assoc();
        $stmt->close();

        if (!$tag_data) continue;

        $tag_id = $tag_data['id'];
        $tag_limit = $tag_data['limit_amount'];

        if ($type === 'expense') {
            $stmt = $conn->prepare("SELECT COALESCE(SUM(t.amount), 0) FROM Transactions t JOIN Transaction_Tags tt ON t.id = tt.transaction_id WHERE tt.tag_id = ? AND t.wallet_id = ? AND t.type = 'expense'");
            $stmt->bind_param("ii", $tag_id, $wallet_id);
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

    // 3. Thêm giao dịch nếu không có lỗi
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Transactions (user_id, wallet_id, category_id, amount, type, date, note, photo_receipt_url, emotion_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidssssss", $user_id, $wallet_id, $category_id, $amount, $type, $date, $note, $receipt_url, $emotion, $created_at);
        $stmt->execute();
        $transaction_id = $stmt->insert_id;
        $stmt->close();

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

        echo "<script>
          window.parent.closeAddTransactionModal?.();
          window.parent.location.reload();
        </script>";
        exit;
    }
}

$wallets_result = $conn->query("SELECT id, name FROM Wallets WHERE user_id = $user_id");
$tags_result = $conn->query("SELECT name FROM Tags WHERE user_id = $user_id");
$tags_suggest = [];
while ($row = $tags_result->fetch_assoc()) {
    $tags_suggest[] = $row['name'];
}
?>


<!-- HTML Form -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm Giao Dịch</title>
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
<body class=" font-sans min-h-screen flex items-center justify-center m-0 p-0">
  <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg">
    <h1 class="text-2xl font-bold mb-6 text-center tracking-wide text-gray-900 drop-shadow-sm">
      THÊM GIAO DỊCH
    </h1>
    <form method="POST" enctype="multipart/form-data" >
    <div>
      <label class="block font-medium mb-1">Tên </label>
      <input name="category" required class="w-full border p-2 rounded"
      value="<?= htmlspecialchars($_POST['category'] ?? '') ?>"
      oninvalid="this.setCustomValidity('Vui lòng nhập tên.')"
      oninput="this.setCustomValidity('')">

    </div>

    <div>
      <label class="block font-medium mb-1">Chọn ví</label>
      <select name="wallet_id" id="wallet_id" required class="w-full border p-2 rounded"
        oninvalid="this.setCustomValidity('Vui lòng chọn ví.')"
        oninput="this.setCustomValidity('')">
        <option value="">-- Chọn ví --</option> 
        <?php while ($wallet = $wallets_result->fetch_assoc()): ?>
          <option value="<?= $wallet['id'] ?>" 
            <?= (isset($_POST['wallet_id']) && $_POST['wallet_id'] == $wallet['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($wallet['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    

    <div class="flex gap-4">
      <div class="flex-1">
        <label class="block font-medium mb-1">Loại giao dịch</label>
       <select name="type" id="type" class="w-full border p-2 rounded">
        <option value="expense" <?= (($_POST['type'] ?? '') === 'expense') ? 'selected' : '' ?>>Chi</option>
        <option value="income" <?= (($_POST['type'] ?? '') === 'income') ? 'selected' : '' ?>>Thu</option>
      </select>
      </div>
      <div class="flex-1">
        <label class="block font-medium mb-1">Số tiền</label >
        <input type="number" name="amount" step="0.01" required class="w-full border p-2 rounded"
        value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
        oninvalid="this.setCustomValidity('Vui lòng nhập số tiền phù hợp.')"
        oninput="checkAmount(this)">

      </div>
    </div>

    <div class="mb-4">
      <label class="block font-medium mb-1">Ngày giao dịch</label >
      <input type="datetime-local" name="date" required class="w-full border p-2 rounded"
        value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
        oninvalid="this.setCustomValidity('Vui lòng chọn ngày.')"
        oninput="this.setCustomValidity('')">

    </div>

    <div class="mb-4">
      <input type="text" name="note" class="w-full border p-2 rounded"
      value="<?= htmlspecialchars($_POST['note'] ?? '') ?>">

    </div>

    <div class="mb-4">
      <label class="block font-medium mb-1">Ảnh hóa đơn</label>
      <input type="file" name="photo_receipt" class="w-full border p-2 rounded">
    </div>

    <!-- <div>
      <label class="block font-medium mb-1">Cảm xúc</label>
      <input type="range" min="1" max="5" name="emotion_level" class="w-full">
    </div> -->

    <div class="mb-6" id="tags-section">
      <label class="block font-medium mb-1">Tags</label>
      <input type="text" name="tags" id="tags" class="w-full border p-2 rounded" list="tags-list"
        value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>"
        <?= (($_POST['type'] ?? 'expense') === 'income') ? '' : 'required' ?>
        oninvalid="this.setCustomValidity('Vui lòng chọn Tags.')"
        oninput="this.setCustomValidity('')">

      <datalist id="tags-list">
        <?php foreach ($tags_suggest as $tag): ?>
          <option value="<?= htmlspecialchars($tag) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>


      <div class="flex gap-4 justify-end">
        <button type="button" onclick="window.parent.closeAddTransactionModal()"
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
          Lưu giao dịch
        </button>
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
        datalist.innerHTML = ""; // Clear old options
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
    document.getElementById("tags-section").classList.add("hidden");
  } else {
    tagsInput.required = true;
    document.getElementById("tags-section").classList.remove("hidden");
  }
}

  typeSelect.addEventListener("change", toggleTagsRequired);
  toggleTagsRequired(); // Gọi lần đầu để set theo mặc định
});

</script>