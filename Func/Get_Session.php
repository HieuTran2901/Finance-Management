<?php
    // 
    function Get_Session($url_config, $url_redirect) {
        session_start();
        if($url_config !== null) {
            require_once htmlspecialchars($url_config);
        }
        else {
            return;
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . htmlspecialchars($url_redirect));
            exit();
        }
        $users_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $users_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_assoc();

        // Trả về mảng chứa dữ liệu người dùng, kết nối và user_id
        return [
        'user' => $users,
        'conn' => $conn,
        'user_id' => $users_id
    ];
    }
?>