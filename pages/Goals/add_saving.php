<?php
    require_once '../../Func/Get_Session.php';
    $sessionData = Get_Session('../../module/config.php', '../../dangkydangnhap/login.php');
    $conn = $sessionData['conn'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lấy dữ liệu từ form
        $user_id = $_SESSION['user_id'];
        $goal_name = trim($_POST['goal_name']);
        $target_amount = floatval($_POST['target_amount']);
        $end_date = $_POST['end_date'];
        $description = trim($_POST['description']);

        // Ngày bắt đầu là ngày hiện tại
        $start_date = date('Y-m-d');

        // Mặc định saved_amount = 0 khi mới tạo mục tiêu
        $saved_amount = 0;

        // Mặc định trạng thái là "đang thực hiện"
        $status = 'in_progress';

        // Thêm vào CSDL
        $sql = "INSERT INTO goals (user_id, goal_name, target_amount, saved_amount, start_date, end_date, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("isddssss", $user_id, $goal_name, $target_amount, $saved_amount, $start_date, $end_date, $description, $status);
            if ($stmt->execute()) {
                // ✅ Thành công → chuyển hướng về trang Goals
                header("Location: ./Goals.php?success=1");
                exit();
            } else {
                echo "❌ Lỗi khi thêm mục tiêu: " . $stmt->error;
            }
        } else {
            echo "❌ Lỗi truy vấn: " . $conn->error;
        }
    }

?>