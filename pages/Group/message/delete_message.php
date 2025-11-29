<?php
session_start();
require_once __DIR__ . '/../../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit("Bạn chưa đăng nhập.");
}

$user_id = $_SESSION['user_id'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
  http_response_code(400);
  exit("Thiếu ID tin nhắn.");
}

// 1. Lấy ảnh từ DB trước khi xóa bản ghi
$stmt = $conn->prepare("SELECT image FROM Group_Chat WHERE id = ? AND sender_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->bind_result($image);
$stmt->fetch();
$stmt->close();

// 2. Nếu có ảnh thì xóa file ảnh
if (!empty($image)) {
  $path = __DIR__ . "/uploads/" . $image;
  if (file_exists($path)) {
    unlink($path); // ❌ xóa file vật lý
  }
}

// 3. Xóa tin nhắn khỏi DB
$stmt = $conn->prepare("DELETE FROM Group_Chat WHERE id = ? AND sender_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo "OK";
} else {
  echo "Không thể xóa tin nhắn.";
}
$stmt->close();
