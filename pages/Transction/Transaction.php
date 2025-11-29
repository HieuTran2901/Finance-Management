<?php
  require_once "../../Func/Get_Session.php";
  require_once "../Sidebar/Sidebar.php";

  $sessionData = Get_Session('../../module/config.php', '../pages/Login/Login.php');
  $users = $sessionData['user'];
  $conn = $sessionData['conn'];
  $user_id = $sessionData['user_id'];
  
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transaction</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body class="bg-gray-100 font-sans">
  <div class="flex min-h-screen">
  <aside class="w-64 fixed left-0 top-0 h-full bg-white shadow-lg p-6">
    <?php
      $currentPage = $_SERVER['PHP_SELF'];
      renderSidebar($users, $currentPage , '..','../../index.php','../logout.php');
    ?>
  </aside>
  <div class="flex-1 ml-64 p-6 space-y-6">
    
    <?php
      // Lấy thông tin ví của người dùng
      $user_id = $_SESSION['user_id']; // Giả sử bạn đã lưu user_id khi đăng nhập
          if (!isset($_SESSION['user_id'])) {
              die("Vui lòng đăng nhập trước.");
             
          }

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
    <div class="bg-white rounded-md shadow p-6 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Danh sách Ví</h2>
        </div>

        <?php if (count($wallets) === 0): ?>
          <p>Không có ví nào.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
          <table class="min-w-full table-auto border border-gray-200">
            <thead class="bg-gray-100 text-left text-sm font-medium text-gray-700">
              <tr>
                <th class="px-4 py-2 border">STT</th>
                <th class="px-4 py-2 border">Tên Ví</th>
                <th class="px-4 py-2 border">Loại</th>
                <th class="px-4 py-2 border">Số Dư</th>
                <th class="px-4 py-2 border">Tiền Tệ</th>
                <th class="px-4 py-2 border">Ngày Tạo</th>
                <th class="px-4 py-2 border text-center">Ngày Chỉnh</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($wallets as $index => $wallet): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['name']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['type']) ?></td>

                <?php
                    $used = $used_per_wallet[$wallet['id']] ?? 0;
                    $available_balance = $wallet['balance'] ;
                  ?>
                  <td class="px-4 py-2 border text-green-600 font-semibold">
                    <?= number_format($available_balance, 0) ?>₫
                  </td>


                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['currency']) ?></td>
                <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($wallet['created_at'])) ?></td>
                <td class="px-4 py-2 border text-center"><?= date('d/m/Y', strtotime($wallet['edit_at'])) ?></td>
                
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
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
          <th class="text-left p-3">STT</th>
          <th class="text-left p-3">Danh mục</th>
          <th class="text-left p-3">Số tiền</th>
          <th class="text-left p-3">Ghi chú</th>
          <th class="text-left p-3">Tags</th>
          <th class="text-left p-3">Ảnh</th>
          <th class="text-left p-3">icon</th>
          <th class="text-left p-3">Ngày Tạo</th>
          <th class="text-left p-3">Ngày Chỉnh</th>
          <th class="text-left p-3">Thao tác</th>
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
                <tr class="border-t">
                   <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                  <td class="p-3"><?= htmlspecialchars($transaction['category_name']) ?></td>
                  <td class="p-3 <?= $transaction['type'] === 'expense' ? 'text-red-500' : 'text-green-600' ?>">
                    <?= ($transaction['type'] === 'expense' ? '-' : '+') . number_format($transaction['amount'], 0) ?> VND
                  </td>
                  <td class="p-3"><?= htmlspecialchars($transaction['note']) ?></td>
                  <td class="p-3">
                    <?php if (!empty($transaction['tags'])): ?>
                      <?php foreach (explode(',', $transaction['tags']) as $tag): ?>
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1"><?= htmlspecialchars(trim($tag)) ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-gray-400 text-sm">Không có</span>
                    <?php endif; ?>
                  </td>

                  <td class="p-3">
                    <?php if (!empty($transaction['photo_receipt_url'])): ?>
                      <img src="<?= htmlspecialchars($transaction['photo_receipt_url']) ?>" class="w-10 h-10 rounded" alt="Ảnh" />
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>

                  <td class="p-3">
                    <?= htmlspecialchars($transaction['icon'] ?? '🔖') ?>
                  </td>

                  <td class="p-3"><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                  <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($wallet['edit_at']))  ?></td>
                  <td class="p-3">
                    <a href="edit_transaction.php?id=<?= $transaction['id'] ?>" class="text-blue-600 hover:underline">Sửa</a>
                    <a href="delete_transaction.php?id=<?= $transaction['id'] ?>" class="text-red-600 hover:underline ml-2" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>

        <!-- Nhiều dòng khác -->
      </tbody>
    </table>
  </div>


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

