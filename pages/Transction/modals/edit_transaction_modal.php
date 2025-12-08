<!-- MODAL EDIT -->
<div id="editTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg overflow-hidden w-[500px] h-[680px] relative">
    <iframe id="editTransactionFrame" src="" class="w-full h-full border-none"></iframe>

    <button 
      onclick="closeEditTransactionModal()" 
      class="absolute top-2 right-2 text-red-600 font-bold text-lg"
    >
      ✖
    </button>
  </div>
</div>
<script>
function openEditTransactionModal(id) {
  const modal = document.getElementById("editTransactionModal");
  const frame = document.getElementById("editTransactionFrame");

  frame.src = "edit_transaction.php?id=" + id;
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeEditTransactionModal() {
  const modal = document.getElementById("editTransactionModal");
  const frame = document.getElementById("editTransactionFrame");

  frame.src = "";
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}
</script>
