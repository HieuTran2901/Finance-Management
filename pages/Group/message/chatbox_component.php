<!-- Chatbox -->
<button id="toggleBtn" class="fixed bottom-4 right-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-xl z-50 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 animate-pulse-custom">
   <img id="chatIcon" src="../../../img/chat-box.png" class="w-6"/>
</button>

<div id="chatBox" class="fixed bottom-20 right-4 w-80 z-50 transform scale-0 opacity-0 transition-all duration-300 origin-bottom-right pointer-events-none">
  <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col h-96">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white p-3 font-semibold flex justify-between items-center shadow-md">
      <span class="text-lg flex items-center">
        <img src="../../../img/chat-group.png" class="w-6 mr-2" />
        Chat 
        <div id="newMessageAlert" class="hidden text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded cursor-pointer text-center absolute bottom-24 right-4 z-50 shadow-lg transition">
          📩 Có tin nhắn mới – nhấn để xem
        </div>

      </span>
      <button id="closeBtn" class="text-white hover:text-gray-200 text-2xl leading-none focus:outline-none transition-transform duration-200 transform hover:rotate-90">&times;</button>
    </div>
    

    <!-- Messages -->
    <div id="chatMessages" class="flex-1 p-3 space-y-3 overflow-y-auto bg-gray-50 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
    </div>

    <!-- Input -->
    <form id="chatForm" class="p-3 border-t border-gray-200 bg-white">
      
      <div class="flex items-center gap-2">
        <div class="flex items-center flex-1 border border-gray-300 rounded-full px-3 py-2 focus-within:ring-2 focus-within:ring-indigo-500 transition-all duration-200">
          <input id="chatInput" type="text" placeholder="Nhập tin nhắn của bạn..." class="flex-1 outline-none border-none bg-transparent text-sm" autocomplete="off"/>
          <label for="chatImage" class="ml-2 cursor-pointer text-indigo-600 hover:text-indigo-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11l-4 4m0 0l-4-4m4 4V5" />
            </svg>
          </label>
          <input id="chatImage" type="file" accept="image/*" class="hidden" />
          <div id="imagePreview" class="ml-2"></div>
        </div>
        <button type="submit" class="bg-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-full shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l4.453-1.483a1 1 0 00.67-.341l6.197-7.442a1 1 0 00-.075-1.54l-3.04-2.135z" />
            <path d="M14.004 5.955L9.694 11.23a.999.999 0 00-.285.51L8.09 15.54a1 1 0 01-1.071.05L4.0 14.15l.487-1.462A.999.999 0 004.28 12.18l6.197-7.442a1 1 0 011.374-1.09l4.453 1.484a1 1 0 01-.285.51z" />
          </svg>
        </button>
      </div>
    </form>
  </div>
</div>
  <script>
  // Lấy các phần tử HTML
  const toggleBtn = document.getElementById('toggleBtn');   // nút tròn dưới góc
  const closeBtn = document.getElementById('closeBtn');     // nút [x] trong khung chat
  const chatBox = document.getElementById('chatBox');       // khung chat
  const newMsgAlert = document.getElementById('newMessageAlert'); // cảnh báo tin nhắn
  let newMessageCount = 0; // số tin nhắn chưa đọc
  let chatOpened = false;  // trạng thái đã mở chat hay chưa

  // Sự kiện khi bấm nút mở/đóng (toggle)
  toggleBtn.addEventListener('click', () => {
    // Toggle class để hiện / ẩn khung chat
    chatBox.classList.toggle('scale-0');
    chatBox.classList.toggle('opacity-0');
    chatBox.classList.toggle('pointer-events-none');

    // Cập nhật trạng thái mở chat
    chatOpened = !chatBox.classList.contains('scale-0');

    // Nếu người dùng vừa mở khung chat
    if (chatOpened) {
      // Reset cảnh báo, đếm số tin mới
      newMsgAlert.classList.add('hidden');
      newMessageCount = 0;

      // Đổi icon chat về mặc định nếu bạn dùng ảnh
      const icon = document.getElementById('chatIcon');
      if (icon) icon.src = '../../../img/chat-box.png';

      // Đổi nút về màu gốc và xoá hiệu ứng
      toggleBtn.classList.remove('bg-red-600', 'ring-4', 'ring-red-500', 'animate-pulse');
      toggleBtn.classList.add('bg-indigo-600');
    }
  });

  // Sự kiện khi bấm nút đóng [x] ở khung chat
  closeBtn.addEventListener('click', () => {
    // Ẩn khung chat
    chatBox.classList.add('scale-0');
    chatBox.classList.add('opacity-0');
    chatBox.classList.add('pointer-events-none');

    // Cập nhật trạng thái
    chatOpened = false;

    // Reset cảnh báo và màu (nếu muốn)
    newMsgAlert.classList.add('hidden');
    newMessageCount = 0;

    const icon = document.getElementById('chatIcon');
    if (icon) icon.src = '../../../img/chat-box.png';

    toggleBtn.classList.remove('bg-red-600', 'ring-4', 'ring-red-500', 'animate-pulse');
    toggleBtn.classList.add('bg-indigo-600');
  });
  
</script>
<script>
  const GROUP_ID = <?= json_encode($group_id) ?>;
  const USER_ID = <?= json_encode($_SESSION['user_id']) ?>;
</script>
<script type="module" src="../confis/Chatbox.js"></script>

<style>
  /* ====== MESSAGE WRAPPER ====== */
.new-msg {
  animation: fadeInUp 0.25s ease;
}
.chat-message {
  position: relative;
  max-width: 70%;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.4;
  word-wrap: break-word;
  box-sizing: border-box;
}

/* ====== USER MESSAGE (ME) ====== */
.chat-message.me {
  position: relative;
  left: 80px;
  align-self: flex-end;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  border-bottom-right-radius: 4px;
  box-shadow: 0 6px 16px rgba(99,102,241,0.3);
}

/* ====== OTHER MESSAGE ====== */
.chat-message.other {
  align-self: flex-start;
  background: #f3f4f6;
  color: #111827;
  border-bottom-left-radius: 4px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}

/* ====== USERNAME ====== */
.chat-username {
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 4px;
  opacity: 0.8;
}

/* ====== TIME ====== */
.chat-time {
  font-size: 11px;
  opacity: 0.6;
  margin-top: 4px;
  text-align: right;
}

/* ====== IMAGE ====== */
.chat-image {
  margin-top: 6px;
  border-radius: 10px;
  max-width: 160px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.chat-image:hover {
  transform: scale(1.05);
  opacity: 0.9;
}

/* ====== ACTION BUTTONS ====== */
.chat-actions {
  position: absolute;
  top: -8px;
  right: 6px;
  display: flex;
  gap: 8px;
  font-size: 12px;
}

.chat-actions button {
  background: rgba(255,255,255,0.85);
  padding: 2px 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.chat-actions button:hover {
  background: #e5e7eb;
}

/* ====== ANIMATION ====== */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>