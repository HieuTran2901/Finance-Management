<?php
session_start();
require_once '../../module/config.php';

// ⛔ Nếu chưa đăng nhập → chuyển về login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../dangkydangnhap/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ====================== LẤY THÔNG TIN USER ======================
$sql_user = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$sql_user->bind_param("i", $user_id);
$sql_user->execute();
$users = $sql_user->get_result()->fetch_assoc();

// ====================== LẤY TỔNG SỐ DƯ ======================
$sql_wallets = $conn->prepare("SELECT SUM(balance) AS total_balance FROM wallets WHERE user_id = ?");
$sql_wallets->bind_param("i", $user_id);
$sql_wallets->execute();
$total_balance = $sql_wallets->get_result()->fetch_assoc()['total_balance'] ?? 0;

// ====================== THỜI GIAN HIỆN TẠI ======================
$current_year = date('Y');
$current_month = date('m');

// ====================== THÁNG TRƯỚC ======================
$previous_time = strtotime('-1 month');
$previous_month = date('m', $previous_time);
$previous_year = date('Y', $previous_time);

// ====================== HÀM TÍNH TỔNG ======================
function get_total_by_type($conn, $user_id, $type, $month, $year) {
    $sql = $conn->prepare("
        SELECT SUM(amount) AS total
        FROM transactions 
        WHERE user_id = ? AND type = ? AND MONTH(date) = ? AND YEAR(date) = ?
    ");
    $sql->bind_param("ssii", $user_id, $type, $month, $year);
    $sql->execute();
    return $sql->get_result()->fetch_assoc()['total'] ?? 0;
}

$current_expense = get_total_by_type($conn, $user_id, "expense", $current_month, $current_year);
$previous_expense = get_total_by_type($conn, $user_id, "expense", $previous_month, $previous_year);

$current_income = get_total_by_type($conn, $user_id, "income", $current_month, $current_year);
$previous_income = get_total_by_type($conn, $user_id, "income", $previous_month, $previous_year);

// ====================== LẤY TÊN TẤT CẢ VÍ ======================
$sql_main_wallet = $conn->prepare("SELECT name FROM wallets WHERE user_id = ?");
$sql_main_wallet->bind_param("i", $user_id);
$sql_main_wallet->execute();
$main_wallets = $sql_main_wallet->get_result()->fetch_all(MYSQLI_ASSOC);

// ====================== DANH MỤC CHI TIÊU LỚN NHẤT ======================
$sql_biggest = $conn->prepare("
    SELECT c.name, SUM(t.amount) AS total 
    FROM transactions t 
    JOIN categories c ON c.id = t.category_id 
    WHERE t.user_id = ? AND t.type = 'expense' 
      AND MONTH(t.date) = ? AND YEAR(t.date) = ?
    GROUP BY c.name 
    ORDER BY total DESC 
    LIMIT 1
");
$sql_biggest->bind_param("iii", $user_id, $current_month, $current_year);
$sql_biggest->execute();
$biggest = $sql_biggest->get_result()->fetch_assoc();

$biggest_category_name = $biggest['name'] ?? "Không có dữ liệu";
$biggest_category_amount = $biggest['total'] ?? 0;

// Danh sách tên ví
$wallet_string = implode(", ", array_column($main_wallets, "name"));

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 🎨 CSS UI đẹp -->
    <style>
        body {
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
        }
        .avatar-box {
            transition: 0.25s ease;
        }
        .avatar-box:hover {
            transform: scale(1.08);
            box-shadow: 0 10px 25px rgba(59,130,246,0.35);
        }
        .card {
            transition: 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .fade-in {
            animation: fadeIn 0.8s ease both;
        }
        @keyframes fadeIn {
            from { opacity:0; transform: translateY(10px); }
            to   { opacity:1; transform: translateY(0px); }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
  <div class="max-w-5xl mx-auto p-6 bg-white shadow-2xl rounded-2xl mt-10">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800 text-center absolute left-1/2 -translate-x-1/2">
        HỒ SƠ CÁ NHÂN
      </h1>

      <!-- Nút nằm bên phải -->
      <div class="ml-auto flex gap-3">
        <a href="edit_profile.php" class="text-sm text-white bg-blue-500 hover:bg-blue-600 px-5 py-2 rounded-lg shadow">
          Chỉnh sửa
        </a>

        <!-- NÚT QUAY LẠI -->
        <a href="../../index.php" class="text-sm text-white bg-gray-500 hover:bg-gray-600 px-5 py-2 rounded-lg shadow">
          Quay lại
        </a>
      </div>
    </div>

    <!-- Thông tin người dùng: Avatar + Info nằm bên trái -->
    <div class="flex flex-col sm:flex-row items-start gap-6">

      <!-- Avatar -->
      <div class="w-32 h-32 rounded-full ring-4 ring-blue-300 shadow-md flex items-center justify-center bg-blue-100 text-3xl font-bold">
        <?= strtoupper(substr($users['username'], 0, 1)) ?>
      </div>

      <!-- Info (Nằm bên trái luôn khi trên PC) -->
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Nguyễn Văn Tài</h2>
        <p class="text-gray-600 mt-1">Email: <span class="text-blue-600"><?= $users['email'] ?></span></p>
        <p class="text-gray-600">Số điện thoại: <span class="text-gray-800">0987 654 321</span></p>
        <p class="text-gray-600">Ngày sinh: <span class="text-gray-800">15/05/1997</span></p>
        <p class="text-gray-600">Giới tính: <span class="text-gray-800">Nam</span></p>
        <p class="text-gray-600">Địa chỉ: <span class="text-gray-800">TP. Hồ Chí Minh, Việt Nam</span></p>
        <p class="text-gray-600">Thành viên từ: <span class="text-gray-800">03/2024</span></p>

        <p class="text-green-600 font-semibold mt-2 inline-block bg-green-100 px-3 py-1 rounded-full text-sm">
          Tài khoản đang hoạt động
        </p>
      </div>
    </div>

    <!-- Tài chính -->
    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div class="bg-blue-50 p-6 rounded-xl shadow">
        <h3 class="text-gray-700 text-lg font-medium">💰 Số dư hiện tại</h3>
        <p class="text-3xl font-bold text-blue-600 mt-2"><?= number_format($total_balance, 0) ?> đ</p>
      </div>

      <div class="bg-green-50 p-6 rounded-xl shadow">
        <h3 class="text-gray-700 text-lg font-medium">🎯 Mục tiêu tiết kiệm</h3>
        <p class="text-xl text-green-600 mt-2">50.000.000 đ</p>
        <div class="w-full bg-gray-200 rounded-full h-4 mt-3">
          <div class="bg-green-500 h-4 rounded-full" style="width: 47%"></div>
        </div>
        <p class="text-sm text-gray-500 mt-1">Đã đạt: 47%</p>
      </div>
    </div>

    <!-- Tổng quan -->
    <div class="mt-10 bg-gray-50 p-6 rounded-xl shadow-inner">
      <h3 class="text-gray-700 text-lg font-semibold mb-4">📊 Tổng quan tài chính</h3>

      <ul class="space-y-2 text-gray-700 text-sm leading-relaxed">
        <li>• Tổng thu nhập tháng này: <span class="font-bold text-green-600"><?= number_format($current_income, 0) ?> đ</span></li>
        <li>• Tổng chi tiêu tháng này: <span class="font-bold text-red-500"><?= number_format($current_expense, 0) ?> đ</span></li>

        <?php 
            $wallet_names = array_map(fn($w) => $w['name'], $main_wallets);
            $wallet_string = implode(', ', $wallet_names);
        ?>

        <li>• Ví đang sử dụng: <span class="font-semibold"><?= htmlspecialchars($wallet_string) ?></span></li>

        <li>• Danh mục chi tiêu lớn nhất: 
          <span class="text-indigo-600 font-medium">
            <?= htmlspecialchars($biggest_category_name) ?> - <?= number_format($biggest_category_amount, 0) ?> đ
          </span>
        </li>
      </ul>
    </div>

  </div>
</body>

</html>
