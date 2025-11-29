<?php
require_once __DIR__ . '/../../../module/config.php';

$group_id = $_GET['group_id'] ?? 0;
if ($group_id <= 0) {
  http_response_code(400);
  echo json_encode(["error" => "Missing group ID"]);
  exit;
}

$stmt = $conn->prepare("SELECT gc.id, gc.sender_id, gc.message, gc.image, gc.sent_at, u.username 
                        FROM Group_Chat gc
                        JOIN Users u ON gc.sender_id = u.id
                        WHERE gc.group_id = ?
                        ORDER BY gc.sent_at ASC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
  $row['image'] = $row['image'] ?? null;
  $messages[] = $row;
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($messages);
