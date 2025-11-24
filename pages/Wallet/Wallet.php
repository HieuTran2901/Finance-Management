<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wallet</title>
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
        <a href="../../index.php" class="flex items-center gap-2 p-2 rounded-md font-medium">
          <span><i class="fa-solid fa-house"></i></span> Dashboard
        </a>
        <a href="#" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-map-pin"></i> Pinned
        </a>
        <a href="#" class="flex items-center bg-gray-100 gap-2 p-2 hover:bg-gray-100 rounded-md">
          <i class="fa-solid fa-wallet"></i> Wallets
        </a>
        <a href="../Transction/Transaction.php" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-md">
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

    <!-- Main content -->
     
    <main class="flex-1 p-6">
      <?php
          session_start();
          require_once __DIR__ . '/../../module/config.php';
          $user_id = $_SESSION['user_id']; // Giả sử bạn đã lưu user_id khi đăng nhập
          if (!isset($_SESSION['user_id'])) {
              die("Vui lòng đăng nhập trước.");
             
          }
          
          
          
          // Truy vấn tags
          $sql = "
          SELECT 
            Tags.id AS tag_id,
            Tags.name AS tag_name,
            Tags.created_at,
            Tags.edit_at,
            Wallets.name AS wallet_name,
            SUM(Transactions.amount) AS total_amount
          FROM Tags
          LEFT JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
          LEFT JOIN Transactions ON Transaction_Tags.transaction_id = Transactions.id
          LEFT JOIN Wallets ON Transactions.wallet_id = Wallets.id
          WHERE Tags.user_id = ?
          GROUP BY Tags.id, Wallets.name, Tags.created_at ,Tags.edit_at
          ORDER BY Tags.created_at,Tags.edit_at DESC
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
          


          // $sql_used = "
          //             SELECT 
          //               Transactions.wallet_id,
          //               SUM(Transactions.amount) AS used_amount
          //             FROM Transactions
          //             INNER JOIN Transaction_Tags ON Transactions.id = Transaction_Tags.transaction_id
          //             INNER JOIN Tags ON Transaction_Tags.tag_id = Tags.id
          //             WHERE Tags.user_id = ?
          //             GROUP BY Transactions.wallet_id
          //           ";
          //           $stmt_used = $conn->prepare($sql_used);
          //           $stmt_used->bind_param("i", $user_id);
          //           $stmt_used->execute();
          //           $result_used = $stmt_used->get_result();
          //           $used_per_wallet = [];
          //           while ($row = $result_used->fetch_assoc()) {
          //               $used_per_wallet[$row['wallet_id']] = $row['used_amount'];
          //           }

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
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Danh sách Ví</h2>
          <form method="POST" action="" class="flex gap-2">
            <input type="hidden" name="create_wallet" value="1">
            <input type="text" name="name" id="wallet-name" placeholder="Tên ví" class="border px-2 py-1 rounded" required
            oninvalid="this.setCustomValidity('Vui lòng nhập tên ví.')"
            oninput="this.setCustomValidity('')">
            <input type="text" name="type" placeholder="Loại ví" class="border px-2 py-1 rounded" required
            oninvalid="this.setCustomValidity('Vui lòng nhập Loại ví.')"
            oninput="this.setCustomValidity('')">
            <input type="text" name="currency" placeholder="VNĐ, USD..." class="border px-2 py-1 rounded" required
            oninvalid="this.setCustomValidity('Vui lòng nhập đơn vị tiền tệ.')"
            oninput="this.setCustomValidity('')">
            <input type="number" step="500" name="balance" id="wallet-balance" placeholder="Số dư" class="border px-2 py-1 rounded" required
            oninvalid="this.setCustomValidity('Vui lòng nhập số tiền hợp lệ.')"
            oninput="checkBalance(this)">
            <button type="submit" class="bg-green-500 text-white px-4 py-1 rounded hover:bg-green-600">Thêm</button>
          </form>
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
                <th class="px-4 py-2 border">Ngày chỉnh</th>
                <th class="px-4 py-2 border text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($wallets as $index => $wallet): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['name']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['type']) ?></td>

                <?php
                    // $used = $used_per_wallet[$wallet['id']] ?? 0;
                     $available_balance = $wallet['balance'] //- $used;
                  ?>
                  <td class="px-4 py-2 border text-green-600 font-semibold">
                    <?= number_format($available_balance, 0) ?>₫
                  </td>


                <td class="px-4 py-2 border"><?= htmlspecialchars($wallet['currency']) ?></td>

                <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($wallet['created_at'])) ?></td>
                <td class="px-4 py-2 border">
                  <?=  date('d/m/Y', strtotime($wallet['edit_at']))  ?>
                </td>
                  <td>
                  <a href="edit_wallet.php?id=<?= $wallet['id'] ?>" class="text-blue-500 hover:underline mx-2">
                    <i class="fas fa-edit"></i>Edit
                  </a>
                  <a href="delete_wallet.php?id=<?= $wallet['id'] ?>" onclick="return confirm('Bạn có chắc muốn xoá ví này?')" class="text-red-500 hover:underline mx-2">
                    <i class="fas fa-trash-alt"></i>Delete
                  </a>
                </td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>



      <div class="bg-white rounded-md shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Danh sách Tags</h2>
          <a href="add_tag.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2">
            <i class="fas fa-plus"></i> Thêm Tag
          </a>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full table-auto border border-gray-200">
            <thead class="bg-gray-100 text-left text-sm font-medium text-gray-700">
              <tr>
                <th class="px-4 py-2 border">STT</th>
                <th class="px-4 py-2 border">Tên Tag</th>
                <th class="px-4 py-2 border">Tổng tiền</th>
                <th class="px-4 py-2 border">Ví</th>
                <th class="px-4 py-2 border">Ngày tạo</th>
                <th class="px-4 py-2 border">Ngày chỉnh</th>
                <th class="px-4 py-2 border text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <?php foreach ($data as $index => $row): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border"><?= $index + 1 ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['tag_name']) ?></td>
                <td class="px-4 py-2 border text-green-600 font-semibold"><?= number_format($row['total_amount'] ?? 0) ?>₫</td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['wallet_name'] ?? 'Không xác định') ?></td>
                <td class="px-4 py-2 border"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                <td class="px-4 py-2 border">
                 <?= date('d/m/Y', strtotime($row['edit_at']))?>
                </td>

                <td class="px-4 py-2 border text-center">
                  <a href="edit_tag.php?id=<?= $row['tag_id'] ?>" class="text-blue-500 hover:underline mx-2">
                    <i class="fas fa-edit"></i>Edit
                  </a>
                  <a href="delete_tag.php?id=<?= $row['tag_id'] ?>" onclick="return confirm('Bạn có chắc muốn xoá?')" class="text-red-500 hover:underline mx-2">
                    <i class="fas fa-trash-alt"></i>Delete
                  </a>
                </td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>



<script>
  function checkBalance(input) {
    if (parseFloat(input.value) < 0) {
      input.setCustomValidity("Không được nhập số âm.");
    } else {
      input.setCustomValidity("");
    }
  }
</script>
