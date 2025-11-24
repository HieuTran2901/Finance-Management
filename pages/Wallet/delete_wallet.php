<?php
require_once __DIR__ . '/../../module/config.php';

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {

    // Kiểm tra xem có tags liên quan đến các transaction của ví này không
    $sql = "
        SELECT 1 
        FROM transaction_tags tt
        JOIN transactions t ON t.id = tt.transaction_id
        WHERE t.wallet_id = ?
        LIMIT 1
    ";
    $stmtCheck = $conn->prepare($sql);
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows > 0) {
        // Có tag liên kết -> không cho xoá
        echo "<script>
            alert('Không thể xoá ví này vì có tag đang sử dụng trong các giao dịch.');
            window.location.href = 'Wallet.php';
        </script>";
        exit;
    }

    $stmtCheck->close();

    // Xoá các giao dịch liên quan
    $stmt1 = $conn->prepare("DELETE FROM transactions WHERE wallet_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // Xoá ví
    $stmt2 = $conn->prepare("DELETE FROM wallets WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();
}

header('Location: Wallet.php');
exit;
