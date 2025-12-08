<div id="addWalletModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center backdrop-blur-sm">
    <iframe src="add_wallet.php" 
        class="w-full h-[90vh] border-none rounded-xl bg-transparent" 
        loading="lazy"></iframe>
</div>

<script>
function openAddWalletModal() {
    const modal = document.getElementById("addWalletModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeAddWalletModal() {
    const modal = document.getElementById("addWalletModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>
