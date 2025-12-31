<?php
include './Func/Get_Session.php';
include "./pages/Sidebar/Sidebar.php";
require_once './Func/Notification.php';
include './Func/SQL_Cmd.php';

$sessionData = Get_Session('./module/config.php', './pages/LandingPage/landingpage.php');
// Lấy dữ liệu người dùng và kết nối từ session
$conn = $sessionData['conn'];
$users = $sessionData['user'];
$user_id = $sessionData['user_id'];

$stmt = $conn->prepare("
    SELECT 
        Tags.name,
        Tags.icon,
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
function get_total_by_type($conn, $user_id, $type, $month, $year)
{
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
function calculate_change_percent($current, $previous)
{
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
<?php include './pages/comming_soon_modal.php'; ?>

<body class="bg-gray-100 font-sans">
  <div class="flex min-h-screen pl-64">
    <!-- Sidebar -->
    <!-- Danh sách menu -->
    <?php
    $currentPage = $_SERVER['PHP_SELF']; // Lấy đường dẫn file hiện tại
    renderSidebar($users, $currentPage, "./pages", "./index.php", "./pages/logout.php");
    ?>

    <!-- Chatbox -->
    <!-- Nút mở chatbox -->
    <button id="toggleBtn" class="fixed bottom-4 right-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-xl z-50 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 animate-pulse-custom">
      <!-- Icon Chat Bubble từ Heroicons (ví dụ) -->
      <img src="./img/chat-box.png" class="w-6" />
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
              class="bg-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-full shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
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
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between 
            gap-4 bg-gradient-to-br from-indigo-50 via-white to-purple-50 
            p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">

        <!-- Left -->
        <div class="flex items-center gap-4">
          <!-- Avatar -->
          <div class="relative">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 
                  flex items-center justify-center text-white text-xl font-semibold
                  shadow-md ring-4 ring-indigo-100">
              <?= strtoupper(substr($users['username'], 0, 1)) ?>
            </div>
            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 
                   border-2 border-white rounded-full"></span>
          </div>

          <!-- Text -->
          <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 flex items-center gap-2">
              Xin chào,
              <span class="text-indigo-600">
                <?= htmlspecialchars($users['username']) ?>
              </span>
              <span class="animate-waving-hand">👋</span>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
              Chúc bạn một ngày quản lý tài chính hiệu quả
            </p>
          </div>
        </div>

        <!-- Action -->
        <a href="./pages/Profile/profile.php"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
            text-sm font-medium text-indigo-600 bg-indigo-50
            hover:bg-indigo-100 hover:text-indigo-700
            transition-all duration-200 shadow-sm">
          <i class="fa-solid fa-user text-xs"></i>
          Hồ sơ cá nhân
        </a>
      </div>


      <!-- Container có chiều cao cố định -->
      <div class="flex gap-4 h-[500px]"> <!-- Điều chỉnh h-[500px] nếu cần -->
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
          <div class="grid grid-cols-2 gap-5">

            <!-- EXPENSES -->
            <div class="relative p-5 rounded-2xl 
              bg-gradient-to-br from-red-50 via-white to-white
              border border-red-100
              shadow-sm hover:shadow-md transition">

              <!-- Icon nền -->
              <div class="absolute top-4 right-4 w-10 h-10 
                rounded-full bg-red-100 
                flex items-center justify-center text-red-500">
                <i class="fa-solid fa-wallet"></i>
              </div>

              <h2 class="text-sm font-medium text-red-500 uppercase tracking-wide">
                Total Expenses
              </h2>

              <p class="text-3xl font-bold text-slate-800 mt-3">
                <?= number_format($current_expense, 0) ?> đ
              </p>

              <div class="flex items-center gap-2 mt-3 text-sm">
                <span class="font-medium">
                  <?= number_format(abs($expense_change), 1) ?>%
                </span>

                <i class="fa-solid 
                <?= $expense_change >= 0
                  ? 'fa-arrow-trend-up text-red-500'
                  : 'fa-arrow-trend-down text-green-500' ?>">
                </i>

                <span class="text-slate-400 text-xs">
                  vs last month
                </span>
              </div>
            </div>

            <!-- INCOME -->
            <div class="relative p-5 rounded-2xl 
              bg-gradient-to-br from-blue-50 via-white to-white
              border border-blue-100
              shadow-sm hover:shadow-md transition">

              <!-- Icon nền -->
              <div class="absolute top-4 right-4 w-10 h-10 
                rounded-full bg-blue-100 
                flex items-center justify-center text-blue-500">
                <i class="fa-solid fa-coins"></i>
              </div>

              <h2 class="text-sm font-medium text-blue-500 uppercase tracking-wide">
                Total Income
              </h2>

              <p class="text-3xl font-bold text-slate-800 mt-3">
                <?= number_format($current_income, 0) ?> đ
              </p>

              <div class="flex items-center gap-2 mt-3 text-sm">
                <span class="font-medium">
                  <?= number_format(abs($income_change), 1) ?>%
                </span>

                <i class="fa-solid 
                  <?= $income_change >= 0
                    ? 'fa-arrow-trend-up text-blue-500'
                    : 'fa-arrow-trend-down text-red-500' ?>">
                </i>

                <span class="text-slate-400 text-xs">
                  vs last month
                </span>
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

        <!-- Right Column - Budgets -->
        <div class="w-1/2
            bg-gradient-to-br from-violet-50 via-white to-fuchsia-50
            p-5 rounded-2xl
            border border-violet-100
            shadow-[0_10px_30px_rgba(139,92,246,0.12)]
            h-full overflow-y-auto">

          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-violet-700 tracking-wide ">
              Budgets
            </h2>

            <div class="flex items-center gap-2">
              <button
                class="w-8 h-8 flex items-center justify-center rounded-lg
                border border-violet-200 text-violet-500
                hover:bg-violet-100 hover:text-violet-700
                transition">
                <i class="fa-solid fa-up-right-and-down-left-from-center text-xs"></i>
              </button>

              <button
                class="w-8 h-8 flex items-center justify-center rounded-lg
                border border-gray-200 text-gray-500
                hover:bg-gray-100 hover:text-gray-700
                transition">
                <i class="fa-solid fa-ellipsis text-sm"></i>
              </button>
            </div>
          </div>

          <!-- Budget List -->
          <div class="space-y-5 text-sm ">

            <?php foreach ($budgets as $budget): ?>
              <?php
              // ===== CALCULATION =====
              $percent = ($budget['limit_amount'] > 0)
                ? min(100, ($budget['total_amount'] / $budget['limit_amount']) * 100)
                : 0;

              $remaining = max(0, $budget['limit_amount'] - $budget['total_amount']);

              // ===== COLOR + STATUS =====
              if ($percent < 50) {
                $colorClass  = 'bg-gradient-to-r from-green-300 to-green-400';
                $status      = 'Safe';
                $statusClass = 'bg-green-100 text-green-700';
              } elseif ($percent < 80) {
                $colorClass  = 'bg-gradient-to-r from-yellow-300 to-yellow-400';
                $status      = 'Warning';
                $statusClass = 'bg-yellow-100 text-yellow-700';
              } else {
                $colorClass  = 'bg-gradient-to-r from-red-300 to-red-400';
                $status      = 'Over limit';
                $statusClass = 'bg-red-100 text-red-700';
              }
              ?>

              <!-- Budget Card -->
              <div class="p-4 rounded-xl
                          border <?= $percent >= 80 ? 'border-red-300 bg-red-50/50' : 'border-violet-200/70 bg-white/70' ?>
                          backdrop-blur-sm
                          shadow-[0_6px_20px_rgba(139,92,246,0.12)]
                          hover:-translate-y-0.5
                          hover:shadow-[0_10px_28px_rgba(139,92,246,0.18)]
                          transition-all duration-300">

                <!-- Header -->
                <div class="flex justify-between items-center mb-2">

                  <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center 
                                w-7 h-7 text-lg leading-none"><?php echo $budget['icon'] ?></div>
                    <h3 class="font-medium text-violet-800">
                      <?= htmlspecialchars($budget['name']) ?>
                    </h3>
                  </div>



                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-violet-600">
                      <?= round($percent) ?>%
                    </span>
                    <span class="text-[11px] px-2 py-0.5 rounded-full <?= $statusClass ?>">
                      <?= $status ?>
                    </span>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
                  <div class="<?= $colorClass ?> h-2.5 rounded-full 
                              transition-all duration-500"
                    style="width: <?= $percent ?>%;">
                  </div>
                </div>

                <!-- Amount -->
                <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                  <span>
                    Spent: <?= number_format($budget['total_amount'], 0) ?> đ
                  </span>
                  <span>
                    Limit: <?= number_format($budget['limit_amount'], 0) ?> đ
                  </span>
                </div>

                <!-- Remaining -->
                <p class="mt-1 text-xs text-gray-400">
                  Remaining: <?= number_format($remaining, 0) ?> đ
                </p>

              </div>

            <?php endforeach ?>

          </div>

        </div>

      </div>
      <div class="w-full bg-white p-6 rounded-lg shadow mt-5">
        <canvas id="budgetChart" class="w-full h-64"
          data-labels='<?= json_encode(array_column($budgets, 'expense')) ?>'
          data-labels='<?= json_encode(array_column($budgets, 'name')) ?>'>
        </canvas>
      </div>
    </main>
  </div>
</body>
<?php
Notification_Notyf('login', 'Đăng nhập thành công! Xin chào "' . $users['username'] . '"', 'Tài khoản hoặc mật khẩu không đúng!');
?>

<?php
$months = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December"
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
<!-- Biểu đồ đường -->
<script>
  const ctx = document.getElementById('budgetChart').getContext('2d');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
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
        legend: {
          position: 'top'
        },
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
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelector(this.getAttribute('href')).scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
</script>

<script>
  const USER_ID = "<?php echo $_SESSION['user_id']; ?>";
</script>

<script src="./js/Chart.js"></script>
<script src="./js/Chatboxgpt.js"></script>
<script src="./js/Modal.js"></script>

</html>