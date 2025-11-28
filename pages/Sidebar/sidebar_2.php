<?php
// Lưu ý: không gọi session_start() ở đây
// Giả sử $username được định nghĩa trước khi include Sidebar.php
?>

<aside class="fixed top-0 left-0 w-64 h-screen bg-gradient-to-b from-white via-gray-50 to-gray-100 shadow-lg flex flex-col justify-between z-10">
  <!-- Phần trên -->
  <div class="p-6">
    <!-- Logo -->
    <div class="flex items-center gap-2 mb-8">
      <img src="https://img.icons8.com/ios/50/wallet--v1.png" class="w-7 h-7" alt="Logo" />
      <span class="text-xl font-bold text-gray-800">FinManager</span>
    </div>

    <!-- User -->
    <div class="flex items-center gap-3 mb-8">
      <div class="w-10 h-10 bg-green-500 text-white flex items-center justify-center rounded-full font-bold text-sm">
        <?= strtoupper(substr($username ?? 'U', 0, 1)) ?>
      </div>
      <div class="leading-4">
        <p class="text-gray-800 font-semibold">
          <?= htmlspecialchars(ucwords(strtolower($username ?? 'User'))) ?>

        </p>
        <p class="text-gray-500 text-sm">Tài khoản cá nhân</p>
      </div>
    </div>

    <!-- Menu -->
    <nav class="space-y-2">
      <a href="../../index.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
        <i class="fa-solid fa-house text-indigo-500"></i>
        <span class="font-medium">Dashboard</span>
      </a>

      <a href="#" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 bg-indigo-50 text-indigo-600 transition">
        <i class="fa-solid fa-wallet text-indigo-500"></i>
        <span class="font-medium">Wallet</span>
      </a>

      <a href="../Transction/Transaction.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
        <i class="fa-solid fa-arrow-right-arrow-left text-indigo-500"></i>
        <span class="font-medium">Transaction</span>
      </a>

      <a href="./pages/Group/group.php" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition group">
        <i class="fa-solid fa-users text-indigo-500 group-hover:text-indigo-600"></i>
        <span class="font-medium">Group</span>
      </a>

      <a href="#" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
        <i class="fa-solid fa-chart-pie text-indigo-500"></i>
        <span class="font-medium">Report</span>
      </a>

      <a href="#" class="flex items-center gap-3 p-3 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
        <i class="fa-solid fa-bullseye text-indigo-500"></i>
        <span class="font-medium">Goals</span>
      </a>
    </nav>
  </div>

  <!-- Đăng xuất -->
  <div class="p-6 border-t border-gray-200">
    <a href="../../dangkydangnhap/logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-medium transition">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
    </a>
  </div>
</aside>
