<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ✅ Lấy biến môi trường + chống lỗi thiếu DB_PORT
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? 3307; // ✅ Nếu .env thiếu thì vẫn dùng 3307
$dbname = $_ENV['DB_NAME'] ?? 'dangkydangnhap1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8';

// ✅ GẮN PORT VÀO DSN
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("LỖI KẾT NỐI DATABASE: " . $e->getMessage());
}

// Lấy biến môi trường khác
$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';
$referer = $_ENV['URL_REFERER'] ?? '';
?>
