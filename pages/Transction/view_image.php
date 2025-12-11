<?php
$src = isset($_GET['src']) ? $_GET['src'] : null;
if (!$src || !file_exists($src)) {
    die("Ảnh không tồn tại.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Ảnh Hóa Đơn</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
  <div class="bg-white p-6 rounded shadow-lg">
    <h2 class="text-xl font-bold text-center mb-4">Ảnh Hóa Đơn</h2>
    <img src="<?= htmlspecialchars($src) ?>" alt="Hóa đơn" class="max-w-full max-h-[80vh] rounded shadow">
    <div class="text-center mt-4">
      <a href="javascript:window.close()" class="text-blue-600 hover:underline">Đóng</a>
    </div>
  </div>
</body>
</html>