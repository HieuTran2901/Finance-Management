// Edit Goal Modal Functions
function openEditModal(id, name, saved, target, endDate) {
  document.getElementById("edit_id").value = id;
  document.getElementById("edit_name").value = name;
  document.getElementById("edit_saved").value = saved;
  document.getElementById("edit_target").value = target;
  document.getElementById("edit_end_date").value = endDate;
  document.getElementById("editModal").classList.remove("hidden");
}

function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}

// ----------------------------------------------------------------------
// Add Goal Modal Functions
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("addGoalModal");
  const addBtn = document.getElementById("addGoalBtn");
  const cancelBtn = document.getElementById("cancelBtn");

  addBtn.addEventListener("click", () => modal.classList.remove("hidden"));
  cancelBtn.addEventListener("click", () => modal.classList.add("hidden"));
});
// ----------------------------------------------------------------------
// AI Motivation Fetching
document.addEventListener("DOMContentLoaded", async () => {
  const aiMessages = document.querySelectorAll(".ai-message");

  aiMessages.forEach(async (msg) => {
    const goalName = msg.dataset.goalName;
    const percentage = msg.dataset.percentage;
    const daysLeft = msg.dataset.daysLeft;

    try {
      const res = await fetch("../../Func/AI_Motivation.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          goal_name: goalName,
          percentage: percentage,
          days_left: daysLeft,
        }),
      });

      const data = await res.json();
      msg.textContent = "💬 " + data.message;
    } catch (error) {
      msg.textContent = "⚠️ Không thể lấy lời động viên lúc này.";
      console.error("AI Motivation Error:", error);
    }
  });
});
// ----------------------------------------------------------------------
// AI Analyze Modal Functions
document.addEventListener("DOMContentLoaded", () => {
  const aiBtn = document.getElementById("aiAnalyzeBtn");
  const modal = document.getElementById("aiAnalyzeModal");
  const closeBtn = document.getElementById("closeAiModal");
  const runBtn = document.getElementById("runAiAnalyze");
  const resultDiv = document.getElementById("aiAnalyzeResult");

  // Mở modal -> KHÔNG hiển thị gì cả
  aiBtn.addEventListener("click", () => {
    modal.classList.remove("hidden");
    resultDiv.innerHTML = "Bấm 'Chạy phân tích' để nhận đánh giá từ AI.";
  });

  // Đóng modal
  closeBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
  });

  // Gọi API phân tích AI
  runBtn.addEventListener("click", async () => {
    resultDiv.innerHTML = "⏳ Đang phân tích, vui lòng chờ..."; // Chỉ hiển thị khi bắt đầu phân tích
    try {
      const res = await fetch("../../Func/AI_Analyze.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: USER_ID }),
      });

      const data = await res.json();
      console.log(data); // xem object trả về

      if (data.message) {
        resultDiv.innerHTML = data.message.replace(/\n/g, "<br>");
      } else if (data.error) {
        resultDiv.innerHTML = `⚠️ Có lỗi xảy ra: ${data.error}`;
      } else {
        resultDiv.innerHTML = "⚠️ Không thể phân tích lúc này, thử lại sau.";
      }
    } catch (err) {
      resultDiv.textContent = "⚠️ Lỗi kết nối AI.";
      console.error(err);
    }
  });
});

// ----------------------------------------------------------------------
