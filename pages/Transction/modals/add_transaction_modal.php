 <!--------------- ADD TRANSACTION MODAL ----------------->
<div id="addTransactionModal" 
     class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center backdrop-blur-sm">

    <iframe src="add_transaction.php"
        class="w-full h-[90vh] border-none rounded-xl bg-transparent" 
        loading="lazy">
    </iframe>

</div>
<script>
function openAddTransactionModal() {
  const modal = document.getElementById("addTransactionModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeAddTransactionModal() {
  const modal = document.getElementById("addTransactionModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}
</script>