<?php
    require_once '../../Func/Get_Session.php';
    require_once '../../Func/SQL_Cmd.php';
    $sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
    $conn = $sessionData['conn'];

    if(isset($_GET['id'])) {
        $goal_id = $_GET['id'];
        $delete_success = SQL_Execute($conn, "DELETE FROM goals WHERE id = ?", "i", [$goal_id]);
        if($delete_success) {
            header("Location: ./Goals.php?delete=success");
            exit();
        } else {
            echo '<script>alert("Xóa mục tiêu thất bại! Vui lòng thử lại."); window.history.back();</script>';
            exit();
        }
    }
?>