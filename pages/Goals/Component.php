<?php
    // include_once '../../module/config.php';
    include_once '../../Func/SQL_Cmd.php';
   
?>

<?php
   function getRandomCardColor() {
    $colors = [
        ['from' => 'from-indigo-50', 'to' => 'to-white', 'border' => 'border-indigo-100'],
        ['from' => 'from-yellow-50', 'to' => 'to-white', 'border' => 'border-yellow-100'],
        ['from' => 'from-green-50', 'to' => 'to-white', 'border' => 'border-green-100'],
        ['from' => 'from-pink-50', 'to' => 'to-white', 'border' => 'border-pink-100'],
        ['from' => 'from-blue-50', 'to' => 'to-white', 'border' => 'border-blue-100']
    ];


    return $colors[array_rand($colors)];
}
   function EditModal($wallets, $user_id, $conn) {
    echo '
    <!-- Modal form -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-semibold mb-4">Cập nhật mục tiêu</h3>
        <form id="editGoalForm" method="POST" action="update_goals.php">
          <input type="hidden" name="id" id="edit_id">

          <label class="block mb-2 text-sm font-medium">Tên mục tiêu</label>
          <input type="text" name="goal_name" id="edit_name" class="w-full border rounded-lg p-2 mb-3">

          <label class="block mb-2 text-sm font-medium">Số tiền cần đạt</label>
          <input type="number" min="1" name="target_amount" id="edit_target" class="w-full border rounded-lg p-2 mb-3">

          <label class="block mb-2 text-sm font-medium">Đã tiết kiệm</label>
          <input type="number" disabled name="saved_amount" id="edit_saved" class="w-full border rounded-lg p-2 mb-3">

          <label class="block mb-2 text-sm font-medium">Ngày kết thúc</label>
          <input type="date" name="end_date" id="edit_end_date" class="w-full border rounded-lg p-2 mb-4">

          <!-- Chọn ví và số tiền chuyển -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-medium">Chọn ví để chuyển tiền</label>
            <select id="wallet_select" name="wallet_id" class="w-full border border-gray-300 rounded-lg p-2 mb-2">
    ';
            
        // Lặp qua các ví, tạo option
        $current_balance = 0;
        $first_currency = '';
         $sql = "SELECT
                        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
                        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
                        FROM Transactions WHERE wallet_id = ? AND user_id = ?";

        foreach ($wallets as $index => $wallet) {
            $wallet_id = $wallet['id'];
            $result = SQL_Select($conn, $sql, "ii", [$wallet_id, $user_id]);

            $wallet_balance = floatval($wallet['balance']) 
                            + floatval($result[0]['total_income'] ?? 0) 
                            - floatval($result[0]['total_expense'] ?? 0);

            echo '<option value="'.$wallet['id'].'" data-balance="'.$wallet_balance.'" data-currency="'.$wallet['currency'].'">'.$wallet['name'].'</option>';

            // Chỉ lưu số dư ví đầu tiên để hiển thị ban đầu
            if ($index === 0) {
                $current_balance = $wallet_balance;
                $first_currency = $wallet['currency'];
            }
        }

    echo '
            </select>
            <input type="number" name="transfer_amount" id="transfer_amount" min="0" 
                   class="w-full border border-gray-300 rounded-lg p-2"
                   placeholder="Nhập số tiền">
            <p class="text-xs text-green-600 mt-1" id="wallet_balance_info">Số dư ví: '.number_format($current_balance,0,',','.').' '.$first_currency.'</p>
          </div>

          <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeEditModal()" class="px-3 py-1 bg-gray-200 rounded-lg">Hủy</button>
            <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Lưu</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      const walletSelect = document.getElementById("wallet_select");
      const transferInput = document.getElementById("transfer_amount");
      const balanceInfo = document.getElementById("wallet_balance_info");

      // Gán giá trị ban đầu cho input khi modal vừa mở
      const selected = walletSelect.selectedOptions[0];
      if (selected) {
        const balance = selected.getAttribute("data-balance");
        const currency = selected.getAttribute("data-currency");
        transferInput.max = balance;
        balanceInfo.textContent = "Số dư ví: " + Number(balance).toLocaleString("vi-VN") + " " + currency;
     }

      // Cập nhật max và hiển thị số dư khi chọn ví khác
      walletSelect.addEventListener("change", function() {
      const selected = walletSelect.selectedOptions[0];
      const balance = selected.getAttribute("data-balance");
      const currency = selected.getAttribute("data-currency");
      transferInput.max = balance;
      balanceInfo.textContent = "Số dư ví: " + Number(balance).toLocaleString("vi-VN") + " " + currency;
    });


      // Khi nhập số tiền > max, tự động giới hạn
      transferInput.addEventListener("input", function() {
          if(Number(this.value) > Number(this.max)) this.value = this.max;
          if(Number(this.value) < 0) this.value = 0;
      });
    </script>
    ';
}
    function AddGoalModal() {
        echo '
            <!-- Modal form -->
            <div id="addGoalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Thêm mục tiêu mới</h3>
                <form id="addGoalForm" method="POST" action="add_saving.php">
                    <label class="block mb-2 text-sm font-medium">Tên mục tiêu</label>
                    <input type="text" name="goal_name" class="w-full border rounded-lg p-2 mb-3" required>

                    <label class="block mb-2 text-sm font-medium">Số tiền cần đạt</label>
                    <input type="number" min="1" name="target_amount" class="w-full border rounded-lg p-2 mb-3" required>

                    <label class="block mb-2 text-sm font-medium">Ngày kết thúc</label>
                    <input type="date" name="end_date" class="w-full border rounded-lg p-2 mb-4" required>

                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancelBtn" class="bg-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300">Hủy</button>
                        <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Lưu</button>
                    </div>
                </form>
            </div>
            </div>

            <script>
            function validateDates() {
                const start = document.getElementById("edit_start_date").value;
                const end = document.getElementById("edit_end_date").value;

                if(start && end && start > end) {
                    alert("Ngày bắt đầu không được lớn hơn ngày kết thúc!");
                    return false; // ngăn submit
                }
                return true; // cho phép submit
            }
            </script>
        ';
    }

    function AI_Analyze_Modal() {
        echo '
            <!-- Modal AI Phân tích -->
            <div id="aiAnalyzeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div class="bg-white rounded-lg w-full max-w-2xl p-6 relative">
                <!-- Close button -->
                <button id="closeAiModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-lg font-bold">&times;</button>

                <h2 class="text-xl font-semibold text-indigo-600 mb-4">🤖 AI Phân tích kế hoạch chi tiêu</h2>
                <p class="text-sm text-gray-700 mb-4">
                  AI sẽ xem xét các mục tiêu và số dư ví, sau đó gợi ý cách phân bổ chi tiêu hợp lý cho từng mục tiêu.
                </p>

                <!-- Kết quả AI sẽ hiển thị ở đây -->
                <div id="aiAnalyzeResult" class="text-gray-800 text-sm space-y-3 max-h-96 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200 shadow-inner">
                  <!-- Các mục sẽ được AI điền vào đây -->
                </div>


                <div class="mt-4 flex justify-end">
                  <button id="runAiAnalyze" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition">
                    📊 Phân tích ngay
                  </button>
                </div>
              </div>
            </div>
        ';
    }
?>