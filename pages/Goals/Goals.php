<?php
  require_once '../../Func/Get_Session.php';
  require_once '../../Api/Apiconfig.php';
  include '../Sidebar/Sidebar.php';
  include '../../Func/SQL_Cmd.php';
  include './Component.php';
  include '../../Func/Notification.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mục tiêu tài chính | FinManager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../css/Goals.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
  <!-- Sidebar -->
  <?php
    $sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
    // Lấy dữ liệu người dùng và kết nối từ session
    $users = $sessionData['user'];
    $conn = $sessionData['conn'];
    $users_id = $sessionData['user_id'];

    $currentPage = $_SERVER['PHP_SELF']; // Lấy đường dẫn file hiện tại
    renderSidebar($users, $currentPage, "../../pages","../../index.php","../../dangkydangnhap/logout.php");


    // Lấy số dư ví người dùng
    $sql = "SELECT id, name, type, balance, currency, created_at,edit_at FROM Wallets WHERE user_id = ?";
    $wallets = SQL_Select($conn, $sql, "i", [$users_id]);
    
  ?>
  <!-- Header -->
   <div class="pl-64 min-h-screen ">
    <header class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-indigo-600">🎯 Mục tiêu tài chính</h1>
      <div>
         <button id="aiAnalyzeBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
          🤖 AI Phân tích
        </button>
        <button id="addGoalBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
          + Thêm mục tiêu
        </button>

      </div>

    </header>
    <!-- Main Content -->
    <main class="p-6 ">
      <!-- Danh sách mục tiêu -->
      <section class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card mục tiêu -->
        <?php
          $goals = SQL_Select($conn, "SELECT * FROM goals WHERE user_id = ?", "i", [$users_id]);
          foreach ($goals as $goal) {
            $color = getRandomCardColor(); // Lấy màu ngẫu nhiên cho thẻ

            $percentage = ($goal['saved_amount'] / $goal['target_amount']) * 100; // Tính phần trăm hoàn thành
            // Xác định màu theo mốc phần trăm
            if ($percentage < 30) {
              $progressClass = 'from-red-500 to-red-700';       // Cảnh báo - chưa đạt
            } elseif ($percentage < 70) {
              $progressClass = 'from-yellow-400 to-yellow-600'; // Trung bình
            } elseif ($percentage < 100) {
              $progressClass = 'from-green-400 to-green-600';   // Tốt
            } else {
              $progressClass = 'from-blue-500 to-blue-700';     // Hoàn thành
            }

            $days_left = (strtotime($goal['end_date']) - time()) / (60 * 60 * 24); // Tính số ngày còn lại

            // $message = getAIMotivation($goal['goal_name'], round($percentage), ceil($days_left), $apiKey);

            // 🟩 Xác định trạng thái hoàn thành
            if ($percentage >= 100) {
            $status = '
              <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium shadow-sm border border-green-200">
                ✅ <span class="ml-1">Đã hoàn thành</span>
              </div>';
              $animationClass = 'animate-pulse-once';
              $is_finished = "Hoàn thành";
              $disabled = "disabled";
              $btn_color = "bg-green-600 cursor-not-allowed";
          } elseif ($days_left <= 0 && $percentage < 100) {
            $status = '
              <div class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium shadow-sm border border-red-200">
                ❌ <span class="ml-1">Thất bại (quá hạn)</span>
              </div>';
              $is_finished = "Thất bại";
              $disabled = 'disabled';
              $animationClass = '';
              $btn_color = "bg-red-500 cursor-not-allowed";
          } else {
            $status = '
              <div class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium shadow-sm border border-yellow-200">
                ⏳ <span class="ml-1">Chưa hoàn thành</span>
              </div>';
                $animationClass = '';
                $is_finished = "Cập nhật";
                $disabled = '';
                $btn_color = "bg-indigo-500 hover:bg-indigo-600";
          }

            $openEditModal = "openEditModal(" . $goal['id'] . ", '" . addslashes($goal['goal_name']) . "', " . $goal['saved_amount'] . ", " . $goal['target_amount'] . ", '" . $goal['end_date'] . "')"; // Tạo chuỗi lệnh gọi hàm JavaScript để mở modal chỉnh sửa
            $delete_btn = "if(confirm('Bạn có chắc chắn muốn xóa mục tiêu này?')) window.location.href='delete_saving.php?id=".$goal['id']."'";

            echo ' 
            <div class="bg-gradient-to-br '.$color['from'].' '.$color['to'].' shadow-md rounded-2xl p-6 border border-indigo-100 hover:shadow-xl hover:-translate-y-1 transform transition-all duration-300 ' . $animationClass . '">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800"> ' . htmlspecialchars($goal['goal_name']) . '</h2>
                <!-- 🟦 Trạng thái -->
              <div class=" text-center">
                ' . $status . '
              </div>
              </div>
              <p class="text-gray-600 mb-2 text-sm">Thời hạn: <span class="font-medium text-gray-700">' . htmlspecialchars($goal['end_date']) . '</span></p>
              <p class="text-gray-800 font-medium mb-3">
                Đã tiết kiệm: 
                <span class="text-green-600 font-semibold">' . number_format($goal['saved_amount'], 0, ',', '.') . 'đ</span> / 
                <span class="text-gray-600">' . number_format($goal['target_amount'], 0, ',', '.') . 'đ</span>
              </p>
              <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden shadow-inner">
                <div class="h-3 bg-gradient-to-r '.$progressClass.' rounded-full progress-animated" 
                    style="width: ' . min(htmlspecialchars($percentage), 100) . '%;"></div>
              </div>

              <div class="flex justify-between text-sm text-gray-500 mt-3">
                <span>' . round($percentage, 2) . '% hoàn thành</span>
                <span>' . ceil($days_left) . ' ngày còn lại</span> 
              </div>

              <p class="mt-3 text-sm italic text-gray-700 ai-message"
                data-goal-name="'.htmlspecialchars($goal['goal_name']).'" 
                data-percentage="'.round($percentage).'" 
                data-days-left="'.ceil($days_left).'">
                ⏳ Chờ 1 xíu nhé <3...
              </p>
              
              <div class="flex justify-end mt-5 space-x-3">
                <button '.$disabled.' onclick="'.$openEditModal.'" class="text-sm '.$btn_color.' text-white px-3 py-1.5 rounded-lg transition">'.$is_finished.'</button>
                <button  
                onclick="'.$delete_btn.'"
                class="text-sm bg-red-100 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-200 transition">
                  Xóa
                </button>
              </div>
            </div>
            ';
          }
        ?>
      </section>
    </main>
  </div>
  <!-- Modal thêm mục tiêu -->
  <?php AddGoalModal(); ?>

  <!-- Modal chỉnh sửa mục tiêu -->
   <?php 
    EditModal($wallets, $users_id, $conn);
   ?>

  <!-- Modal AI Phân tích -->
  <?php 
    AI_Analyze_Modal();
   ?>

  <!-- Thông báo -->
  <?php Notification_Notyf('update', 'Cập nhật mục tiêu thành công!', 'Cập nhật mục tiêu thất bại!'); ?>
  <?php Notification_Notyf('delete', 'Xóa mục tiêu thành công!', 'Xóa mục tiêu thất bại!'); ?>
  

  <!-- Script -->
  <script>const USER_ID = <?= json_encode($users_id) ?>;</script>
  <script src="../../js/GoalsComponent.js"></script>
</body>
</html>
