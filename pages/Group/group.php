<?php
include '../../Func/Get_Session.php';
include '../Sidebar/Sidebar.php';

$sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
$conn = $sessionData['conn'];
$users = $sessionData['user'];

if (!isset($_SESSION['user_id'])) {
  die("Vui lòng đăng nhập trước.");
}

$user_id = $_SESSION['user_id'];
$create_message = '';
// Lấy tên user
$sql_user = $conn->prepare("SELECT username FROM Users WHERE id = ?");
$sql_user->bind_param("i", $user_id);
$sql_user->execute();
$result_user = $sql_user->get_result();
$users = $result_user->fetch_assoc();

// Xử lý tạo nhóm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
  $group_name = trim($_POST['group_name'] ?? '');

  if ($group_name !== '') {
    $check = $conn->prepare("SELECT id FROM Groupss WHERE name = ? AND created_by = ?");
    $check->bind_param("si", $group_name, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      // Nhóm đã tồn tại
      $create_message = '<p class="text-red-600 font-medium mt-2">Nhóm đã tồn tại!</p>';
    } else {
      // Tạo nhóm mới
      $stmt = $conn->prepare("INSERT INTO Groupss (name, group_code, created_by, created_at) VALUES (?, ?, ?, NOW())");
      $group_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
      $stmt->bind_param("ssi", $group_name, $group_code, $user_id);
      $stmt->execute();
      $group_id = $stmt->insert_id;
      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO Group_Members (group_id, user_id, role, joined_at) VALUES (?, ?, 'owner', NOW())");
      $stmt->bind_param("ii", $group_id, $user_id);
      $stmt->execute();
      $stmt->close();

      // Chuyển hướng sau khi tạo thành công
      header("Location: group.php");
      exit;
    }

    $check->close();
  }
}


// Lấy danh sách nhóm
$sql = "
  SELECT 
    g.id, g.name, g.created_at, u.username AS creator_name,
    COALESCE(SUM(st.amount), 0) AS total_amount
  FROM Groupss g
  JOIN Group_Members gm ON g.id = gm.group_id
  JOIN Users u ON g.created_by = u.id
  LEFT JOIN Shared_Transactions st ON st.group_id = g.id
  WHERE gm.user_id = ?
  GROUP BY g.id, g.name, g.created_at, u.username
  ORDER BY g.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tính số thành viên cho mỗi group
foreach ($data as &$group) {
    $group_id = $group['id'];
    $member_count_stmt = $conn->prepare("SELECT COUNT(user_id) AS member_count FROM Group_Members WHERE group_id = ?");
    $member_count_stmt->bind_param("i", $group_id);
    $member_count_stmt->execute();
    $member_result = $member_count_stmt->get_result();
    $member_data = $member_result->fetch_assoc();
    $group['member_count'] = $member_data['member_count'] ?? 0;
    $member_count_stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Group</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6; /* thumb and track color */
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f3f4f6; /* track color */
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #d1d5db; /* thumb color */
            border-radius: 10px;
            border: 2px solid #f3f4f6; /* creates padding around thumb */
        }
        /* Custom waving hand animation */
        @keyframes waving-hand {
            0% { transform: rotate(0deg); }
            15% { transform: rotate(14deg); }
            30% { transform: rotate(-8deg); }
            45% { transform: rotate(14deg); }
            60% { transform: rotate(-4deg); }
            75% { transform: rotate(10deg); }
            100% { transform: rotate(0deg); }
        }
        .animate-waving-hand {
            animation: waving-hand 2.5s infinite;
            transform-origin: 70% 70%;
            display: inline-block;
        }
    </style>
</head>
<!-- thông báo -->
<div id="comingSoonModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center relative animate-fade-in">
    <h2 class="text-2xl font-semibold text-indigo-700 mb-3">Thông báo</h2>
    <p class="text-gray-700 mb-6">Tính năng này đang được phát triển. Vui lòng quay lại sau!</p>
    <button id="closeModal" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition">Đóng</button>
  </div>
