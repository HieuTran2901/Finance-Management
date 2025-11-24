<?php
$host = 'localhost:3307'; 
$username = 'root';
$password = ''; 
$database = 'dangkydangnhap1';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}
?>

