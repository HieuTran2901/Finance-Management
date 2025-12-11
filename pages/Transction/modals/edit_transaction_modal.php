<!--------------- EDIT TRANSACTION MODAL ----------------->
<div id="editTransactionModal" 
     class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center backdrop-blur-sm">

    <iframe id="editTransactionFrame"
        src=""
        class="w-full h-[90vh] border-none rounded-xl bg-transparent"
        loading="lazy">
    </iframe>

</div>

<script>
function openEditTransactionModal(id) {
    const modal = document.getElementById("editTransactionModal");
    const frame = document.getElementById("editTransactionFrame");

    // Gắn link edit khi mở
    frame.src = "edit_transaction.php?id=" + id;

    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeEditTransactionModal() {
    const modal = document.getElementById("editTransactionModal");
    const frame = document.getElementById("editTransactionFrame");

    // Reset iframe tránh lưu lại dữ liệu cũ
    frame.src = "";

    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>
