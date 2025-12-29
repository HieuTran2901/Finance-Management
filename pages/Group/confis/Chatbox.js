const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatImage = document.getElementById("chatImage");
const chatMessages = document.getElementById("chatMessages");
const newMsgAlert = document.getElementById("newMessageAlert");

let lastMessageId = 0;
let isAtBottom = true;
let newMessageCount = 0;
let isEditingMessage = false;

/* ======================
   TẠO MESSAGE ELEMENT
====================== */
function createMessageElement(msg) {
  const wrapper = document.createElement("div");
  wrapper.className = `chat-message ${
    msg.sender_id == USER_ID ? "me" : "other"
  }`;
  wrapper.dataset.id = msg.id;

  let html = `
    <div class="chat-username">${msg.username}</div>
    <div class="chat-text">${msg.message}</div>
  `;

  if (msg.image) {
    html += `
      <img src="/pages/Group/message/uploads/${msg.image}"
           class="chat-image"
           onclick="window.open(this.src)">
    `;
  }

  html += `<div class="chat-time">${msg.sent_at}</div>`;

  if (msg.sender_id == USER_ID) {
    html += `
      <div class="chat-actions">
        <button class="edit-btn text-gray-700" data-id="${msg.id}">Sửa</button>
        <button class="delete-btn text-gray-700" data-id="${msg.id}">Thu hồi</button>
      </div>
    `;
  }

  wrapper.innerHTML = html;

  // ✨ animate CHỈ tin mới
  wrapper.classList.add("new-msg");
  setTimeout(() => wrapper.classList.remove("new-msg"), 300);

  return wrapper;
}

/* ======================
   LOAD TIN NHẮN MỚI
====================== */
function loadMessages() {
  if (isEditingMessage) return;

  fetch(
    `../message/get_messages.php?group_id=${GROUP_ID}&after_id=${lastMessageId}`
  )
    .then((res) => res.json())
    .then((data) => {
      if (!data.length) return;

      let lastIdBefore = lastMessageId;
      let hasNew = false;

      data.forEach((msg) => {
        if (document.querySelector(`.chat-message[data-id="${msg.id}"]`))
          return;

        const el = createMessageElement(msg);

        if (msg.id > lastIdBefore) {
          el.classList.add("new-msg");
          hasNew = true;
        }

        chatMessages.appendChild(el);
        lastMessageId = msg.id;
      });

      if (hasNew && isAtBottom) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    });
}

/* ======================
   AUTO LOAD
====================== */
setInterval(loadMessages, 2000);
loadMessages();

/* ======================
   GỬI TIN
====================== */
chatForm.addEventListener("submit", (e) => {
  e.preventDefault();

  const text = chatInput.value.trim();
  const image = chatImage.files[0];
  if (!text && !image) return;

  const fd = new FormData();
  fd.append("group_id", GROUP_ID);
  fd.append("message", text);
  if (image) fd.append("image", image);

  fetch("../message/send_message.php", {
    method: "POST",
    body: fd,
  }).then(() => {
    chatInput.value = "";
    chatImage.value = "";
    loadMessages();
  });
});

/* ======================
   SCROLL
====================== */
chatMessages.addEventListener("scroll", () => {
  const bottom =
    chatMessages.scrollHeight -
    chatMessages.scrollTop -
    chatMessages.clientHeight;
  isAtBottom = bottom < 20;

  if (isAtBottom) {
    newMsgAlert.classList.add("hidden");
    newMessageCount = 0;
  }
});

newMsgAlert.addEventListener("click", () => {
  chatMessages.scrollTop = chatMessages.scrollHeight;
  newMsgAlert.classList.add("hidden");
  newMessageCount = 0;
});

/* ======================
   XOÁ & SỬA
====================== */
chatMessages.addEventListener("click", (e) => {
  /* XOÁ */
  if (e.target.classList.contains("delete-btn")) {
    const id = e.target.dataset.id;
    if (!confirm("Thu hồi tin nhắn?")) return;

    fetch("../message/delete_message.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}`,
    }).then(() => {
      const el = document.querySelector(`.chat-message[data-id="${id}"]`);
      if (el) el.remove();
    });
  }

  /* SỬA */
  if (e.target.classList.contains("edit-btn")) {
    isEditingMessage = true;
    const id = e.target.dataset.id;
    const box = document.querySelector(`.chat-message[data-id="${id}"]`);
    const textEl = box.querySelector(".chat-text");
    const old = textEl.textContent;

    const input = document.createElement("input");
    input.value = old;
    input.className = "w-full border text-gray-700 rounded px-2 py-1 text-sm";

    textEl.replaceWith(input);
    input.focus();

    input.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") {
        fetch("../message/edit_message.php", {
          method: "POST",
          body: new URLSearchParams({ id, message: input.value }),
        }).then(() => {
          textEl.textContent = input.value;
          input.replaceWith(textEl);
          isEditingMessage = false;
        });
      }
    });
  }
});
