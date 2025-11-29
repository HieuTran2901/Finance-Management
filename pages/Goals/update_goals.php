<?php
require_once '../../Func/SQL_Cmd.php';
include '../../Func/Get_Session.php';
$sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
$conn = $sessionData['conn'];
$user_id = $sessionData['user_id']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $goal_name = $_POST['goal_name'];
    $target_amount = $_POST['target_amount'];
    $saved_amount = $_POST['saved_amount'];
    $end_date = $_POST['end_date'];
    $wallet_id = $_POST['wallet_id'];
    $transfer_amount = floatval($_POST['transfer_amount'] ?? 0);

    // ✅ Lấy ngày bắt đầu để kiểm tra hợp lệ
    $goal = SQL_Select($conn, "SELECT start_date FROM goals WHERE id = ?", "i", [$id]);
    $start_date = $goal[0]['start_date'] ?? null;

    if ($start_date && strtotime($start_date) > strtotime($end_date)) {
        echo '<script>alert("Ngày kết thúc không được trước ngày bắt đầu! Vui lòng thử lại."); window.history.back();</script>';
        exit();
    }

    // ✅ Nếu có số tiền chuyển, cập nhật số dư ví và mục tiêu
    if ($transfer_amount > 0 && $wallet_id) {

        // 🔹 Lấy số dư gốc của ví
        $wallet = SQL_Select($conn, "SELECT id, balance FROM Wallets WHERE id = ?", "i", [$wallet_id]);
        if (empty($wallet)) {
            echo '<script>alert("Không tìm thấy ví được chọn!"); window.history.back();</script>';
            exit();
        }

        $wallet_id = $wallet[0]['id'];
        $original_balance = floatval($wallet[0]['balance']);

        $sql = "
            SELECT
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
            FROM Transactions
            WHERE wallet_id = ? AND user_id = ?
        ";

        $result = SQL_Select($conn, $sql, "ii", [$wallet_id, $user_id]);
        $total_income = $result[0]['total_income'] ?? 0;
        $total_expense = $result[0]['total_expense'] ?? 0;

        $total_income = floatval($total_income);
        $total_expense = floatval($total_expense);

        // 🔹 Tính số dư thực tế hiện tại
        $available_balance = $original_balance + $total_income - $total_expense;

        // 🔹 Kiểm tra nếu số dư không đủ
        if ($available_balance < $transfer_amount) {
            echo '<script>alert("Số dư ví không đủ! Số dư khả dụng: ' . number_format($available_balance, 0, ',', '.') . '"); window.history.back();</script>';
            exit();
        }

        // 🔹 Trừ tiền khỏi ví (cập nhật bảng Wallets)
        SQL_Execute($conn, "UPDATE Wallets SET balance = balance - ? WHERE id = ?", "di", [$transfer_amount, $wallet_id]);

        // 🔹 Cộng tiền vào mục tiêu
        SQL_Execute($conn, "UPDATE goals SET saved_amount = saved_amount + ? WHERE id = ?", "di", [$transfer_amount, $id]);
    }

    // ✅ Cập nhật thông tin mục tiêu
    $sql = "UPDATE goals SET goal_name = ?, target_amount = ?, end_date = ? WHERE id = ?";
    $success = SQL_Execute($conn, $sql, "sisi", [$goal_name, $target_amount, $end_date, $id]);

    if ($success) {
        header("Location: goals.php?update=success");
        exit();
    } else {
        echo '<script>alert("Cập nhật mục tiêu thất bại! Vui lòng thử lại.");</script>';
        header("Location: goals.php?update=failure");
        exit();
    }
}
?>
