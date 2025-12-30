<?php

$data = include 'group_detail_controller.php';
extract($data);
include "../../Sidebar/Sidebar.php";

$sql_user = $conn->prepare("SELECT username, is_placeholder FROM users WHERE id = ?");
$sql_user->bind_param("i", $user_id);
$sql_user->execute();
$result_user = $sql_user->get_result();
$users = $result_user->fetch_assoc();
$sql_user->close();

$is_fake_user = isset($users['is_placeholder']) && $users['is_placeholder'] == 1;

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinFlow - Chi tiết nhóm: <?= htmlspecialchars($group['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../css//fadein.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar for better aesthetics */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
            /* thumb and track color */
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f3f4f6;
            /* track color */
            border-radius: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            /* thumb color */
            border-radius: 10px;
            border: 2px solid #f3f4f6;
            /* creates padding around thumb */
        }

        /* Waving hand animation for a friendly touch */
        @keyframes waving-hand {
            0% {
                transform: rotate(0deg);
            }

            15% {
                transform: rotate(14deg);
            }

            30% {
                transform: rotate(-8deg);
            }

            45% {
                transform: rotate(14deg);
            }

            60% {
                transform: rotate(-4deg);
            }

            75% {
                transform: rotate(10deg);
            }

            100% {
                transform: rotate(0deg);
            }
        }

        .animate-waving-hand {
            animation: waving-hand 2.5s infinite;
            transform-origin: 70% 70%;
            /* Adjust origin for a more natural wave */
            display: inline-block;
        }

        /* Custom pulse animation for chat button */
        @keyframes pulse-custom {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
            }
        }

        .animate-pulse-custom {
            animation: pulse-custom 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Custom styling for tables */
        table {
            border-collapse: separate;
            /* Use separate to allow border-radius on cells */
            border-spacing: 0;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            /* Light border for separation */
            border-right: 1px solid #e5e7eb;
            padding: 12px 16px;
            /* Increased padding for better readability */
        }

        th:first-child,
        td:first-child {
            border-left: 1px solid #e5e7eb;
        }

        tr:first-child th:first-child {
            border-top-left-radius: 0.75rem;
            /* rounded-xl on corners */
        }

        tr:first-child th:last-child {
            border-top-right-radius: 0.75rem;
        }

        tr:last-child td:first-child {
            border-bottom-left-radius: 0.75rem;
        }

        tr:last-child td:last-child {
            border-bottom-right-radius: 0.75rem;
        }

        /* Ensure table overflow is handled gracefully with inner shadow */
        .overflow-x-auto {
            position: relative;
        }

        .overflow-x-auto::before,
        .overflow-x-auto::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
        }

        .overflow-x-auto::before {
            left: 0;
            width: 1rem;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.05), transparent);
            opacity: 0;
        }

        .overflow-x-auto::after {
            right: 0;
            width: 1rem;
            background: linear-gradient(to left, rgba(0, 0, 0, 0.05), transparent);
            opacity: 1;
            /* Initially visible for right shadow */
        }

        .overflow-x-auto.scrolled-left::before {
            opacity: 1;
        }

        .overflow-x-auto.scrolled-right::after {
            opacity: 0;
        }
    </style>
</head>

<!-- thông báo -->
<?php include "../../comming_soon_modal.php" ?>


