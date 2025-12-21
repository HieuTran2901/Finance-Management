<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinFlow - Quản lý tài chính cá nhân thông minh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"> <!-- AOS CSS -->
    <link rel="stylesheet" href="../../css/fadein.css">    <!-- Custom Floating Animations -->
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<!-- Modal -->
<div id="comingSoonModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center relative animate-fade-in">
    <h2 class="text-2xl font-semibold text-indigo-700 mb-3">Thông báo</h2>
    <p class="text-gray-700 mb-6">Tính năng này đang được phát triển. Vui lòng quay lại sau!</p>
    <button id="closeModal" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition">Đóng</button>
  </div>
</div>

<body class="bg-gray-50 text-gray-800 antialiased"> <!-- antialiased để làm mượt chữ -->
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50"> <!-- Tăng shadow -->
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <img src="https://img.icons8.com/ios-filled/50/4F46E5/wallet--v1.png" class="w-8 h-8 filter drop-shadow-md" alt="Logo" />
                <span class="text-2xl font-extrabold text-indigo-700 drop-shadow-sm">FinFlow</span>
            </div>
            <nav class="space-x-6 hidden md:block">
                <a href="#features" class="text-gray-700 hover:text-indigo-600 font-medium transition duration-200">Tính năng</a>
                <a href="#pricing" class="text-gray-700 hover:text-indigo-600 font-medium transition duration-200">Gói dịch vụ</a>
                <a href="#testimonials" class="text-gray-700 hover:text-indigo-600 font-medium transition duration-200">Đánh giá</a> <!-- Thêm mục mới -->
                <a href="#contact" class="text-gray-700 hover:text-indigo-600 font-medium transition duration-200">Liên hệ</a>
                <a href="../login.php" class="bg-indigo-600 text-white px-5 py-2 rounded-full hover:bg-indigo-700 transition duration-200 shadow-md">Đăng nhập</a> <!-- Nút bo tròn -->
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-indigo-50 to-blue-100 py-16 md:py-24 relative overflow-hidden"> <!-- Đổi gradient và giảm padding một chút -->
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-12"> <!-- Chia làm 2 cột trên màn hình lớn -->
            <!-- Left Column: Text Content -->
            <div class="md:w-1/2 text-center md:text-left z-10 relative">
                <h1 class="text-4xl md:text-6xl font-extrabold text-indigo-800 leading-tight mb-4" data-aos="fade-right" data-aos-duration="1000">Kiểm soát tài chính của bạn <span class="text-indigo-600">trong tầm tay</span></h1>
                <p class="mt-4 text-lg md:text-xl text-gray-600 max-w-3xl mx-auto md:mx-0" data-aos="fade-right" data-aos-delay="200" data-aos-duration="1000">FinFlow giúp bạn theo dõi thu nhập, chi tiêu, quản lý ví và ngân sách một cách thông minh, biến việc quản lý tiền bạc thành hành trình đơn giản và hiệu quả.</p>
                <a href="../../dangkydangnhap/login.php" class="mt-8 inline-block bg-indigo-600 text-white px-8 py-4 rounded-full shadow-lg hover:bg-indigo-700 transition duration-300 transform hover:-translate-y-1 hover:scale-105 animate-button-pulse" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">Bắt đầu ngay</a>
            </div>

            <!-- Right Column: Main Illustration/Image -->
            <div class="md:w-1/2 flex justify-center items-center mt-10 md:mt-0 z-10 relative" data-aos="fade-left" data-aos-delay="300" data-aos-duration="1000">
                <!-- Đây là placeholder cho hình ảnh minh họa chính. Bạn nên thay thế bằng ảnh thực tế đẹp mắt. -->
                <img src="../../img/finance.jpg" alt="Financial Management Dashboard" class="max-w-full h-auto rounded-xl shadow-2xl transition-transform duration-500 hover:scale-105" />
            </div>
        </div>
        <!-- Thêm các hình ảnh minh họa nhỏ bay lơ lửng -->
        <img src="https://placehold.co/100x100/B2A4FF/FFFFFF?text=Coin" class="absolute top-[10%] left-[5%] opacity-20 animate-float" data-aos="fade-right">
        <img src="https://placehold.co/80x80/9333ea/FFFFFF?text=Chart" class="absolute bottom-[10%] right-[5%] opacity-20 animate-float-delay" data-aos="fade-left">
        <img src="https://placehold.co/60x60/6366f1/FFFFFF?text=Wallet" class="absolute top-[70%] left-[25%] opacity-20 animate-float-more" data-aos="fade-up-right">
        <img src="https://placehold.co/70x70/E879F9/FFFFFF?text=Money" class="absolute top-[20%] right-[20%] opacity-20 animate-float" data-aos="fade-down-left">
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-indigo-800 mb-12" data-aos="fade-up">Tính năng nổi bật</h2>
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <!-- Feature Card 1: Quản lý ví thông minh -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="100">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <!-- Sử dụng ảnh minh họa (placeholder) -->
                        <img src="../../img/wallet.jpg" alt="Wallet Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- Hoặc dùng Font Awesome icon -->
                        <!-- <i class="fa-solid fa-wallet text-5xl text-indigo-600 transition-colors duration-300 group-hover:text-indigo-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Quản lý ví thông minh</h3>
                    <p class="text-gray-600">Theo dõi số dư và giao dịch của từng ví riêng biệt một cách rõ ràng và trực quan.</p>
                </div>

                <!-- Feature Card 2: Thống kê & Báo cáo -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="200">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <img src="../../img/chart.png" alt="Chart Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- <i class="fa-solid fa-chart-line text-5xl text-blue-600 transition-colors duration-300 group-hover:text-blue-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Thống kê & Báo cáo</h3>
                    <p class="text-gray-600">Biểu đồ trực quan và báo cáo chi tiết giúp bạn kiểm soát và tối ưu chi tiêu hàng tháng.</p>
                </div>

                <!-- Feature Card 3: Bảo mật tuyệt đối -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="300">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <img src="../../img/security.png" alt="Security Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- <i class="fa-solid fa-shield-halved text-5xl text-green-600 transition-colors duration-300 group-hover:text-green-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Bảo mật tuyệt đối</h3>
                    <p class="text-gray-600">Dữ liệu được mã hóa và bảo vệ bằng các chuẩn bảo mật hiện đại nhất.</p>
                </div>

                <!-- Feature Card 4: Quản lý Nhóm -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="400">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <img src="../../img/group.png" alt="Group Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- <i class="fa-solid fa-users text-5xl text-yellow-600 transition-colors duration-300 group-hover:text-yellow-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Quản lý Nhóm</h3>
                    <p class="text-gray-600">Chia sẻ và quản lý tài chính nhóm với bạn bè hoặc gia đình một cách dễ dàng.</p>
                </div>

                <!-- Feature Card 5: Thiết lập Ngân sách -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="500">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <img src="../../img/budget.png" alt="Budget Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- <i class="fa-solid fa-bullseye text-5xl text-purple-600 transition-colors duration-300 group-hover:text-purple-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Thiết lập Ngân sách</h3>
                    <p class="text-gray-600">Đặt ra và theo dõi ngân sách cho từng danh mục, giúp bạn chi tiêu có kế hoạch.</p>
                </div>

                <!-- Feature Card 6: Trợ lý tài chính AI -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center justify-center transform transition-transform duration-300 hover:scale-105 group" data-aos="fade-up" data-aos-delay="600">
                    <div class="relative w-20 h-20 mb-6 bg-indigo-100 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-200">
                        <img src="../../img/AI.png" alt="AI Icon" class="w-14 h-14 object-contain filter drop-shadow-md transition-transform duration-300 group-hover:scale-110">
                        <!-- <i class="fa-solid fa-robot text-5xl text-indigo-600 transition-colors duration-300 group-hover:text-indigo-700"></i> -->
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Trợ lý tài chính AI</h3>
                    <p class="text-gray-600">Hỗ trợ bạn dự đoán xu hướng chi tiêu, tối ưu ngân sách và trả lời thắc mắc tài chính một cách thông minh.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="bg-indigo-700 py-16 text-white text-center">
        <div class="max-w-4xl mx-auto px-6" data-aos="zoom-in" data-aos-duration="1000">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Sẵn sàng để kiểm soát tài chính của bạn?</h2>
            <p class="text-lg mb-8 opacity-90">Tham gia FinFlow ngay hôm nay và bắt đầu hành trình quản lý tài chính hiệu quả!</p>
            <a href="../../dangkydangnhap/register.php" class="inline-block bg-white text-indigo-700 px-8 py-4 rounded-full shadow-lg hover:bg-gray-200 transition duration-300 transform hover:-translate-y-1 hover:scale-105 font-semibold">Đăng ký miễn phí</a>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-indigo-800 mb-12" data-aos="fade-up">Chọn gói phù hợp với bạn</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col transform transition-transform duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Miễn phí</h3>
                    <p class="text-4xl font-extrabold mb-4 text-indigo-600">0 đ</p>
                    <p class="text-gray-600 mb-6">Dành cho người mới bắt đầu</p>
                    <ul class="text-left space-y-2 text-gray-700 text-base flex-grow">
                        <li>✔ Theo dõi thu/chi cơ bản</li>
                        <li>✔ Báo cáo tổng quan</li>
                        <li>✔ 1 ví cá nhân</li>
                        <li>✔ Hỗ trợ cộng đồng</li>
                    </ul>
                    <a href="../../dangkydangnhap/login.php" class="mt-8 inline-block w-full bg-indigo-600 text-white py-3 rounded-full hover:bg-indigo-700 transition duration-200 shadow-md font-semibold">Dùng thử ngay</a>
                </div>
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white p-8 rounded-xl shadow-2xl border-2 border-indigo-700 flex flex-col transform transition-transform duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-2xl font-bold mb-4">Chuyên nghiệp</h3>
                    <p class="text-4xl font-extrabold mb-4 text-white">99.000 đ<span class="text-lg font-normal">/tháng</span></p>
                    <p class="text-indigo-100 mb-6">Kiểm soát tài chính toàn diện</p>
                    <ul class="text-left space-y-2 text-white text-base flex-grow">
                        <li>✔ Tất cả tính năng miễn phí</li>
                        <li>✔ Không giới hạn số ví</li>
                        <li>✔ Thống kê & báo cáo nâng cao</li>
                        <li>✔ Chatbox AI hỗ trợ</li>
                        <li>✔ Hỗ trợ ưu tiên 24/7</li>
                    </ul>
                    <a href="#" class="js-coming-soon mt-8 inline-block w-full bg-white text-indigo-700 py-3 rounded-full hover:bg-gray-100 transition duration-200 shadow-md font-semibold">Đăng ký ngay</a>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col transform transition-transform duration-300 hover:scale-105" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Doanh nghiệp</h3>
                    <p class="text-4xl font-extrabold mb-4 text-gray-700">Liên hệ</p>
                    <p class="text-gray-600 mb-6">Giải pháp tùy chỉnh cho doanh nghiệp</p>
                    <ul class="text-left space-y-2 text-gray-700 text-base flex-grow">
                        <li>✔ Tất cả tính năng chuyên nghiệp</li>
                        <li>✔ Quản lý nhóm & nhân viên</li>
                        <li>✔ Giao diện & tính năng tùy biến</li>
                        <li>✔ Báo cáo tài chính chuyên sâu</li>
                        <li>✔ Hỗ trợ riêng 24/7</li>
                    </ul>
                    <a href="#" class="js-coming-soon mt-8 inline-block w-full bg-indigo-600 text-white py-3 rounded-full hover:bg-indigo-700 transition duration-200 shadow-md font-semibold">Liên hệ</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section (Mới) -->
    <section id="testimonials" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-indigo-800 mb-12" data-aos="fade-up">Khách hàng nói gì về FinFlow</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center text-center" data-aos="fade-right" data-aos-delay="100">
                    <img src="https://placehold.co/80x80/6366f1/FFFFFF/png?text=JD" alt="John Doe" class="w-20 h-20 rounded-full mb-4 object-cover border-4 border-indigo-200">
                    <p class="text-lg font-medium text-gray-700 mb-3">"FinFlow đã thay đổi cách tôi quản lý tiền bạc. Thật dễ sử dụng và hiệu quả!"</p>
                    <p class="text-indigo-600 font-semibold">- John Doe, Freelancer</p>
                </div>
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col items-center text-center" data-aos="fade-left" data-aos-delay="200">
                    <img src="https://placehold.co/80x80/9333ea/FFFFFF/png?text=AS" alt="Alice Smith" class="w-20 h-20 rounded-full mb-4 object-cover border-4 border-purple-200">
                    <p class="text-lg font-medium text-gray-700 mb-3">"Với FinFlow, tôi cuối cùng cũng hiểu tiền của mình đang đi đâu. Rất khuyến khích!"</p>
                    <p class="text-purple-600 font-semibold">- Alice Smith, Chuyên gia Marketing</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-indigo-800 mb-6">Liên hệ với chúng tôi</h2>
            <p class="mb-6 text-lg text-gray-600">Bạn có câu hỏi hoặc cần hỗ trợ? Đừng ngần ngại liên hệ với đội ngũ FinFlow.</p>
            <a href="mailto:support@finflow.vn" class="text-indigo-600 hover:text-indigo-800 font-semibold text-lg underline transition duration-200">support@finflow.vn</a>
            <p class="mt-4 text-gray-500">Hoặc gọi cho chúng tôi: <span class="font-medium text-gray-700">0123 456 789</span></p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 py-8 text-center text-sm text-gray-300"> <!-- Footer tối màu -->
        <div class="max-w-7xl mx-auto px-6">
            <p class="mb-2">&copy; 2025 FinFlow. All rights reserved.</p>
            <div class="flex justify-center space-x-4">
                <a href="#" class="hover:text-white transition duration-200">Chính sách bảo mật</a>
                <a href="#" class="hover:text-white transition duration-200">Điều khoản dịch vụ</a>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script> <!-- AOS JS -->
    <script>
        AOS.init({
            once: true, // Chỉ animate khi cuộn qua lần đầu tiên
            mirror: false // Không lặp lại animation khi cuộn lên
        });

        // Optional: Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
    
    <script>
        const modal = document.getElementById("comingSoonModal");
        const closeModal = document.getElementById("closeModal");

        document.querySelectorAll(".js-coming-soon").forEach(btn => {
            btn.addEventListener("click", function (e) {
            e.preventDefault();
            modal.classList.remove("hidden");
            });
        });

        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        // Đóng modal khi click ra ngoài hộp
        modal.addEventListener("click", (e) => {
            if (e.target === modal) modal.classList.add("hidden");
        });
    </script>
    <script>
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
            window.location.reload();
            }
        });
    </script>

</body>
</html>