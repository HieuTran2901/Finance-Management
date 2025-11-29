<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$username = isset($_GET['user']) ? trim($_GET['user']) : '';

if ($group_id <= 0 || empty($username)) {
  die("Thiếu thông tin xoá thành viên.");
}

// Lấy ID người cần xoá và kiểm tra có phải user ảo không
$stmt = $conn->prepare("SELECT id, is_placeholder FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($target_user_id, $is_placeholder);
$stmt->fetch();
$stmt->close();

if (!$target_user_id) {
  die("Không tìm thấy thành viên.");
}

// 1. Xoá bản ghi chia tiền của user này trong tất cả giao dịch nhóm
$stmt = $conn->prepare("
  DELETE sp FROM Shared_Transaction_Participants sp
  JOIN Shared_Transactions st ON sp.shared_transaction_id = st.id
  WHERE st.group_id = ? AND sp.user_id = ?
");
$stmt->bind_param("ii", $group_id, $target_user_id);
$stmt->execute();
$stmt->close();

// 2. Xoá khỏi bảng Group_Members
$stmt = $conn->prepare("DELETE FROM Group_Members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $target_user_id);
$stmt->execute();
$stmt->close();

// 3. Nếu là user ảo → xoá luôn khỏi bảng Users
if ($is_placeholder) {
  $stmt = $conn->prepare("DELETE FROM Users WHERE id = ?");
  $stmt->bind_param("i", $target_user_id);
  $stmt->execute();
  $stmt->close();
}

header("Location: ./group_detail/group_detail.php?id=$group_id");
exit;
?>
