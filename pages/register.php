<?php
require_once '../module/config.php';
include "../Func/Notification.php";

$fieldError = [];
$username = $email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];

    if (empty($username)) {
        $fieldError['username'] = "Vui lòng nhập tên đăng nhập!";
    } elseif (empty($email)) {
        $fieldError['email'] = "Vui lòng nhập email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldError['email'] = "Email không hợp lệ!";
    } elseif (empty($password)) {
        $fieldError['password'] = "Vui lòng nhập mật khẩu!";
    } elseif (strlen($password) < 8) {
        $fieldError['password'] = "Mật khẩu phải có ít nhất 8 ký tự!";
    } elseif (empty($confirm)) {
        $fieldError['confirm'] = "Vui lòng xác nhận mật khẩu!";
    } elseif ($password !== $confirm) {
        $fieldError['confirm'] = "Mật khẩu xác nhận không khớp!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $fieldError['username'] = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $fieldError['general'] = "Đăng ký thất bại!";
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
    font-size: 13px;
    font-weight: 600;
    color: yellow;
    margin: 4px 0 10px 6px;
}
</style>

<body>

<!-- Tuyết rơi -->
<div class="snow"></div>
<div class="bottom_snow"></div>

<!-- Người tuyết -->
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

        <form method="POST">

            <!-- Username -->
            <div class="field">
                <input class="input-field" type="text" name="username"
                       value="<?= htmlspecialchars($username) ?>"
                       placeholder="Tên đăng nhập" required>
            </div>
            <?php if (!empty($fieldError['username'])): ?>
                <div class="error-message"><?= $fieldError['username'] ?></div>
            <?php endif; ?>

            <!-- Email -->
            <div class="field">
                <input class="input-field" type="email" name="email"
                       value="<?= htmlspecialchars($email) ?>"
                       placeholder="Email" required>
            </div>
            <?php if (!empty($fieldError['email'])): ?>
                <div class="error-message"><?= $fieldError['email'] ?></div>
            <?php endif; ?>

            <!-- Password -->
            <div class="field">
                <input class="input-field" type="password" name="password"
                       placeholder="Mật khẩu" required>
            </div>
            <?php if (!empty($fieldError['password'])): ?>
                <div class="error-message"><?= $fieldError['password'] ?></div>
            <?php endif; ?>

            <!-- Confirm -->
            <div class="field">
                <input class="input-field" type="password" name="confirm_password"
                       placeholder="Xác nhận mật khẩu" required>
            </div>
            <?php if (!empty($fieldError['confirm'])): ?>
                <div class="error-message"><?= $fieldError['confirm'] ?></div>
            <?php endif; ?>

            <div class="btn register-btn">
                <button class="button1" type="submit">Đăng ký</button>
            </div>

            <?php if (!empty($fieldError['general'])): ?>
                <div class="error-message" style="text-align:center">
                    <?= $fieldError['general'] ?>
                </div>
            <?php endif; ?>

        </form>

        <div class="link register-link">
            <p>Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>

    </div>
</div>

</body>
</html>
