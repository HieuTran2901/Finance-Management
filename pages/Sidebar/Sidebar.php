<?php
function renderSidebar($users, $currentPage, $baseUrl, $indexUrl, $logoutUrl) {
    $menus = [
        [$indexUrl, 'fa-house', 'Dashboard'],
        ["{$baseUrl}/Wallet/Wallet.php", 'fa-wallet', 'Wallet'],
        ["{$baseUrl}/Transction/Transaction.php", 'fa-arrow-right-arrow-left', 'Transaction'],
        ["{$baseUrl}/Group/group.php", 'fa-users', 'Group'],
        ["javascript:void(0)", 'fa-chart-pie', 'Report', 'js-coming-soon'],
        ["{$baseUrl}/Goals/Goals.php", 'fa-bullseye', 'Goals'],
    ];

    // Sidebar HTML
    echo '
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="'.$baseUrl.'../../css/sidebar.css">

    <aside class="custom-sidebar">
            <!-- Tuyết rơi -->
          <div class="snow"></div>
          <div class="bottom_snow"></div>

            <div class="sidebar-top">
            <!-- Logo -->
            <div class="sidebar-logo">            
                <img src="'.$baseUrl.'../../css/img/FinManager.png" alt="logo">
            </div>

            <!-- User -->
            <div class="sidebar-user">
                <div class="sidebar-avatar">'.strtoupper(substr($users["username"], 0, 1)).'</div>
                <div class="sidebar-user-info">
                    <p>'.htmlspecialchars($users["username"]).'</p>
                    <p>Tài khoản cá nhân</p>
                </div>
            </div>

            <!-- Menu -->
            <nav class="sidebar-menu">';
            
            foreach ($menus as $menu) {
                $url = $menu[0];
                $icon = $menu[1];
                $label = $menu[2];
                $extraClass = $menu[3] ?? '';
                $isActive = (strpos($currentPage, basename($url)) !== false) ? 'active' : '';

                echo '<a href="'.htmlspecialchars($url).'" class="'.$isActive.' '.$extraClass.'">
                        <i class="fa-solid '.htmlspecialchars($icon).'"></i>
                        <span>'.htmlspecialchars($label).'</span>
                    </a>';
            }

    echo '
            </nav>
        </div>
        <!-- Logout -->
        <div class="flex flex-col h-full">
            <!-- Nội dung sidebar ở đây -->
            <div class="flex-1">
                <!-- Menu chính, các link, logo,... -->
            </div>

            <!-- Nút Đăng xuất cố định cuối sidebar -->
            <div class="sidebar-bottom mt-auto p-1">
                <a href="<?= $logoutUrl ?>" class="sidebar-logout flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
                </a>
            </div>
        </div>


    </aside>';
}
?>