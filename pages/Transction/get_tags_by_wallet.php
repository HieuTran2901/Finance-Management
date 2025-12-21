<?php
session_start();
require_once __DIR__ . '/../../module/config.php';

header('Content-Type: application/json; charset=utf-8');

$user_id   = $_SESSION['user_id'] ?? 0;
$wallet_id = intval($_GET['wallet_id'] ?? 0);

if ($user_id <= 0 || $wallet_id <= 0) {
    echo json_encode([]);
    exit;
}

/*
  Lấy các tag đã từng xuất hiện trong các giao dịch của ví này
*/
$sql = "
    SELECT DISTINCT T.name
    FROM Tags T
    JOIN Transaction_Tags TT ON TT.tag_id = T.id
    JOIN Transactions TR ON TR.id = TT.transaction_id
    WHERE TR.wallet_id = ? AND TR.user_id = ?
    ORDER BY T.name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $wallet_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$tags = [];

while ($row = $result->fetch_assoc()) {
    $tags[] = $row['name'];
}

$stmt->close();

echo json_encode($tags);
exit;
