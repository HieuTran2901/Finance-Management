<?php
session_start();
require_once __DIR__ . '/../../../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

if ($group_id <= 0 || $transaction_id <= 0) {
  die("ID nhóm hoặc giao dịch không hợp lệ.");
}

// Xoá các bản ghi phân chia
$stmt = $conn->prepare("DELETE FROM Shared_Transaction_Participants WHERE shared_transaction_id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$stmt->close();

// Xoá giao dịch chính
$stmt = $conn->prepare("DELETE FROM Shared_Transactions WHERE id = ? AND group_id = ?");
$stmt->bind_param("ii", $transaction_id, $group_id);
$stmt->execute();
$stmt->close();

header("Location: ../group_detail.php?id=$group_id");
exit;
?>
