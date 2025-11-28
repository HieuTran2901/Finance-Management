<?php
require_once __DIR__ . "/../Api/apiconfig.php";

$host = $_ENV['DB_HOST'] . ":3307";
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}
?>

