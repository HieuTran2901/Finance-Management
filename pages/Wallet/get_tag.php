<?php
require_once __DIR__ . '/../../module/config.php';

$user_id = $_GET['user_id'] ?? 1; // giả định user_id = 1

$sql = "
SELECT 
  Tags.id AS tag_id,
  Tags.name AS tag_name,
  Tags.created_at,
  Wallets.name AS wallet_name,
  SUM(Transactions.amount) AS total_amount
FROM Tags
LEFT JOIN Transaction_Tags ON Tags.id = Transaction_Tags.tag_id
LEFT JOIN Transactions ON Transaction_Tags.transaction_id = Transactions.id
LEFT JOIN Wallets ON Transactions.wallet_id = Wallets.id
WHERE Tags.user_id = ?
GROUP BY Tags.id, Wallets.name, Tags.created_at
ORDER BY Tags.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$tags = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($tags);
?>
