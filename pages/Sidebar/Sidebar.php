<?php
function renderSidebar($users, $currentPage, $baseUrl, $indexUrl, $logoutUrl) {
    // Danh sách menu
    $menus = [
        ["$indexUrl", 'fa-house', 'Dashboard'],
        ["$baseUrl/Wallet/Wallet.php", 'fa-wallet', 'Wallet'],
        ["$baseUrl/Transction/Transaction.php", 'fa-arrow-right-arrow-left', 'Transaction'],
        ["$baseUrl/Group/group.php", 'fa-users', 'Group'],
        ["#", 'fa-chart-pie', 'Report', 'js-coming-soon'],
        ["$baseUrl/Goals/Goals.php", 'fa-bullseye', 'Goals'],
    ];

    echo '
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
          <div class="w-10 h-10 bg-green-500 text-white flex items-center justify-center rounded-full font-bold text-sm">'
            . strtoupper(substr($users["username"], 0, 1)) .
          '</div>
          <div class="leading-4">
            <p class="text-gray-800 font-semibold">' . htmlspecialchars($users["username"]) . '</p>
            <p class="text-gray-500 text-sm">Tài khoản cá nhân</p>
          </div>
        </div>

        <!-- Danh sách menu -->
        <nav class="space-y-2">';
    
    // In ra các item menu
    foreach ($menus as $menu) {
        $url = $menu[0];
        $icon = $menu[1];
        $label = $menu[2];
        $extraClass = isset($menu[3]) ? $menu[3] : '';

        // Kiểm tra trang hiện tại để làm nổi bật menu
        $isActive = (strpos($currentPage, basename($url)) !== false) 
                    ? 'bg-indigo-50 text-indigo-600' 
                    : 'text-gray-700';

        echo '
          <a href="' . htmlspecialchars($url) . '" 
             class="flex items-center gap-3 p-3 rounded-lg ' . $isActive . ' hover:bg-indigo-50 hover:text-indigo-600 transition group ' . $extraClass . '">
            <i class="fa-solid ' . htmlspecialchars($icon) . ' text-indigo-500 group-hover:text-indigo-600"></i>
            <span class="font-medium">' . htmlspecialchars($label) . '</span>
          </a>';
    }

    // Kết thúc sidebar
    echo '
        </nav>
      </div>

      <!-- Đăng xuất -->
      <div class="p-6 border-t border-gray-200">
        <a href="'.$logoutUrl.'" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-medium transition">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
        </a>
      </div>
    </aside>';
}
?>
