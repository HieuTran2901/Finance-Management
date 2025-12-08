<?php
require_once '../module/config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm = trim($_POST["confirm"]);

    // Kiểm tra username + email có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {

        // Kiểm tra mật khẩu nhập lại
        if ($password !== $confirm) {
            $message = "<span '>Mật khẩu xác nhận không khớp!</span>";
        } else {
            // Hash mật khẩu
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Update mật khẩu mới
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ? AND email = ?");
            $stmt->bind_param("sss", $hashed, $username, $email);
            $stmt->execute();

            $message = "<span ;'>Đổi mật khẩu thành công! <a href='login.php'>Đăng nhập</a></span>";
        }
    } else {
        $message = "<span ;'>Tên người dùng hoặc email không đúng!</span>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/style.css">
<title>Quên mật khẩu</title>
</head>
<body>

<div class="snow"></div>
<div class="bottom_snow"></div>

<div class="snowman">
        <img src="../css/img/snowman.png" alt="Người tuyết">
    </div>

    <!-- Cây thông -->
    <div class="tree-container">
        <img src="../css/img/christmas_tree.png" alt="Cây thông" class="tree-img">
    </div>

<div class="image-container">
    
    <div class="form forgot-form">
        <a href="login.php" class="close-btn">✖</a>

        <div id="heading">Quên mật khẩu</div>

        <form method="POST">
            <div class="field">
                <input class="input-field" type="text" name="username" placeholder="Tên đăng nhập" required>
            </div>

            <div class="field">
                <input class="input-field" type="email" name="email" placeholder="Email" required>
            </div>

            <div class="field">
                <input class="input-field" type="password" name="password" placeholder="Mật khẩu mới" required>
            </div>

            <div class="field">
                <input class="input-field" type="password" name="confirm" placeholder="Xác nhận mật khẩu" required>
            </div>

            <div class="btn forgot-btn">
                <button class="button1" type="submit">Đổi mật khẩu</button>
            </div>
        </form>

        <!-- Thông báo -->
        <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

    </div>
</div>

</body>
</html>
