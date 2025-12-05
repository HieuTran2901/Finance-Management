<?php
  // session_start();
  // require_once __DIR__ . '/../../module/config.php';
  include '../../func/Get_Session.php';
  include '../Sidebar/Sidebar.php';

  $sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
  $conn = $sessionData['conn'];
  $users = $sessionData['user'];

  $user_id = $_SESSION['user_id']; // Giả sử bạn đã lưu user_id khi đăng nhập
  if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập trước.");
             
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
  <title>Wallet</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../css/index.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../css/fadein.css">
  <link rel="stylesheet" href="../../css/chudep.css">
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
    <?php
      $currentPage = $_SERVER['PHP_SELF']; // Lấy đường dẫn file hiện tại
      renderSidebar($users, $currentPage, "../../pages","../../index.php","../../dangkydangnhap/logout.php");
    ?>

    <!-- Main content --> 
    <main class="flex-1 p-6">
      <?php     
          // Truy vấn tags
          $sql = "
                  SELECT 
                      Tags.id AS tag_id,
                      Tags.name AS tag_name,
                      Tags.icon AS tag_icon,
                      Tags.limit_amount,
                      Tags.created_at,
                      Tags.edit_at,
                      MAX(Wallets.name) AS wallet_name,
                      (
                          SUM(CASE WHEN Transactions.type = 'income' THEN Transactions.amount ELSE 0 END) -
                          SUM(CASE WHEN Transactions.type = 'expense' THEN Transactions.amount ELSE 0 END)
                      ) AS total_amount
                  FROM Tags
                  LEFT JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
                  LEFT JOIN Transactions ON Transaction_Tags.transaction_id = Transactions.id
                  LEFT JOIN Wallets ON Transactions.wallet_id = Wallets.id
                  WHERE Tags.user_id = ?
                  GROUP BY Tags.id, Tags.created_at, Tags.edit_at
                  ORDER BY Tags.created_at, Tags.edit_at DESC
              ";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);

          // Truy vấn thông tin ví
          $sql_wallets = "SELECT id, name, balance, currency FROM Wallets WHERE user_id = ?";
          $stmt_wallets = $conn->prepare($sql_wallets);
          $stmt_wallets->bind_param("i", $user_id);
          $stmt_wallets->execute();
          $result_wallets = $stmt_wallets->get_result();
          $wallets = $result_wallets->fetch_all(MYSQLI_ASSOC);

          $spent_by_wallet = [];
            $sql = "SELECT wallet_id, SUM(amount) AS total_spent 
                    FROM Transactions 
                    WHERE user_id = ? AND type = 'expense' 
                    GROUP BY wallet_id";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $spent_by_wallet[$row['wallet_id']] = $row['total_spent'];
            }
            $stmt->close();

          // ?>

      <?php
      // Lấy thông tin ví của người dùng
      

      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_wallet'])) {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $currency = $_POST['currency'] ?? '';
        $balance = floatval($_POST['balance'] ?? 0);

        if ($name !== '' && $currency !== '') {
            $stmt = $conn->prepare("INSERT INTO Wallets (user_id, name, type, balance, currency, created_at, edit_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("issds", $user_id, $name, $type, $balance, $currency);
            $stmt->execute();
        }
}
      $stmt = $conn->prepare("SELECT id, name, type, balance, currency, created_at,edit_at FROM Wallets WHERE user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $wallets = $result->fetch_all(MYSQLI_ASSOC);

      ?>

      <div class="bg-white rounded-md shadow p-6 mb-6">
        <!-- Nút Thêm Ví -->
        <div class="mb-6">
            <!-- Tiêu đề căn giữa, chữ nổi bật -->
        <h2 class="snow-text "
            data-text="DANH SÁCH VÍ">
            DANH SÁCH VÍ
        </h2>




            <!-- Nút thêm nằm bên phải -->
            <div class="flex justify-end">
                <button onclick="openAddWalletModal()" 
                    class="bg-gradient-to-r from-green-400 to-green-600 hover:from-green-500 hover:to-green-700
                          text-white px-5 py-2.5 rounded-full flex items-center gap-2 font-semibold shadow-md transition-all duration-200">
                    Thêm Ví
                </button>
            </div>
        </div>





        <?php if (count($wallets) === 0): ?>
          <p>Không có ví nào.</p>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($wallets as $index => $wallet): ?>
                <?php
                    $wallet_id = $wallet['id'];
                    $original_balance = floatval($wallet['balance']);

                    $stmt = $conn->prepare("SELECT
                        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
                        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
                        FROM Transactions WHERE wallet_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $wallet_id, $user_id);
                    $stmt->execute();
                    $stmt->bind_result($total_income, $total_expense);
                    $stmt->fetch();
                    $stmt->close();

                    $total_income = floatval($total_income);
                    $total_expense = floatval($total_expense);
                    $available_balance = $original_balance + $total_income - $total_expense;

                    // Chọn gradient ngẫu nhiên cho mặt trước
                    $gradients = [
                        'from-indigo-500 via-purple-500 to-pink-500',
                        'from-blue-500 via-cyan-500 to-teal-500',
                        'from-green-500 via-lime-500 to-yellow-500',
                        'from-red-500 via-orange-500 to-amber-500',
                        'from-purple-500 via-fuchsia-500 to-rose-500',
                    ];
                    $selected_gradient = $gradients[$index % count($gradients)];
                ?>

                <div class="group [perspective:1000px]">
                    <div class="relative h-[160px] w-full transition-transform duration-700 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)]">

                        <!-- Mặt trước -->
                        <div class="absolute inset-0 bg-gradient-to-r <?= $selected_gradient ?> text-white p-6 rounded-xl shadow-md [backface-visibility:hidden] flex flex-col justify-between transform transition-transform duration-300 group-hover:scale-105">
                            <div class="flex justify-between items-center">
                                <div class="text-sm opacity-90 font-medium tracking-wide"><?= htmlspecialchars($wallet['type']) ?> • <?= htmlspecialchars($wallet['currency']) ?></div>
                                <img src="https://img.icons8.com/ios-filled/50/ffffff/sim-card-chip.png" alt="Chip" class="h-6 w-8 opacity-80 filter grayscale" style="filter: brightness(0) invert(1);">
                            </div>
                            <div>
                                <div class="text-xl font-semibold mb-1"><?= htmlspecialchars($wallet['name']) ?></div>
                                <div class="text-2xl mt-1 font-bold tracking-wide"><?= number_format($available_balance, 0) ?>₫</div>
                            </div>
                            <div class="text-xs mt-4 flex justify-between opacity-80">
                                <span>Tạo: <?= date('d/m/Y', strtotime($wallet['created_at'])) ?></span>
                                <span>Sửa: <?= date('d/m/Y', strtotime($wallet['edit_at'])) ?></span>
                            </div>
                        </div>

                        <!-- Mặt sau -->
                      <div class="absolute inset-0 bg-gray-800 text-white rounded-xl shadow-md [transform:rotateY(180deg)] [backface-visibility:hidden] flex flex-col overflow-hidden">

                          <!-- Dải từ (Magnetic Stripe) -->
                          <div class="h-10 bg-black mt-5 w-full"></div>

                            <div class="absolute right-0 top-[-10px] flex justify-end mt-auto pt-4 border-t border-gray-700">
                              <a href="javascript:void(0)"
                                onclick="openEditWalletModal(<?= $wallet['id'] ?>)"
                                class="text-blue-600 hover:text-blue-800 font-medium mx-1.5 p-1 rounded-md hover:bg-blue-50 transition-colors duration-150"
                                title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                              </a>


                              <a href="../Wallet/delete_wallet.php?id=<?= $wallet['id'] ?>"
                                onclick="return confirm('Bạn có chắc muốn xoá ví này không? Toàn bộ giao dịch liên quan cũng sẽ bị xóa.')"
                                class="inline-flex items-center px-3 py-1.5 text-red-400 text-sm font-semibold">
                                  <i class="fas fa-trash-alt"></i>
                              </a>
                            </div>


                          <!-- Khu vực Mã bảo mật (CVV) / Chữ ký -->
                          <div class="bg-gray-700 mx-6 mt-4 p-3 rounded-lg flex flex-col">
                              <p class="text-xs text-gray-400 mb-1">MÃ BẢO MẬT (CVV)</p>
                              <div class="bg-gray-300 text-gray-900 h-7 px-3 flex items-center justify-end text-sm font-bold tracking-widest rounded-sm">
                                  XXX <!-- Giả lập 3 chữ số mã bảo mật -->
                              </div>
                              <p class="text-xs text-gray-500 mt-2 text-right">Chữ ký được ủy quyền</p>
                          </div>

                          <!-- Khu vực Chi tiết ví (Thông tin ID, Loại, Tiền tệ, Ngày) và Nút hành động -->
                          <div class="flex-grow p-6 flex flex-col justify-between">
                              <div class="text-sm space-y-2">
                                  <h3 class="text-lg font-semibold mb-2">Thông tin chi tiết</h3>
                                  <p><span class="font-medium text-gray-400">ID Ví:</span> <?= $wallet['id'] ?></p>
                                  <p><span class="font-medium text-gray-400">Loại Ví:</span> <?= htmlspecialchars($wallet['type']) ?></p>
                                  <p><span class="font-medium text-gray-400">Tiền tệ:</span> <?= htmlspecialchars($wallet['currency']) ?></p>
                                  <p><span class="font-medium text-gray-400">Ngày tạo:</span> <?= date('d/m/Y', strtotime($wallet['created_at'])) ?></p>
                                  <p><span class="font-medium text-gray-400">Ngày sửa:</span> <?= date('d/m/Y', strtotime($wallet['edit_at'])) ?></p>
                              </div>

                              <!-- Các nút hành động (Edit/Delete) - Đẩy xuống cuối bởi flex-grow -->
                            
                          </div>
                          <!-- Phần Branding/Disclaimer nhỏ ở cuối thẻ -->
                          <div class="p-4 text-center text-xs text-gray-500 border-t border-gray-700">
                              Ứng dụng Tài chính của bạn © 2025
                          </div>
                      </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
      </div>
      <div class="bg-white rounded-xl shadow-lg p-6"> <!-- Tăng đổ bóng và bo tròn góc -->
        <div class="flex flex-col mb-6 border-b pb-4">
            <!-- Tiêu đề ở giữa -->
            <h2 class="snow-text "
            data-text="DANH SÁCH THẺ">
                DANH SÁCH THẺ 
            </h2>

            <!-- Nút thêm nằm bên phải dưới tiêu đề -->
            <div class="flex justify-end">
                <button onclick="openAddTagModal()" 
                    class="bg-gradient-to-r from-indigo-500 to-indigo-700 
                          hover:from-indigo-600 hover:to-indigo-800 
                          text-white px-5 py-2.5 rounded-full flex items-center gap-2 font-semibold shadow-md transition-all duration-200">
                         Tạo Thẻ Mới
                </button>
            </div>
        </div>

<?php
include "modals/add_wallet_modal.php";
include "modals/edit_wallet_modal.php";
include "modals/add_tag_modal.php";
include "modals/edit_tag_modal.php";
?>

     
    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm"> <!-- Bọc bảng trong container có bo tròn và đổ bóng -->
        <table class="min-w-full table-auto divide-y divide-gray-200"> <!-- Bỏ border của table, dùng divide-y -->
            <thead class="bg-gray-50"> <!-- Nền header nhẹ -->
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">STT</th> <!-- rounded-tl-lg cho góc trên bên trái -->
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên Thẻ</th> <!-- Căn trái tên thẻ -->
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng tiền</th> <!-- Căn phải tổng tiền -->
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Giới hạn</th> <!-- Căn phải giới hạn -->
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ví Liên Quan</th> <!-- Đổi tên cho rõ ràng -->
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày chỉnh</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">Thao Tác</th> <!-- rounded-tr-lg cho góc trên bên phải -->
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100"> <!-- Nền body trắng, chia dòng mỏng -->
                <?php foreach ($data as $index => $row): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-150"> <!-- Hiệu ứng hover mượt mà -->
                    <td class="px-4 py-3 text-center text-sm text-gray-700"><?= $index + 1 ?></td>
                    <td class="px-4 py-3 text-center text-2xl"><?=htmlspecialchars($row['tag_icon']) ?></td> <!-- Tăng kích thước icon -->
                    <td class="px-4 py-3 text-left text-sm font-medium text-gray-800"><?= htmlspecialchars($row['tag_name']) ?></td> <!-- Tăng độ đậm và căn trái -->
                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700"><?= number_format($row['total_amount'] ?? 0) ?>₫</td> <!-- Màu chữ đậm hơn -->
                    <td class="px-4 py-3 text-right text-sm font-semibold">
                        <?php
                            $limit_amount = $row['limit_amount'] ?? 0;
                            if ($limit_amount > 0) {
                                // So sánh total_amount với limit_amount để hiển thị màu sắc
                                $current_amount = $row['total_amount'] ?? 0;
                                $percentage = ($current_amount / $limit_amount) * 100;
                                $limit_text = number_format($limit_amount) . '₫';

                                if ($percentage >= 100) {
                                    echo '<span class="text-red-600">' . $limit_text . '</span>';
                                } elseif ($percentage >= 80) {
                                    echo '<span class="text-orange-500">' . $limit_text . '</span>';
                                } else {
                                    echo '<span class="text-green-600">' . $limit_text . '</span>';
                                }
                            } else {
                                echo '<span class="text-gray-500">Không giới hạn</span>';
                            }
                        ?>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-700"><?= htmlspecialchars($row['wallet_name'] ?? 'Không xác định') ?></td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($row['edit_at']))?>
                    </td>

                    <td class="px-4 py-3 text-center text-sm">
                        <a href="javascript:void(0)"
                                    onclick="openEditTagModal(<?= $row['tag_id'] ?>)"
                                    class="text-blue-600 hover:text-blue-800 font-medium mx-1.5 p-1 rounded-md hover:bg-blue-50 transition-colors duration-150"
                                    title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                  </a>

                        <a href="delete_tag.php?id=<?= $row['tag_id'] ?>" onclick="return confirm('Bạn có chắc muốn xoá thẻ này không?')" class="text-red-600 hover:text-red-800 font-medium mx-1.5 p-1 rounded-md hover:bg-red-50 transition-colors duration-150" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php if (empty($data)): ?>
            <div class="text-center py-8 text-gray-500">Chưa có thẻ nào được tạo.</div>
        <?php endif; ?>
    </div>
</div>

    </main>
  </div>
  

</body>
</script>
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


<!-- Custom Scripts (Modal & Chart) -->
<script src="../../js/Modal.js"></script>


</html>



<script>
  function checkBalance(input) {
    const value = input.value;

    // Không cho nhập dấu '-' và không cho số âm
    if (value.includes('-') || parseFloat(value) < 0) {
      input.setCustomValidity("Không được nhập số âm hoặc dấu '-'");
    } else {
      input.setCustomValidity("");
    }
  }
</script>