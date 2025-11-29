<?php
  session_start();
    require_once '../../module/config.php';
    $user_id = $_SESSION['user_id']; // Giả sử bạn đã lưu user_id khi đăng nhập
      if (!isset($_SESSION['user_id'])) {
        header("Location: ../../dangkydangnhap/login.php");
      }
   // Lấy tên user
  $sql_user = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
  $sql_user->bind_param("i",$user_id);
  $sql_user->execute();
  $result_user = $sql_user->get_result();
  $users = $result_user->fetch_assoc();

  // Truy vấn lấy total balance
  $sql_wallets = $conn->prepare("SELECT SUM(balance) FROM wallets WHERE user_id = ?");
  $sql_wallets->bind_param("i", $user_id);
  $sql_wallets->execute();
  $result_wallets = $sql_wallets->get_result();
  $wallets = $result_wallets->fetch_all(MYSQLI_ASSOC);
  $total_balance = $wallets[0]['SUM(balance)'];

    // Truy vấn lấy chi phí
  $sql_type = $conn->prepare("SELECT type,date,amount FROM transactions WHERE user_id = ?");
  $sql_type->bind_param("i", $user_id);
  $sql_type->execute();
  $result_type = $sql_type->get_result();
  $types = $result_type->fetch_all(MYSQLI_ASSOC);

  // Truy vấn lấy tên ví
  $sql_main_wallet = $conn->prepare("SELECT name FROM wallets WHERE user_id = ?");
  $sql_main_wallet->bind_param("i", $user_id);
  $sql_main_wallet->execute();
  $result_main_wallet = $sql_main_wallet->get_result();
  $main_wallets = $result_main_wallet->fetch_all(MYSQLI_ASSOC); // Lấy tất cả ví

  // Lấy danh mục chi tiêu lớn nhất
  $sql_biggest_category = $conn->prepare("
    SELECT c.name, SUM(t.amount) AS total 
    FROM transactions t 
    JOIN categories c ON c.id = t.category_id 
    WHERE t.user_id = ? AND t.type = 'expense' AND MONTH(t.date) = ? AND YEAR(t.date) = ? 
    GROUP BY c.name 
    ORDER BY total DESC 
    LIMIT 1
  ");
  $sql_biggest_category->bind_param("iii", $user_id, $current_month, $current_year);
  $sql_biggest_category->execute();
  $result_biggest_category = $sql_biggest_category->get_result();
  $biggest_category = $result_biggest_category->fetch_assoc();
  $biggest_category_name = $biggest_category['name'] ?? 'Không có dữ liệu';
  $biggest_category_amount = $biggest_category['total'] ?? 0;

  $current_year = date('Y');
  $current_month = date('m');

// Lấy thời gian của tháng trước (có thể là tháng 12 năm trước)
  $previous_month_time = strtotime('-1 month');
  $previous_month = date('m', $previous_month_time);
  $previous_year = date('Y', $previous_month_time);

// Hàm tính tổng theo type, month, year
  function get_total_by_type($conn, $user_id, $type, $month, $year) {
      $sql = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = ? AND MONTH(date) = ? AND YEAR(date) = ?");
      $sql->bind_param("ssii", $user_id, $type, $month, $year);
      $sql->execute();
      $result = $sql->get_result();
      return $result->fetch_assoc()['total'] ?? 0;
  }

// Lấy tổng chi tiêu và thu nhập cho 2 tháng
  $current_expense = get_total_by_type($conn, $user_id, 'expense', $current_month, $current_year);
  $previous_expense = get_total_by_type($conn, $user_id, 'expense', $previous_month, $previous_year);

  $current_income = get_total_by_type($conn, $user_id, 'income', $current_month, $current_year);
  $previous_income = get_total_by_type($conn, $user_id, 'income', $previous_month, $previous_year);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hồ sơ cá nhân</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
  <div class="max-w-5xl mx-auto p-6 bg-white shadow-2xl rounded-2xl">
  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Hồ sơ cá nhân</h1>
    <div class="space-x-2">
      <a href="edit_profile.php" class="text-sm text-white bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg shadow">
        ✏️ Chỉnh sửa
      </a>
      <a href="logout.php" class="text-sm text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg shadow">
        🚪 Đăng xuất
      </a>
    </div>
  </div>

  <!-- Thông tin người dùng -->
  <div class="flex flex-col sm:flex-row items-center gap-6">
    <!-- Avatar -->
  <div class="w-32 h-32 rounded-full ring-4 ring-blue-300 shadow-md flex items-center justify-center rounded-full font-bold ">
      <?= strtoupper(substr($users['username'], 0, 1)) ?>
  </div>

    <!-- Info -->
    <div class="flex-1">
      <h2 class="text-2xl font-semibold text-gray-800">Nguyễn Văn Tài</h2>
      <p class="text-gray-600 mt-1">Email: <span class="text-blue-600"><?php echo $users['email'] ?></span></p>
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
    <!-- Số dư -->
    <div class="bg-blue-50 p-6 rounded-xl shadow">
      <h3 class="text-gray-700 text-lg font-medium">💰 Số dư hiện tại</h3>
      <p class="text-3xl font-bold text-blue-600 mt-2"><?= number_format($total_balance, 0) ?> đ</p>
    </div>

    <!-- Mục tiêu -->
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
          $wallet_names = array_map(function($w) {
                return $w['name'];
            }, $main_wallets);

            $wallet_string = implode(', ', $wallet_names);
        ?>
      <li>• Ví đang sử dụng: <span class="font-semibold"><?= htmlspecialchars($wallet_string) ?></span></li>
      <li>• Danh mục chi tiêu lớn nhất: <span class="text-indigo-600 font-medium"><?= htmlspecialchars($biggest_category_name) ?> - <?= number_format($biggest_category_amount, 0) ?> đ</span></li>
    </ul>
  </div>
</div>
</body>
</html>
