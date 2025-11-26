const toggleBtn = document.getElementById("toggleBtn");
const closeBtn = document.getElementById("closeBtn");
const chatBox = document.getElementById("chatBox");
const form = document.getElementById("chatForm");
const input = document.getElementById("chatInput");
const imageInput = document.getElementById("chatImage");
const imagePreview = document.getElementById("imagePreview");
const messagesContainer = document.getElementById("chatMessages");

// ✅ Khôi phục lịch sử chat nếu có
const savedMessages = sessionStorage.getItem("chatMessages");
if (savedMessages) {
  messagesContainer.innerHTML = savedMessages;
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}
function saveMessages() {
  sessionStorage.setItem("chatMessages", messagesContainer.innerHTML);
}

// ✅ Điều chỉnh input xuống dòng và gửi tin
input.addEventListener("keydown", function (e) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault(); // chặn xuống dòng
    form.dispatchEvent(new Event("submit")); // gửi
  }
});
input.addEventListener("input", function () {
  this.style.height = "auto";
  this.style.height = this.scrollHeight + "px";
});

// ✅ Hiển thị preview ảnh
imageInput.addEventListener("change", () => {
  const imageFile = imageInput.files[0];
  imagePreview.innerHTML = "";
  if (imageFile) {
    const reader = new FileReader();
    reader.onload = () => {
      const img = document.createElement("img");
      img.src = reader.result;
      img.className = "h-8 w-8 rounded object-cover";
      imagePreview.appendChild(img);
    };
    reader.readAsDataURL(imageFile);
  }
});

toggleBtn.addEventListener("click", () => {
  chatBox.classList.toggle("scale-0");
  chatBox.classList.toggle("opacity-0");
  chatBox.classList.toggle("pointer-events-none");
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
});

closeBtn.addEventListener("click", () => {
  chatBox.classList.add("scale-0", "opacity-0", "pointer-events-none");
});

function readImageAsDataURL(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

// ✅ Gửi tin nhắn
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const userMessage = input.value.trim();
  const imageFile = imageInput.files[0];
  if (!userMessage && !imageFile) return;

  if (userMessage) appendMessage(userMessage, "user");

  let base64Image = null;
  if (imageFile) {
    try {
      base64Image = await readImageAsDataURL(imageFile);
      appendImage(base64Image, "user");
    } catch (err) {
      appendMessage("Lỗi khi đọc ảnh.", "ai error");
      return;
    }
  }

  // Làm mới input NGAY sau khi gửi
  input.value = "";
  imageInput.value = "";
  imagePreview.innerHTML = "";

  const loadingBubble = showLoading();

  const formData = new FormData();
  formData.append("message", userMessage);
  if (imageFile) formData.append("image", imageFile);

  try {
    const res = await fetch("./module/handle.php", {
      method: "POST",
      body: formData,
    });
    const aiReply = await res.text();
    loadingBubble.remove();
    appendMessage(aiReply, "ai");
  } catch (error) {
    loadingBubble.remove();
    appendMessage("Lỗi khi kết nối: " + error.message, "ai error");
  }
});

// ✅ Các hàm hỗ trợ
function showLoading() {
  const messageContainer = document.createElement("div");
  messageContainer.className =
    "flex w-full items-start justify-start gap-2 mb-2 animate-pulse";

  const avatar = document.createElement("div");
  avatar.className =
    "w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0";
  avatar.innerText = "AI";

  const bubble = document.createElement("div");
  bubble.className =
    "bg-gray-200 text-gray-700 px-4 py-2 rounded-2xl rounded-bl-none shadow text-sm flex gap-1 items-center max-w-[80%]";
  bubble.innerHTML = `
    <div class="flex items-center space-x-1">
      <span class="animate-bounce" style="animation-delay: 0s">.</span>
      <span class="animate-bounce" style="animation-delay: 0.1s">.</span>
      <span class="animate-bounce" style="animation-delay: 0.2s">.</span>
    </div>
  `;

  messageContainer.appendChild(avatar);
  messageContainer.appendChild(bubble);
  messagesContainer.appendChild(messageContainer);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
  saveMessages();
  return messageContainer;
}

function appendMessage(text, type) {
  const messageContainer = document.createElement("div");
  messageContainer.className = "flex w-full items-end mb-2";

  const bubble = document.createElement("div");
  bubble.className =
    "max-w-[80%] px-4 py-2 rounded-2xl shadow text-sm whitespace-pre-wrap break-words";

  try {
    const parsedJson = JSON.parse(text);
    if (typeof parsedJson === "object" && parsedJson !== null) {
      const pre = document.createElement("pre");
      pre.className = "whitespace-pre-wrap font-mono";
      if (parsedJson.amount || parsedJson.error) {
        pre.innerText = parsedJson.amount
          ? `Danh mục: ${
              parsedJson.category || "Không rõ"
            }\nSố tiền: ${new Intl.NumberFormat("vi-VN").format(
              parsedJson.amount
            )}đ\nNgày: ${parsedJson.date || "Hôm nay"}\nMô tả: ${
              parsedJson.description || "N/A"
            }`
          : `Lỗi: ${parsedJson.error}`;
        bubble.appendChild(pre);
      } else {
        bubble.innerText = JSON.stringify(parsedJson, null, 2);
      }
    } else {
      bubble.innerText = text;
    }
  } catch (e) {
    bubble.innerText = text;
  }

  if (type === "user") {
    messageContainer.classList.add("justify-end");
    bubble.classList.add("bg-indigo-500", "text-white", "rounded-br-none");
    messageContainer.appendChild(bubble);
  } else {
    messageContainer.classList.add("justify-start", "gap-2");
    const avatar = document.createElement("div");
    avatar.className =
      "w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0";
    avatar.innerText = "AI";
    bubble.classList.add("bg-gray-200", "text-gray-800", "rounded-bl-none");
    messageContainer.appendChild(avatar);
    messageContainer.appendChild(bubble);
  }

  messagesContainer.appendChild(messageContainer);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
  saveMessages();
}

function appendImage(src, type) {
  const messageContainer = document.createElement("div");
  messageContainer.className = "flex w-full items-end mb-2";

  const bubble = document.createElement("div");
  bubble.className =
    "max-w-[80%] p-2 rounded-2xl shadow text-sm overflow-hidden";

  const img = document.createElement("img");
  img.src = src;
  img.className = "max-w-full h-auto rounded-lg object-cover";
  bubble.appendChild(img);

  if (type === "user") {
    messageContainer.classList.add("justify-end");
    bubble.classList.add("bg-indigo-100", "rounded-br-none");
    messageContainer.appendChild(bubble);
  } else {
    messageContainer.classList.add("justify-start", "gap-2");
    const avatar = document.createElement("div");
    avatar.className =
      "w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0";
    avatar.innerText = "AI";
    bubble.classList.add("bg-gray-100", "rounded-bl-none");
    messageContainer.appendChild(avatar);
    messageContainer.appendChild(bubble);
  }

  messagesContainer.appendChild(messageContainer);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
  saveMessages();
}
