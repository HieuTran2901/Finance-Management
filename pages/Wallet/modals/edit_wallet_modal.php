<div id="editWalletModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-xl animate-popup">
        <iframe 
            src=""
            id="editWalletFrame"
            class="w-full h-[90vh] border-none rounded-xl bg-transparent"
            loading="lazy">
        </iframe>
    </div>
</div>

<script>
function openEditWalletModal(id) {
    const modal = document.getElementById("editWalletModal");
    const iframe = document.getElementById("editWalletFrame");

    iframe.src = "../Wallet/edit_wallet.php?id=" + id;
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeEditWalletModal() {
    const modal = document.getElementById("editWalletModal");
    const iframe = document.getElementById("editWalletFrame");

    iframe.src = "";
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>
