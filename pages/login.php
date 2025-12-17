<?php
session_start();
require_once '../module/config.php';
require_once '../Func/Notification.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: ../index.php");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';

    if ($username === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $id;
                header("Location: ../index.php?login=success");
                exit;
            } else {
                $error = "Tài khoản hoặc mật khẩu không đúng!";
                header("Location: login.php?login=error"); 
            }
        } else {
            $error = "Tài khoản không tồn tại!";
            header("Location: login.php?login=error");
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <title>Đăng Nhập</title>
</head>

<body>
    <!-- Tuyết rơi -->
    <div class="snow"></div>
    <div class="bottom_snow"></div>

    <!-- Người tuyết bên trái -->
    <div class="snowman">
        <img src="../css/img/onggia.png" alt="Người tuyết">
    </div>

    <!-- Cây thông -->
    <div class="tree-container">
        <img src="../css/img/caythong.png" alt="Cây thông" class="tree-img">
    </div>

    <div class="image-container">
        <div class="form">
            <div id="heading">Đăng nhập</div>
            <form method="POST" action="">
                <div class="field">
                    <input class="input-field" type="text" name="username" placeholder="Tên đăng nhập" required>
                </div>
                <div class="field">
                    <input class="input-field" type="password" name="password" placeholder="Mật khẩu" required>
                </div>
                <div class="link">
                    <a href="forgot_password.php">Quên mật khẩu?</a>
                </div>
                <div class="error-message">
                    <?php if ($error !== ''): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </div>
                <div class="btn">
                    <button class="button1" type="submit">Đăng nhập</button> 
                </div>
            </form>
            <button class="button2" type="submit" onclick="window.location.href='register.php'">Đăng ký</button>
        </div>
    </div> 
    <?php 
        Notification_Notyf('login', null, 'Tài khoản hoặc mật khẩu không đúng!');
    ?>
</body>
</html>


