<?php
    session_start();
    require_once './module/config.php';
    $user_id = $_SESSION['user_id']; // Giả sử bạn đã lưu user_id khi đăng nhập
      if (!isset($_SESSION['user_id'])) {
        header("Location: ./pages/login.php");
      }
    $stmt = $conn->prepare("
    SELECT 
        Tags.name,
        Tags.limit_amount,
        SUM(Transactions.amount) AS total_amount
    FROM Tags 
          LEFT JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
          LEFT JOIN Transactions ON Transaction_Tags.transaction_id = Transactions.id
    WHERE 
        Tags.user_id = ?
    GROUP BY Tags.id, Tags.created_at ,Tags.edit_at
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

</head>
<body class="bg-gray-100 font-sans">
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-4">
      <div class="text-2xl font-bold flex items-center gap-2 mb-6">
        <img src="https://img.icons8.com/ios/50/wallet--v1.png" class="w-6 h-6" alt="Logo"/>
        finmanager
      </div>
      <div class="mb-6">
        <div class="flex items-center gap-2">
          <div class="bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center">M</div>
          <span class="font-medium">Mia</span>
        </div>
      </div>
      <nav class="space-y-3 text-gray-700">
        <a href="#" class="flex items-center gap-2 p-2 bg-gray-100 rounded-md font-medium">
          <span><i class="fa-solid fa-house"></i></span> Dashboard
        </a>
        <a href="#" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-map-pin"></i> Pinned
        </a>
        <a href="./pages/Wallet/Wallet.php" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-wallet"></i> Wallets
        </a>
        <a href="./pages/Transction/Transaction.php" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-regular fa-money-bill-1"></i> Transactions
        </a>
        <a href="#" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-bullseye"></i> Budgets & Goals
        </a>
        <a href="#" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-clock-rotate-left"></i> Recurring
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <h1 class="text-3xl font-semibold mb-6">Hello, Mia 👋</h1>

      <!-- Container có chiều cao cố định -->
      <div class="flex gap-4 h-[480px]"> <!-- Điều chỉnh h-[500px] nếu cần -->
        <!-- Left Column (50%) -->
        <div class="w-1/2 flex flex-col gap-4 overflow-visible">
          <!-- Total balance + Total income -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-lg shadow">
              <h2 class="text-2xl font-bold">Total balance</h2>
              <div class="flex mt-5 justify-between">
                <p class="text-lg font-bold"><?= number_format($wallets[0]["SUM(balance)"], 2) ?> đ</p>
                <div class="text-center">
                  <p >+0.8% </p>
                  <p class="text-sm text-gray-400"> vs last month</p>
                </div>
              </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
              <h2 class="text-2xl font-bold">Total income</h2>
              <div class="flex mt-5 justify-between">
                <p class="text-lg font-bold"><?= number_format($wallets[0]["SUM(balance)"], 2) ?> đ</p>
                <div class="text-center">
                  <p>+0.8% </p>
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
                <canvas id="donutChart" class="w-full h-full"></canvas>

                <!-- Centered text over the chart -->
                <div class="flex items-center justify-center text-center w-64 h-64 -mt-64">
                  <div>
                    <p class="text-2xl font-semibold">$2,500</p>
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
                  <p class="text-right text-xs text-gray-500">
                    <?= htmlspecialchars($budget['total_amount']) ?> / <?= htmlspecialchars($budget['limit_amount']) ?>
                  </p>
                </div>
                <p class="mb-7 text-gray-400">Remaining</p>
              </div>
            <?php endforeach ?>
          </div>
      </div>

      </div>
    </main>
  </div>
</body>

<script>
    const ctx = document.getElementById('donutChart').getContext('2d');
    const data = {
      labels: <?= json_encode(array_column($budgets, 'name')) ?>,
      datasets: [{
        data: <?= json_encode(array_map(fn($b) => (float)$b['total_amount'], $budgets)) ?>,
        backgroundColor: [
            '#34D399', '#60A5FA', '#FBBF24', '#F87171', '#A78BFA',
            '#F472B6', '#818CF8', '#2DD4BF', '#FB923C', '#10B981',
            '#22D3EE', '#A3E635', '#F43F5E', '#E879F9', '#8B5CF6',
            '#F59E0B', '#38BDF8', '#A1A1AA', '#737373', '#78716C'
          ],
        hoverOffset: 20,
        borderWidth: 0
      }]
    };

    const options = {
      responsive: true,
      cutout: '70%',
      layout: {
    padding: 8 // tránh tooltip bị cắt sát rìa
  },
      plugins: {
        tooltip: {
          enabled: true,
          backgroundColor: '#fff',
          borderColor: '#ccc',
          borderWidth: 1,
          titleColor: '#000',
          bodyColor: '#000',
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: $${value} (${percentage}%)`;
            }
          }
        },
        legend: { display: false }
      }
    };

    new Chart(ctx, {
      type: 'doughnut',
      data: data,
      options: options
    });
  </script>


</html>
