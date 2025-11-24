<?php
require_once __DIR__ . '/../../module/config.php';

$id = $_GET['id'] ?? null;

if ($id) {
  // Xoá Transaction_Tags theo tag_id
  $stmt1 = $conn->prepare("DELETE FROM Transaction_Tags WHERE tag_id = ?");
  $stmt1->bind_param("i", $id);
  $stmt1->execute();
  $stmt1->close();

  // Xoá Tag chính
  $stmt2 = $conn->prepare("DELETE FROM Tags WHERE id = ?");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $stmt2->close();
}

header('Location: Wallet.php');
exit;
