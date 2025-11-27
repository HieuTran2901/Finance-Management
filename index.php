<?php
    include './Func/Get_Session.php';
    include "./pages/Sidebar/Sidebar.php";
    require_once './Func/Notification.php';

    $sessionData = Get_Session('./module/config.php', './pages/LandingPage/landingpage.php');
    // Lấy dữ liệu người dùng và kết nối từ session
    $conn = $sessionData['conn'];
    $users = $sessionData['user'];
    $user_id = $sessionData['user_id'];

    $stmt = $conn->prepare("
    SELECT 
        Tags.name,
        Tags.limit_amount,
        SUM(CASE 
            WHEN Transactions.type = 'expense' THEN Transactions.amount 
            ELSE 0 
        END) AS total_amount
    FROM Tags 
    LEFT JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
    LEFT JOIN Transactions ON Transaction_Tags.transaction_id = Transactions.id
    WHERE 
        Tags.user_id = ? AND
        MONTH(Transactions.date) = MONTH(CURRENT_DATE()) AND
        YEAR(Transactions.date) = YEAR(CURRENT_DATE())
    GROUP BY Tags.id, Tags.name, Tags.limit_amount
");

  // Truy vấn lấy thông tin từ transaction
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $budgets = $result->fetch_all(MYSQLI_ASSOC);

  // Truy vấn lấy total balance
  $sql_wallets = $conn->prepare("SELECT SUM(balance) FROM wallets WHERE user_id = ?");
  $sql_wallets->bind_param("i", $user_id);
  $sql_wallets->execute();
  $result_wallets = $sql_wallets->get_result();
  $wallets = $result_wallets->fetch_all(MYSQLI_ASSOC);

  // Truy vấn lấy chi phí
  $sql_type = $conn->prepare("SELECT type,date,amount FROM transactions WHERE user_id = ?");
  $sql_type->bind_param("i", $user_id);
  $sql_type->execute();
  $result_type = $sql_type->get_result();
  $types = $result_type->fetch_all(MYSQLI_ASSOC);

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


// Tính phần trăm thay đổi
  function calculate_change_percent($current, $previous) {
      if ($previous == 0) return 0;
      return (($current - $previous) / $previous) * 100;
  }

  $expense_change = calculate_change_percent($current_expense, $previous_expense);
  $income_change = calculate_change_percent($current_income, $previous_income);

// Tính tổng chi tiêu của tag

  $total_monthly_expense = 0;
  foreach ($budgets as $budget) {
      $total_monthly_expense += (float)$budget['total_amount'];
  }

  // Lấy tên user
  $sql_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
  $sql_user->bind_param("i",$user_id);
  $sql_user->execute();
  $result_user = $sql_user->get_result();
  $users = $result_user->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="./css/fadein.css">

</head>
<!-- Modal -->
<div id="comingSoonModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center relative animate-fade-in">
    <h2 class="text-2xl font-semibold text-indigo-700 mb-3">Thông báo</h2>
    <p class="text-gray-700 mb-6">Tính năng này đang được phát triển. Vui lòng quay lại sau!</p>
    <button id="closeModal" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition">Đóng</button>
  </div>
</div>
<body class="bg-gray-100 font-sans">
  <div class="flex min-h-screen pl-64">
<aside class="fixed top-0 left-0 w-64 h-screen bg-gradient-to-b from-white via-gray-50 to-gray-100 shadow-lg flex flex-col justify-between z-10">
  <!-- Phần trên cùng -->
  <div class="p-6">
    <!-- Logo -->
    <div class="flex items-center gap-2 mb-8">
      <img src="https://img.icons8.com/ios/50/wallet--v1.png" class="w-7 h-7" alt="Logo" />
      <span class="text-xl font-bold text-gray-800">FinManager</span>
    </div>

    <!-- User -->
    <div class="flex items-center gap-3 mb-8">
      <div class="w-10 h-10 bg-green-500 text-white flex items-center justify-center rounded-full font-bold text-sm">
        <?= strtoupper(substr($users['username'], 0, 1)) ?>
      </div>
      <div class="leading-4">
        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($users['username']) ?></p>
        <p class="text-gray-500 text-sm">Tài khoản cá nhân</p>
      </div>
    </div>

    <!-- Danh sách menu -->
     <?php
      $currentPage = $_SERVER['PHP_SELF']; // Lấy đường dẫn file hiện tại
      renderSidebar($users, $currentPage,"./pages","./index.php","./dangkydangnhap/logout.php");
      ?>
    <!-- <nav class="space-y-2">
      <a href="#" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 bg-indigo-50 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-house text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Dashboard</span>
      </a>
      <a href="./pages/Wallet/Wallet.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-wallet text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Wallet</span>
      </a>
      <a href="./pages/Transction/Transaction.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-arrow-right-arrow-left text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Transaction</span>
      </a>
      <a href="./pages/Group/group.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-users text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Group</span>
      </a>
      <a href="#" class="js-coming-soon flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-chart-pie text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Report</span>
      </a>
      <a href="./pages/Goals/Goals.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-bullseye text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Goals</span>
      </a>
    </nav> -->
  </div>

  <!-- Đăng xuất -->
  <div class="p-6 border-t border-gray-200">
    <a href="./dangkydangnhap/logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-medium transition">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
    </a>
  </div>
</aside>


    <!-- Chatbox -->
  <!-- Nút mở chatbox -->
  <button id="toggleBtn" class="fixed bottom-4 right-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-xl z-50 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 animate-pulse-custom">
    <!-- Icon Chat Bubble từ Heroicons (ví dụ) -->
    <img src="./img/chat-box.png" class="w-6"/>
  </button>

  <!-- Chatbox -->
  <div id="chatBox" class="fixed bottom-20 right-4 w-80 z-50 transform scale-0 opacity-0 transition-all duration-300 origin-bottom-right pointer-events-none">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col h-96">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white p-3 font-semibold flex justify-between items-center shadow-md">
          <span class="text-lg flex items-center">
              <img src="./img/chatbot.png" class="w-6 mr-2" />
              Chat với AI
          </span>
          <button id="closeBtn" class="text-white hover:text-gray-200 text-2xl leading-none focus:outline-none transition-transform duration-200 transform hover:rotate-90">&times;</button>
        </div>

        <!-- Messages Container -->
        <div id="chatMessages" class="flex-1 p-3 space-y-3 overflow-y-auto bg-gray-50 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
            <!-- Tin nhắn sẽ hiển thị ở đây (Ví dụ mẫu) -->
        </div>

        <!-- Input Area -->
<form id="chatForm" class="p-3 border-t border-gray-200 bg-white">
  <div class="flex items-center gap-2">
    <!-- Input + nút ảnh nằm cùng trong một khung -->
    <div class="flex items-center flex-1 border border-gray-300 rounded-full px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500 transition-all duration-200">
      <textarea
        id="chatInput"
        rows="1"
        placeholder="Nhập tin nhắn của bạn..."
        class="flex-1 outline-none border-none bg-transparent text-sm resize-none overflow-y-auto max-h-10"
        autocomplete="off"></textarea>

      <!-- Biểu tượng ảnh -->
      <label for="chatImage" class="ml-2 cursor-pointer text-indigo-600 hover:text-indigo-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7v10a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M16 11l-4 4m0 0l-4-4m4 4V5" />
        </svg>
      </label>
      <input id="chatImage" type="file" accept="image/*" class="hidden" />
      <!-- Sau <input id="chatImage" ... /> -->
      <div id="imagePreview" class="ml-2"></div>
    </div>

    <!-- Nút gửi tách biệt -->
    <button
      type="submit"
      class="bg-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-full shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path
          d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l4.453-1.483a1 1 0 00.67-.341l6.197-7.442a1 1 0 00-.075-1.54l-3.04-2.135z" />
        <path
          d="M14.004 5.955L9.694 11.23a.999.999 0 00-.285.51L8.09 15.54a1 1 0 01-1.071.05L4.0 14.15l.487-1.462A.999.999 0 004.28 12.18l6.197-7.442a1 1 0 011.374-1.09l4.453 1.484a1 1 0 01-.285.51z" />
      </svg>
    </button>
  </div>
</form>


    </div>
</div>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <!-- Greeting Section -->
<div class="flex items-center justify-between bg-gradient-to-r from-indigo-50 via-white to-purple-50 p-6 rounded-xl shadow mb-6">
  <div class="flex items-center gap-4">
    <!-- Avatar -->
    <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-2xl font-bold shadow-inner">
      <?= strtoupper(substr($users['username'], 0, 1)) ?>
    </div>

    <!-- Text -->
    <div>
      <h1 class="text-2xl font-semibold text-gray-800">Xin chào, <?= htmlspecialchars($users['username']) ?> <span class="animate-waving-hand">👋</span></h1>
      <p class="text-sm text-gray-500">Chúc bạn một ngày tài chính hiệu quả!</p>
    </div>
  </div>

  <!-- Optional action button -->
  <a href="./pages/Profile/profile.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
    <i class="fa-solid fa-user"></i> Hồ sơ cá nhân
  </a>
</div>

      <!-- Container có chiều cao cố định -->
      <div class="flex gap-4 h-[480px]"> <!-- Điều chỉnh h-[500px] nếu cần -->
        <!-- Left Column (50%) -->
        <div class="w-1/2 flex flex-col gap-4 overflow-visible">
          <!-- Total expense + Total income -->
           <?php
            $total_expense = 0;
            $total_income = 0;

            foreach ($types as $row) {
                if ($row['type'] === 'expense') {
                    $total_expense += (float)$row['amount'];
                } elseif ($row['type'] === 'income') {
                    $total_income += (float)$row['amount'];
                }
            } 
           ?>
          <!-- Total expense + Total income -->
          <div class="grid grid-cols-2 gap-4">
            <!-- EXPENSES -->
            <div class="bg-red-50 p-4 rounded-lg shadow">
              <h2 class="text-xl font-semibold text-red-600">Total expenses</h2>
              <div class="flex mt-5 justify-between">
                <p class="text-xl font-bold"><?= number_format($current_expense, 0) ?> đ</p>
                <div class="text-center">
                  <p>
                    <?= number_format(abs($expense_change), 1) ?>% 
                    <i class="fa-solid <?= $expense_change >= 0 ? 'fa-arrow-trend-up text-red-400' : 'fa-arrow-trend-down text-green-400' ?> text-sm"></i>
                  </p>
                  <p class="text-sm text-gray-400"> vs last month</p>
                </div>
              </div>
            </div>

            <!-- INCOME -->
            <div class="bg-blue-50 p-4 rounded-lg shadow">
              <h2 class="text-xl font-semibold text-blue-600">Total income</h2>
              <div class="flex mt-5 justify-between">
                <p class="text-xl font-bold"><?= number_format($current_income, 0) ?> đ</p>
                <div class="text-center">
                  <p>
                    <?= number_format(abs($income_change), 1) ?>% 
                    <i class="fa-solid <?= $income_change >= 0 ? 'fa-arrow-trend-up text-blue-400' : 'fa-arrow-trend-down text-red-400' ?> text-sm"></i>
                  </p>
                  <p class="text-sm text-gray-400"> vs last month</p>
                </div>
              </div>
            </div>
          </div>


          <!-- Monthly Expenses -->
          <div class="bg-white h-[420px] rounded-lg p-4 ">
            <div class="flex justify-between">
              <h2 class="text-lg font-semibold text-gray-800 mb-4 text-left">Monthly Expenses</h2>
              <div class="flex gap-4 border-2 border-gray-300 h-6 items-center p-4 rounded-lg">
                <i class="fa-solid fa-up-right-and-down-left-from-center text-xs"></i>
                <i class="fa-solid fa-ellipsis"></i>
              </div>
            </div>
            <div class="flex justify-center items-center">
              <div class="relative w-64 h-64">
                <!-- Donut chart canvas -->
                <canvas id="donutChart"
                  data-labels='<?= json_encode(array_column($budgets, 'name')) ?>'
                  data-values='<?= json_encode(array_map(fn($b) => (float)$b['total_amount'], $budgets)) ?>'>
              </canvas>

                <!-- Centered text over the chart -->
                <div class="flex items-center justify-center text-center w-64 h-64 -mt-64">
                  <div>
                    <p class="text-2xl font-semibold"><?= number_format($total_monthly_expense, 0) ?> đ</p>
                    <p class="text-gray-600 text-sm">Total</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column (50%) với cuộn -->
        <div class="w-1/2 bg-white p-6 rounded-lg shadow h-full overflow-y-auto">
          <div class="flex justify-between">
              <h2 class="text-lg font-semibold mb-4">Budgets</h2>
              <div class="flex gap-4 border-2 border-gray-300 h-6 items-center p-4 rounded-lg">
                <i class="fa-solid fa-up-right-and-down-left-from-center text-xs"></i>
                <i class="fa-solid fa-ellipsis"></i>
              </div>
            </div>
          <div class="space-y-4 text-sm">
            <!-- Các mục ngân sách -->
              <?php foreach ($budgets as $budget): ?>
              <div>
                <div>
                  <?= htmlspecialchars($budget['name']) ?>
                  <div class="bg-gray-200 h-3 rounded-lg mt-1">
                    <?php
                      $percent = ($budget['limit_amount'] > 0)
                          ? min(100, ($budget['total_amount'] / $budget['limit_amount']) * 100)
                          : 0;
  
                      // Chọn màu theo phần trăm
                      if ($percent < 50) {
                        $colorClass = 'bg-green-400';
                      } elseif ($percent < 80) {
                        $colorClass = 'bg-yellow-400';
                      } else {
                        $colorClass = 'bg-red-400';
                      }
                    ?>
                    <div class="<?= $colorClass ?> h-3 rounded-lg" style="width: <?= $percent ?>%;"></div>
                  </div>
                   <span class=" right-2 top-1/2 -translate-y-1/2 text-xs text-gray-700 font-medium">
                        <?= round($percent) ?>%
                      </span>
                  <p class="text-right text-xs text-gray-500">
                    <?= htmlspecialchars($budget['total_amount']) ?> / <?= htmlspecialchars($budget['limit_amount']) ?>
                  </p>
                </div>
                <p class="mb-7 text-gray-500 text-xs">Remaining</p>
              </div>
            <?php endforeach ?>
          </div>      
        </div>         
      </div>
        <div class="w-full bg-white p-6 rounded-lg shadow mt-5">
          <canvas id="budgetChart" class="w-full h-64"  
                data-labels='<?= json_encode(array_column($budgets, 'expense')) ?>'
                data-labels='<?= json_encode(array_column($budgets, 'name')) ?>' >
              </canvas>
        </div>
    </main>
  </div>
</body>
<?php
  Notification_Notyf('login', 'Đăng nhập thành công! Xin chào "'.$users['username'].'"', 'Tài khoản hoặc mật khẩu không đúng!');
?>

<?php
$months = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"
];
$budget_expense = []; // expense
$budget_income = []; // income

