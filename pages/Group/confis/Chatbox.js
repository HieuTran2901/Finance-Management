const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatImage = document.getElementById("chatImage");
const chatMessages = document.getElementById("chatMessages");
const newMsgAlert = document.getElementById("newMessageAlert");

let isAtBottom = true;
let newMessageCount = 0;
let lastMessageId = null;
let isEditingMessage = false; // 🔥 flag để tránh reload khi đang sửa

function loadMessages() {
  fetch(`../message/get_messages.php?group_id=${GROUP_ID}`)
    .then(res => res.json())
    .then(data => {
      chatMessages.innerHTML = '';
      data.forEach(msg => {
        const div = document.createElement('div');
        div.classList.add('bg-white', 'p-2', 'rounded', 'shadow-sm', 'relative');

        let content = `
          <strong>${msg.username}</strong>: 
          <span class="chat-msg" data-id="${msg.id}">${msg.message}</span><br>
          <small class="text-gray-400">${msg.sent_at}</small>
        `;

        if (msg.image) {
          const imagePath = `/pages/Group/message/uploads/${msg.image}`;
          content += `
            <div class="mt-2">
              <a href="${imagePath}" target="_blank">
                <img src="${imagePath}" class="w-20 h-20 object-cover rounded shadow cursor-pointer hover:opacity-80 transition" />
              </a>
            </div>
          `;
        }

        if (msg.sender_id == USER_ID) {
          content += `
            <div class="absolute right-2 top-2 space-x-2 text-sm">
              <button class="text-blue-500 edit-btn" data-id="${msg.id}" data-msg="${msg.message}" data-image="${msg.image || ''}">Sửa</button>
              <button class="text-red-500 delete-btn" data-id="${msg.id}">Thu hồi</button>
            </div>`;
        }

        div.innerHTML = content;
        chatMessages.appendChild(div);
      });

      if (data.length > 0) {
        const newestId = data[data.length - 1].id;
        const newestMsg = data[data.length - 1];
        const isChatVisible = !chatBox.classList.contains("scale-0");

        if (lastMessageId !== null && newestId !== lastMessageId && newestMsg.sender_id != USER_ID && !isChatVisible) {
          newMessageCount++;
          newMsgAlert.classList.remove('hidden');
          newMsgAlert.textContent = `📩 Có ${newMessageCount} tin nhắn mới – nhấn để xem`;

          const icon = document.getElementById('chatIcon');
          if (icon) icon.src = './img/chat-warning.png';

          toggleBtn.classList.remove('bg-indigo-600');
          toggleBtn.classList.add('bg-red-600', 'ring-4', 'ring-red-500', 'animate-pulse');
        }

        lastMessageId = newestId;
      } else {
        lastMessageId = null;
      }

      if (isAtBottom) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    });
}

// Tự động tải lại (trừ khi đang sửa)
setInterval(() => {
  if (!isEditingMessage) loadMessages();
}, 2000);
loadMessages();

// Gửi tin mới
chatForm.addEventListener("submit", function (e) {
  e.preventDefault();

  const message = chatInput.value.trim();
  const image = chatImage.files[0];
  if (!message && !image) return;

  const formData = new FormData();
  formData.append("group_id", GROUP_ID);
  formData.append("message", message);
  if (image) formData.append("image", image);

  fetch("../message/send_message.php", {
    method: "POST",
    body: formData
  })
    .then(res => res.text())
    .then(response => {
      if (response === "OK") {
        chatInput.value = "";
        chatImage.value = "";
        loadMessages();
      } else {
        alert("❌ Gửi thất bại: " + response);
      }
    })
    .catch(err => {
      console.error("Lỗi gửi tin:", err);
      alert("❌ Có lỗi khi gửi");
    });
});

// Scroll
chatMessages.addEventListener('scroll', () => {
  const scrollBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight;
  isAtBottom = scrollBottom <= 20;
  if (isAtBottom) {
    newMsgAlert.classList.add('hidden');
    newMessageCount = 0;
  }
});

newMsgAlert.addEventListener('click', () => {
  chatMessages.scrollTop = chatMessages.scrollHeight;
  newMsgAlert.classList.add('hidden');
  newMessageCount = 0;
});

// Xử lý sửa / xoá
chatMessages.addEventListener("click", function (e) {
  if (e.target.classList.contains("delete-btn")) {
    const msgId = e.target.dataset.id;
    if (confirm("Bạn chắc chắn muốn thu hồi tin nhắn này?")) {
      fetch("../message/delete_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${msgId}`
      })
        .then(res => res.text())
        .then(response => {
          if (response === "OK") {
            loadMessages();
          } else {
            alert("❌ Không thể xóa tin nhắn: " + response);
          }
        });
    }
  }

  // ✅ Inline edit
  if (e.target.classList.contains("edit-btn")) {
    const msgId = e.target.dataset.id;
    const msgSpan = document.querySelector(`.chat-msg[data-id="${msgId}"]`);
    const parentDiv = msgSpan.closest('div');
    const oldText = msgSpan.textContent;

    e.target.style.display = 'none';
    isEditingMessage = true; // 🔒 Ngăn auto reload

    const input = document.createElement("input");
    input.type = "text";
    input.value = oldText;
    input.className = "border rounded px-2 py-1 text-sm w-full mt-1";

    input.addEventListener("keydown", function (e) {
      if (e.key === "Enter") e.preventDefault(); // 🔒 Ngăn Enter submit form
    });

    const imageInput = document.createElement("input");
    imageInput.type = "file";
    imageInput.accept = "image/*";
    imageInput.className = "block mt-2";

    const saveBtn = document.createElement("button");
    saveBtn.textContent = "Lưu";
    saveBtn.className = "bg-blue-500 text-white px-2 py-1 rounded text-sm mr-2 mt-2";

    const cancelBtn = document.createElement("button");
    cancelBtn.textContent = "Huỷ";
    cancelBtn.className = "bg-gray-400 text-white px-2 py-1 rounded text-sm mt-2";

    msgSpan.replaceWith(input);
    parentDiv.appendChild(imageInput);
    parentDiv.appendChild(saveBtn);
    parentDiv.appendChild(cancelBtn);

    cancelBtn.onclick = () => {
      input.replaceWith(msgSpan);
      imageInput.remove();
      saveBtn.remove();
      cancelBtn.remove();
      e.target.style.display = 'inline';
      isEditingMessage = false; // 🔓 Cho phép reload lại
    };

    saveBtn.onclick = () => {
      const newText = input.value.trim();
      const imageFile = imageInput.files[0];

      const formData = new FormData();
      formData.append("id", msgId);
      formData.append("message", newText);
      if (imageFile) formData.append("image", imageFile);

      fetch("../message/edit_message.php", {
        method: "POST",
        body: formData
      })
        .then(res => res.text())
        .then(response => {
          if (response === "OK") {
            isEditingMessage = false;
            loadMessages();
          } else {
            alert("❌ Không thể sửa tin nhắn: " + response);
          }
        });
    };
  }
});