</div>
<body class="bg-gray-100 font-sans">
  <div class="flex min-h-screen pl-64">
    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 w-64 h-screen bg-gradient-to-b from-white via-gray-50 to-gray-100 shadow-lg flex flex-col justify-between z-10">
      <div class="p-6">
        <div class="flex items-center gap-2 mb-8">
          <img src="https://img.icons8.com/ios/50/wallet--v1.png" class="w-7 h-7" alt="Logo" />
          <span class="text-xl font-bold text-gray-800">FinManager</span>
        </div>
        <div class="flex items-center gap-3 mb-8">
          <div class="w-10 h-10 bg-green-500 text-white flex items-center justify-center rounded-full font-bold text-sm">
            <?= strtoupper(substr($users['username'], 0, 1)) ?>
          </div>
          <div class="leading-4">
            <p class="text-gray-800 font-semibold"><?= htmlspecialchars($users['username']) ?></p>
            <p class="text-gray-500 text-sm">Tài khoản cá nhân</p>
          </div>
        </div>
        <!-- Menu -->
        <?php
          $currentPage = $_SERVER['PHP_SELF']; // Lấy đường dẫn file hiện tại
          renderSidebar($users, $currentPage,"../../pages","../../index.php","../../dangkydangnhap/logout.php");
        ?>
        <!--  -->
      </div>  
      <div class="p-6 border-t border-gray-200">
        <a href="../logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-medium transition">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Tạo nhóm mới</h2>
            <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="w-full sm:flex-grow">
                    <label for="group_name" class="block text-sm font-medium mb-1">Tên nhóm:</label>
                    <input type="text" name="group_name" id="group_name" required placeholder="Nhập tên nhóm"
                           class="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <button type="submit" name="create_group"
                        class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg shadow hover:bg-emerald-700 transition font-semibold">
                    <i class="fa-solid fa-plus mr-1"></i> Tạo nhóm
                </button>
            </form>
            <?= $create_message ?>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Danh sách nhóm của bạn</h2>
                <?php if (empty($data)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p class="mb-4 text-lg">Bạn chưa tham gia hoặc tạo nhóm chia sẻ nào.</p>
                        <p>Hãy tạo một nhóm mới để quản lý tài chính cùng bạn bè hoặc gia đình!</p>
                    </div>
                <?php else: ?>
          <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm"></div>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 border text-center">STT</th>
                  <th class="px-4 py-2 border text-center">Tên Group</th>
                  <th class="px-4 py-2 border text-center">Tổng tiền</th>
                  <th class="px-4 py-2 border text-center">Thành viên</th>
                  <th class="px-4 py-2 border text-center">Tạo bởi</th>
                  <th class="px-4 py-2 border text-center">Ngày tạo</th>
                </tr>
              </thead>

              <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($data as $index => $group): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150 cursor-pointer">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $index + 1 ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-700">
                                        <?= htmlspecialchars($group['name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                        <?= number_format($group['total_amount'], 0, ',', '.') ?>₫
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <i class="fa-solid fa-users-line text-blue-500 mr-1"></i> <?= $group['member_count'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= htmlspecialchars($group['creator_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m/Y', strtotime($group['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="group_detail/group_detail.php?id=<?= $group['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition duration-200 shadow-sm">
                                            Chi tiết <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                                        </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
   </div>
</body>
<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Khởi tạo AOS và Smooth Scroll -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Khởi tạo AOS
    AOS.init({
      once: true,
      mirror: false
    });

    // Cuộn mượt cho các liên kết nội bộ
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: 'smooth'
          });
        }
      });
    });
  });
</script>

<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>

<!-- Custom Scripts (Modal & Chart) -->
<script src="../../js/Modal.js"></script>
<script src="../../js/Chart.js"></script>
</html>
