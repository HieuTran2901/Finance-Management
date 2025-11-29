<?php
    // require_once '../vendor/autoload.php';
    require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Đường dẫn đến thư mục chứa file .env
    $dotenv->load();

    // Kết nối cơ sở dữ liệu sử dụng biến môi trường
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset",$user, $pass);  

    // Lấy biến môi trường
    $apiKey = $_ENV['OPENROUTER_API_KEY']; // API key của openrouter
    $referer = $_ENV['URL_REFERER'];
?>