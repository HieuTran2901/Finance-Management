<?php
require_once __DIR__ . '/../../module/config.php';

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập trước.");
}

$transaction_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($transaction_id) {
    // Xoá các liên kết tag (Transaction_Tags)
    $stmt1 = $conn->prepare("DELETE FROM Transaction_Tags WHERE transaction_id = ?");
    $stmt1->bind_param("i", $transaction_id);
    $stmt1->execute();
    $stmt1->close();

    // Xoá giao dịch (chỉ nếu thuộc về user này)
    $stmt2 = $conn->prepare("DELETE FROM Transactions WHERE id = ? AND user_id = ?");
    $stmt2->bind_param("ii", $transaction_id, $user_id);
    $stmt2->execute();
    $stmt2->close();
}

header('Location: Transaction.php'); // Quay lại danh sách giao dịch
exit;
