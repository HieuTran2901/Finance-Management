const modal = document.getElementById("comingSoonModal");
const closeModal = document.getElementById("closeModal");

document.querySelectorAll(".js-coming-soon").forEach((btn) => {
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
