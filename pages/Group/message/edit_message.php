<?php
session_start();
require_once __DIR__ . '/../../../module/config.php';

$id = intval($_POST['id']);
$user_id = $_SESSION['user_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if ($id <= 0 || $user_id <= 0) {
  http_response_code(400);
  exit("Thiếu thông tin.");
}

// 1. Lấy tin nhắn cũ để kiểm tra và xoá ảnh cũ nếu cần
$stmt = $conn->prepare("SELECT image FROM Group_Chat WHERE id = ? AND sender_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->bind_result($oldImage);
$stmt->fetch();
$stmt->close();

$newImageName = $oldImage;

// 2. Nếu có file ảnh mới được gửi lên → xử lý cập nhật ảnh
if (!empty($_FILES['image']['name'])) {
  $upload_dir = __DIR__ . '/uploads/';
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

  // Xóa ảnh cũ nếu có
  if ($oldImage && file_exists($upload_dir . $oldImage)) {
    unlink($upload_dir . $oldImage);
  }

  // Lưu ảnh mới
  $newFile = time() . '_' . basename($_FILES['image']['name']);
  $target_path = $upload_dir . $newFile;
  if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
    $newImageName = $newFile;
  } else {
    http_response_code(500);
    exit("Không thể lưu ảnh mới.");
  }
}

// 3. Cập nhật tin nhắn (văn bản + ảnh)
$stmt = $conn->prepare("UPDATE Group_Chat SET message = ?, image = ? WHERE id = ? AND sender_id = ?");
$stmt->bind_param("ssii", $message, $newImageName, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo "OK";
} else {
  echo "Không thể sửa tin nhắn.";
}
$stmt->close();
