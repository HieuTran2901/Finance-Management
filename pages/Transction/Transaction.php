<?php
  // session_start();
  // require_once __DIR__ . '/../../module/config.php';
  include '../../Func/Get_Session.php';
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
  <title>Transaction</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../css/fadein.css">

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
      renderSidebar($users, $currentPage,"../../pages","../../index.php","../../dangkydangnhap/logout.php");
    ?>
  </div>

  <!-- Đăng xuất -->
  <div class="p-6 border-t border-gray-200">
    <a href="../../dangkydangnhap/logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-medium transition">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
    </a>
  </div>
</aside>


<div class="flex-1 p-6 space-y-6">
    <?php
      // Lấy thông tin ví của người dùng
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_wallet'])) {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $currency = $_POST['currency'] ?? '';
        $balance = floatval($_POST['balance'] ?? 0);

        if ($name !== '' && $currency !== '') {
            $stmt = $conn->prepare("INSERT INTO Wallets (user_id, name, type, balance, currency, created_at,edit_at) VALUES (?, ?, ?, ?, ?, NOW(),NOW())");
            $stmt->bind_param("issds", $user_id, $name, $type, $balance, $currency);
            $stmt->execute();
        } 
}
      $stmt = $conn->prepare("SELECT id, name, type, balance, currency, created_at ,edit_at FROM Wallets WHERE user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $wallets = $result->fetch_all(MYSQLI_ASSOC);


      // Lấy danh sách giao dịch theo user_id
      $transaction_query = "
            SELECT 
              t.id,
              t.date,
              c.name AS category_name,
              t.amount,
              t.type,
              t.note,
              t.photo_receipt_url,
              t.emotion_level,
              GROUP_CONCAT(DISTINCT tg.name SEPARATOR ', ') AS tags
            FROM Transactions t
            JOIN Categories c ON t.category_id = c.id
            LEFT JOIN Transaction_Tags tt ON t.id = tt.transaction_id
            LEFT JOIN Tags tg ON tt.tag_id = tg.id
            WHERE t.user_id = ?
            GROUP BY t.id, t.date, c.name, t.amount, t.type, t.note
            ORDER BY t.date DESC
          ";

      $sql_used = "
                  SELECT 
                      Transactions.wallet_id,
                      SUM(Transactions.amount) AS used_amount
                  FROM Transactions
                  INNER JOIN Transaction_Tags ON Transactions.id = Transaction_Tags.transaction_id
                  WHERE Transactions.type = 'expense'
                  GROUP BY Transactions.wallet_id
              ";
              $stmt_used = $conn->query($sql_used);
              $used_per_wallet = [];
              while ($row = $stmt_used->fetch_assoc()) {
                  $used_per_wallet[$row['wallet_id']] = $row['used_amount'];
              }

      $stmt = $conn->prepare($transaction_query);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $transactions_result = $stmt->get_result();
      $transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);

      ?>
  <!-- Wallets Section -->
   <div class="bg-white rounded-xl shadow-lg p-6 mb-6"> <!-- Tăng đổ bóng và bo tròn góc -->
    <div class="flex justify-between items-center mb-6 border-b pb-4"> <!-- Thêm border-b và padding -->
        <h2 class="text-2xl font-bold text-gray-800">Danh sách Ví Của Bạn</h2> <!-- Tăng kích thước tiêu đề -->
        <!-- Nếu bạn muốn thêm nút "Thêm Ví", có thể đặt ở đây, ví dụ: -->
        <!-- <a href="add_wallet.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-full flex items-center gap-2 font-semibold shadow-md transition-all duration-200">
            <i class="fas fa-plus text-sm"></i> Thêm Ví Mới
        </a> -->
    </div>

    <?php if (count($wallets) === 0): ?>
        <div class="text-center py-8 text-gray-500">
            <p class="mb-4">Bạn chưa có ví nào được tạo.</p>
        </div>
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
                      `<div class="absolute inset-0 bg-gray-800 text-white rounded-xl shadow-md [transform:rotateY(180deg)] [backface-visibility:hidden] flex flex-col overflow-hidden">

                          <!-- Dải từ (Magnetic Stripe) -->
                          <div class="h-10 bg-black mt-5 w-full"></div>

                            <div class="absolute right-0 top-[-10px] flex justify-end mt-auto pt-4 border-t border-gray-700"> <!-- Thêm border-t để phân tách -->
                                  <a href="../Wallet/edit_wallet.php?id=<?= $wallet['id'] ?>" class="inline-flex items-center text-blue-300 text-sm font-semibold ">
                                      <i class="fas fa-edit"></i>
                                  </a>
                                  <a href="../Wallet/delete_wallet.php?id=<?= $wallet['id'] ?>" onclick="return confirm('Bạn có chắc muốn xoá ví này không? Toàn bộ giao dịch liên quan cũng sẽ bị xóa.')" class="inline-flex items-center px-3 py-1.5 rounded-md text-red-400 text-sm font-semibold shadow-sm">
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
                      </div>`
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


  <!-- Transactions Section -->
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold">💳 Giao dịch</h2>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" onclick="openTransactionForm()"><a href="add_transaction.php"> Thêm giao dịch</a></button>
    </div>
                
    <!-- Bảng giao dịch -->
    <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-2 border text-center">STT</th>
          <th class="px-4 py-2 border text-center">Tên giao dịch</th>
          <th class="px-4 py-2 border text-center">Số tiền</th>
          <th class="px-4 py-2 border text-center">Ghi chú</th>
          <th class="px-4 py-2 border text-center">Tags</th>
          <th class="px-4 py-2 border text-center">Ảnh</th>
          
          <th class="px-4 py-2 border text-center">Ngày Tạo</th>
          <th class="px-4 py-2 border text-center">Ngày Chỉnh</th>
          <th class="px-4 py-2 border text-center text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <!-- Giao dịch mẫu -->
            <?php if (count($transactions) === 0): ?>
              <tr>
                <td colspan="5" class="p-4 text-center text-gray-500">Không có giao dịch nào.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($transactions as $index =>$transaction): ?>
                <tr class="hover:bg-gray-50">
                   <td class="px-4 py-2 border text-center"><?= $index + 1 ?></td>
                  <td class="px-4 py-2 border text-center"><?= htmlspecialchars($transaction['category_name']) ?></td>
                  <td class="px-4 py-2 border text-center <?= $transaction['type'] === 'expense' ? 'text-red-500' : 'text-green-600' ?>">
                    <?= ($transaction['type'] === 'expense' ? '-' : '+') . number_format($transaction['amount'], 0) ?> VND
                  </td>
                  <td class="px-4 py-2 border text-center"><?= htmlspecialchars($transaction['note']) ?></td>
                  <td class="px-4 py-2 border text-center">
                    <?php if (!empty($transaction['tags'])): ?>
                      <?php foreach (explode(',', $transaction['tags']) as $tag): ?>
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1"><?= htmlspecialchars(trim($tag)) ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-gray-400 text-sm">Không có</span>
                    <?php endif; ?>
                  </td>

                  <td class="px-4 py-2 border text-center">
                    <?php if (!empty($transaction['photo_receipt_url'])): ?>
                      <a href="view_image.php?src=<?= urlencode($transaction['photo_receipt_url']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($transaction['photo_receipt_url']) ?>" class="w-10 h-10 rounded hover:opacity-75 cursor-pointer" alt="Ảnh" />
                      </a>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>

                  

                  <td class="px-4 py-2 border text-center"><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                  <td class="px-4 py-2 border text-center"><?= date('d/m/Y', strtotime($wallet['edit_at']))  ?></td>
                  <td class="px-4 py-2 border text-center">
                    <a href="edit_transaction.php?id=<?= $transaction['id'] ?>" class="text-blue-600 hover:underline">
                      <i class="fas fa-edit "></i>
                    </a>
                    <a href="delete_transaction.php?id=<?= $transaction['id'] ?>" class="text-red-600 hover:underline ml-2" onclick="return confirm('Bạn có chắc muốn xóa?')">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>

        <!-- Nhiều dòng khác -->
      </tbody>
    </table>
  </div>

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

<!-- Custom Scripts (Modal & Chart) -->
<script src="../../js/Modal.js"></script>
<script>
  function openTransactionForm() {
    document.getElementById('transactionForm').classList.remove('hidden');
    document.getElementById('transactionForm').classList.add('flex');
  }

  function closeTransactionForm() {
    document.getElementById('transactionForm').classList.add('hidden');
    document.getElementById('transactionForm').classList.remove('flex');
  }
</script>