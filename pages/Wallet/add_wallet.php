<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

// 🔐 Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập trước.");
}

$user_id = $_SESSION['user_id'];
$errors = [];
$name = '';
$type = '';
$balance = '';
$currency = '';

// 🔹 Xử lý form khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    $currency = trim($_POST['currency'] ?? '');

    if ($name === '') $errors[] = "Tên ví không được để trống.";
    if ($type === '') $errors[] = "Loại ví không được để trống.";
    if ($balance < 0) $errors[] = "Số dư không được âm.";
    if ($currency === '') $errors[] = "Tiền tệ không được để trống.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Wallets (user_id, name, type, balance, currency, created_at, edit_at) 
                                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("issds", $user_id, $name, $type, $balance, $currency);
        $stmt->execute();
        $stmt->close();

        // Sau khi thêm xong, đóng modal và reload parent
        echo "<script>
                window.parent.closeAddWalletModal?.();
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
<title>Thêm Ví Mới</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class=" font-sans min-h-screen flex items-center justify-center m-0 p-0">
  <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-lg">
    <h1 class="text-2xl font-bold mb-6 text-center tracking-wide text-gray-900 drop-shadow-sm">
        Thêm Ví Mới
    </h1>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?php foreach ($errors as $error): ?>
            <div>- <?= htmlspecialchars($error) ?></div>
        <?php endforeach ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-5">

        <!-- TÊN VÍ -->
        <div class="flex flex-col gap-1">
            <label class="font-medium text-gray-700">Tên ví</label>
            <input type="text" name="name"
            value="<?= htmlspecialchars($name) ?>"
            placeholder="Nhập tên ví"
            class="border px-3 py-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
            required
            oninvalid="this.setCustomValidity('Vui lòng nhập tên ví.')"
            oninput="this.setCustomValidity('')">
        </div>

        <!-- LOẠI VÍ -->
        <div class="flex flex-col gap-1">
            <label class="font-medium text-gray-700">Loại ví</label>
            <select name="type"
            class="border px-3 py-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
            required>
            <option value="">Chọn loại ví</option>
            <option value="Cá nhân" <?= $type === 'Cá nhân' ? 'selected' : '' ?>>Cá nhân</option>
            <option value="Doanh nghiệp" <?= $type === 'Doanh nghiệp' ? 'selected' : '' ?>>Doanh nghiệp</option>
            </select>
        </div>

        <!-- ĐƠN VỊ TIỀN TỆ -->
        <div class="flex flex-col gap-1">
            <label class="font-medium text-gray-700">Đơn vị tiền tệ</label>
            <select name="currency"
            class="border px-3 py-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
            required>
            <option value="">Chọn đơn vị tiền tệ</option>
            <option value="VND" <?= $currency === 'VND' ? 'selected' : '' ?>>VND</option>
            <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
        </div>

        <!-- SỐ DƯ -->
        <div class="flex flex-col gap-1">
            <label class="font-medium text-gray-700">Số dư</label>
            <input type="number" name="balance"
            value="<?= htmlspecialchars($balance) ?>"
            placeholder="Nhập số dư"
            class="border px-3 py-2 rounded focus:ring-2 focus:ring-blue-400 outline-none"
            step="1"
            oninput="checkBalance(this)"
            onkeypress="return event.key !== '-';">
        </div>

        <!-- NÚT -->
        <div class="flex gap-4 justify-end pt-4">
            <button type="button" onclick="window.parent.closeAddWalletModal()"
            class="px-4 py-2 rounded text-white font-semibold
                    bg-gradient-to-r from-red-500 to-red-700
                    hover:from-red-600 hover:to-red-800 transition">
            Huỷ
            </button>

            <button type="submit"
            class="px-4 py-2 rounded text-white font-semibold
                    bg-gradient-to-r from-blue-500 to-blue-700
                    hover:from-blue-600 hover:to-blue-800 transition">
            Lưu
            </button>
        </div>

        </form>

</div>

<script>
function checkBalance(input) {
    const value = input.value;
    if (value.includes('-') || parseFloat(value) < 0) {
        input.setCustomValidity("Không được nhập số âm hoặc dấu '-'");
    } else {
        input.setCustomValidity("");
    }
}
</script>
</body>
</html>
