<?php
require_once '../module/config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } elseif (strlen($password) < 8) {
        $error = "Mật khẩu phải có ít nhất 8 ký tự!";
    } elseif ($password !== $confirm) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Đăng ký thất bại!";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <title>Đăng ký</title>
</head>
<style>
.error-message {
    font-size: 15px;
    font-weight: 600;

    background: linear-gradient(135deg, #0ba60bff, #5a0d0dff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    /* hỗ trợ thêm */
    background-clip: text;
    color: transparent;

    text-align: center;
    margin-top: 10px;
}


</style>
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
    <div class="form register-form">
        <div id="heading">Đăng ký</div>
        <form method="POST" action="">
            <div class="field">
                <input class="input-field" type="text" name="username" placeholder="Tên đăng nhập" required>
            </div>
            <div class="field">
                <input class="input-field" type="email" name="email" placeholder="Email" required>
            </div>
            <div class="field">
                <input class="input-field" type="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <div class="field">
                <input class="input-field" type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            </div>

            <div class="btn register-btn">
                <button class="button1" type="submit">Đăng ký</button>
            </div>
        </form>
        <div class="link register-link">
            <p>Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
        <?php if (!empty($error)) : ?>
            <div class="error-message <?= $error_type ?>">
                <?= $error ?>
            </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