foreach ($months as $month) {
    $budget_expense[$month] = 0;
    $budget_income[$month] = 0;
}

foreach ($types as $t) {
  // Chuyển từ date sang tên tháng tiếng Anh
    $month = date('F', strtotime($t['date']));

    if (!in_array($month, $months)) continue;

    if ($t['type'] === 'expense') {
        $budget_expense[$month] += $t['amount'];
    } elseif ($t['type'] === 'income') {
        $budget_income[$month] += $t['amount'];
    }
}

?>
<script>
  const ctx = document.getElementById('budgetChart').getContext('2d');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [
      {
        label: 'Total expense',
        data: <?= json_encode(array_values($budget_expense)) ?>,
        borderColor: '#f87171',
        backgroundColor: '#f87171',
        tension: 0.4,
        fill: false
      },
      {
        label: 'Total income',
        data: <?= json_encode(array_values($budget_income)) ?>,
        borderColor: '#60a5fa',
        backgroundColor: '#60a5fa',
        tension: 0.4,
        fill: false
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: {
        display: true,
        text: 'Budget vs Planned Budget by Month'
      }
    },
    scales: {
      y: {
        beginAtZero: true
      }
    }
  }
});

</script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.3/dist/tesseract.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script> <!-- AOS JS -->
    <script>
        AOS.init({
            once: true, // Chỉ animate khi cuộn qua lần đầu tiên
            mirror: false // Không lặp lại animation khi cuộn lên
        });

        // Optional: Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
        <script src="https://kit.fontawesome.com/yourkit.js" crossorigin="anonymous"></script>

<script src="./js/Chart.js"></script>
<script src="./js/Chatboxgpt.js" ></script>
<script src="./js/Modal.js"></script>

</html>


