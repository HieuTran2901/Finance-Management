<?php
session_start();
require_once __DIR__ . '/../../../module/config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$group_id = $_POST['group_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if ($user_id <= 0 || $group_id <= 0) {
  http_response_code(400);
  exit("Missing info");
}

$image = null;
if (!empty($_FILES['image']['name'])) {
  $upload_dir = __DIR__ . '/uploads/';
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

  $file_name = time() . '_' . basename($_FILES['image']['name']);
  $target_path = $upload_dir . $file_name;
  if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
    $image = "$file_name";
  }
}

$stmt = $conn->prepare("INSERT INTO Group_Chat (group_id, sender_id, message, image, sent_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiss", $group_id, $user_id, $message, $image);
$stmt->execute();
$stmt->close();

echo "OK";