<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-100 min-h-screen font-sans antialiased">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php
        $current_page = $_SERVER['PHP_SELF'];
        renderSidebar($users, $current_page, "../../../pages", "../../../index.php", "../../logout.php");
        ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 lg:ml-64">
            <!-- Greeting Section -->

            <div class="max-w-full mx-auto bg-white p-6 rounded-xl shadow-lg border border-gray-100" data-aos="fade-up">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b border-gray-200">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Thông tin nhóm: <span class="text-emerald-600"><?= htmlspecialchars($group['name']) ?></span></h1>
                        <h2 class="text-xl font-bold text-gray-800 ">
                            ID Phòng: <?= $group_code ?>
                        </h2>
                    </div>
                    <div class="flex flex-wrap gap-3 mt-4 sm:mt-0 items-center">
                        <?php if (in_array($current_user_role, ['owner', 'owner_full'])): ?>
                            <!-- Rename Group Form -->
                            <form method="POST" onsubmit="return confirm('Bạn muốn đổi tên nhóm này thành tên mới?')" class="flex items-center gap-2">
                                <input type="text" name="new_group_name" placeholder="Tên nhóm mới"
                                    class="border border-gray-300 px-3 py-1.5 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition w-32 sm:w-auto" required
                                    oninvalid="this.setCustomValidity('Vui lòng nhập tên nhóm mới.')"
                                    oninput="this.setCustomValidity('')">
                                <button type="submit" name="rename_group" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition shadow-sm">
                                    <i class="fas fa-edit mr-1"></i> Đổi tên
                                </button>
                            </form>
                            <!-- Delete Group Button -->
                            <?php if ($current_user_role === 'owner'): // Only true owner can delete 
                            ?>
                                <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN nhóm này và tất cả dữ liệu liên quan? Hành động này không thể hoàn tác!')">
                                    <button type="submit" name="delete_group" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition shadow-sm">
                                        <i class="fas fa-trash-alt mr-1"></i> Xóa nhóm
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Exit Button -->
                        <?php
                        $is_fake_user = isset($user['is_placeholder']) && $user['is_placeholder'] == 1;
                        $exit_link = $is_fake_user ? '../../logout.php' : '../group.php';
                        ?>
                        <a href="<?= $exit_link ?>"
                            class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-200 transition duration-200 ml-2 shadow-sm text-2xl"
                            title="Quay lại danh sách nhóm"
                            <?= $is_fake_user ? 'onclick="return confirm(\'Bạn có chắc muốn thoát không?\')"' : '' ?>>&times;</a>

                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-600 mb-1"><strong>Người tạo:</strong> <span class="font-medium text-gray-800"><?= htmlspecialchars($group['creator_name']) ?></span></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1"><strong>Ngày tạo:</strong> <span class="font-medium text-gray-800"><?= date('d/m/Y', strtotime($group['created_at'])) ?></span></p>
                    </div>
                </div>

                <!-- Members Section -->
                <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">Thành viên nhóm</h2>
                <div class="mb-6">
                    <?php if (in_array($current_user_role, ['owner', 'owner_full'])): ?>
                        <form method="POST" action="" class="flex flex-col sm:flex-row gap-4 items-end bg-blue-50 p-4 rounded-xl border border-blue-100 shadow-sm mb-4" data-aos="fade-right">
                            <input type="hidden" name="add_member_inline" value="1">
                            <div class="flex-grow w-full">
                                <label for="member_email" class="block text-sm font-medium text-gray-700 mb-1">Email thành viên:</label>
                                <input type="email" id="member_email" name="email" placeholder="Email (ví dụ: abc@example.com)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 transition text-base" required
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    oninvalid="this.setCustomValidity('Vui lòng nhập email hợp lệ')"
                                    oninput="this.setCustomValidity('')">
                            </div>
                            <div class="w-full sm:w-auto">
                                <label for="member_role" class="block text-sm font-medium text-gray-700 mb-1">Vai trò:</label>
                                <select id="member_role" name="role"
                                    class="w-50 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 transition text-base"
                                    required>

                                    <option value="member">Thành viên</option>
                                    <?php if ($current_user_role === 'owner' || $current_user_role === 'owner_full'): ?>
                                        <option value="manager">Quản lý</option>
                                        <option value="owner_full">Phó nhóm</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-indigo-700 transition duration-200 flex items-center gap-2 font-semibold w-full sm:w-auto justify-center">
                                <i class="fas fa-user-plus text-sm"></i> Thêm thành viên
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($members)): ?>
                        <div class="text-center py-4 text-gray-500" data-aos="fade-in">
                            Chưa có thành viên nào trong nhóm này.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm" data-aos="fade-up">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider rounded-tl-xl">Thành viên</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày tham gia</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tổng đã chi</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vai trò</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider rounded-tr-xl">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($members as $m): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 flex items-center gap-2">
                                                <?php
                                                $role_icon = '';
                                                $role_color = 'text-gray-700';
                                                if ($m['role'] === 'owner') {
                                                    $role_icon = '<i class="fas fa-crown text-amber-500"></i>';
                                                } elseif ($m['role'] === 'owner_full') {
                                                    $role_icon = '<i class="fas fa-star text-yellow-500"></i>';
                                                } elseif ($m['role'] === 'manager') {
                                                    $role_icon = '<i class="fas fa-user-tie text-blue-500"></i>';
                                                } else { // member
                                                    $role_icon = '<i class="fas fa-user text-gray-500"></i>';
                                                }
                                                // Highlight current user
                                                if ($m['user_id'] === $user_id) {
                                                    $role_color = 'text-emerald-600 font-semibold';
                                                }
                                                ?>
                                                <span class="<?= $role_color ?>"><?= $role_icon ?> <?= htmlspecialchars($m['username']) ?></span>
                                                <?php if (isset($m['is_placeholder']) && $m['is_placeholder']): ?>
                                                    <span class="text-xs px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full">ảo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?= date('d/m/Y', strtotime($m['joined_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                                <?= number_format($memberTotals[$m['user_id']] ?? 0, 0, ',', '.') ?>₫
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?php
                                                if ($m['role'] === 'owner') {
                                                    echo 'Nhóm trưởng';
                                                } elseif ($m['role'] === 'owner_full') {
                                                    echo 'Phó nhóm';
                                                } elseif ($m['role'] === 'manager') {
                                                    echo 'Quản lý chi tiêu';
                                                } else {
                                                    echo 'Thành viên';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if (
                                                    in_array($current_user_role, ['owner', 'owner_full']) &&
                                                    $m['role'] !== 'owner' && // Cannot change owner's role
                                                    $m['user_id'] != $user_id // Cannot change self-role if not owner
                                                ): ?>
                                                    <form method="POST" action="" class="inline-flex items-center gap-1">
                                                        <input type="hidden" name="update_role" value="1">
                                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($m['user_id']) ?>">
                                                        <select name="new_role" class="border border-gray-300 px-2 py-1 rounded-lg text-xs focus:ring-emerald-500 focus:border-emerald-500 transition">
                                                            <option value="member" <?= $m['role'] === 'member' ? 'selected' : '' ?>>Thành viên</option>
                                                            <?php if ($current_user_role === 'owner' || $current_user_role === 'owner_full'): ?>
                                                                <option value="manager" <?= $m['role'] === 'manager' ? 'selected' : '' ?>>Quản lý</option>
                                                                <option value="owner_full" <?= $m['role'] === 'owner_full' ? 'selected' : '' ?>>Phó nhóm</option>
                                                            <?php endif; ?>
                                                        </select>
                                                        <button type="submit" class="bg-indigo-600 text-white px-2.5 py-1 rounded-lg hover:bg-indigo-700 text-xs font-medium transition shadow-sm">Cập nhật</button>
                                                    </form>
                                                    <?php if (isset($group['created_by']) && $m['user_id'] !== $group['created_by'] && $m['user_id'] !== $user_id): // Not the group creator and not self 
                                                    ?>
                                                        <a href="../delete_member.php?group_id=<?= $group_id ?>&user=<?= urlencode($m['username']) ?>"
                                                            class="ml-2 text-red-600 hover:text-red-800 text-xs font-medium transition"
                                                            onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này khỏi nhóm? Toàn bộ giao dịch của họ sẽ vẫn còn nhưng họ sẽ không thể truy cập nhóm nữa.')">Xóa</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-400 italic">Không có quyền</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shared Transactions Section -->
                <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">Giao dịch nhóm</h2>
                <div class="mb-6 text-right">
                    <?php if (in_array($current_user_role, ['manager', 'owner', 'owner_full'])): ?>
                        <a href="./shared_transaction/add_shared_transaction.php?id=<?= $group_id ?>" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg shadow-md transition duration-200 font-semibold" data-aos="zoom-in">
                            <i class="fas fa-plus-circle text-lg"></i> Thêm giao dịch mới
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="text-center py-4 text-gray-500" data-aos="fade-in">
                        Chưa có giao dịch nào được chia sẻ trong nhóm này.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm" data-aos="fade-up">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider rounded-tl-xl">Ngày</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Người chi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mô tả</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Số tiền</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider rounded-tr-xl">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $tx): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= date('d/m/Y', strtotime($tx['date'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?= (isset($tx['creator_id']) && $tx['creator_id'] === $user_id) ? '<span class="font-semibold text-emerald-600">Bạn</span>' : htmlspecialchars($tx['creator']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium"><?= htmlspecialchars($tx['description']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                            <?= number_format($tx['amount'], 0, ',', '.') ?>₫
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if (in_array($current_user_role, ['manager', 'owner', 'owner_full']) || (isset($tx['creator_id']) && $tx['creator_id'] === $user_id)): ?>
                                                <a href="./shared_transaction/edit_shared_transaction.php?id=<?= $group_id ?>&transaction_id=<?= $tx['id'] ?>" class="text-blue-600 hover:text-blue-800 transition mr-2">Sửa</a>
                                                <a href="./shared_transaction/delete_shared_transaction.php?id=<?= $group_id ?>&transaction_id=<?= $tx['id'] ?>" class="text-red-600 hover:text-red-800 transition" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này? Hành động này không thể hoàn tác!')">Xóa</a>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">Không có quyền</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include __DIR__ . '/../message/chatbox_component.php'; ?>

        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script>
            AOS.init({
                duration: 800, // global duration for AOS animations
                once: true // animations only happen once
            });
        </script>

</body>
</script>
<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Khởi tạo AOS và Smooth Scroll -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo AOS
        AOS.init({
            once: true,
            mirror: false
        });

        // Cuộn mượt cho các liên kết nội bộ
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
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


<!-- Custom Scripts (Modal & Chart) -->
<script src="../../../js/Modal.js"></script>

</html>