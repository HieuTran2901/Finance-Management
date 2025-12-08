<div id="editTagModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center backdrop-blur-sm">
    <div class="w-full max-w-xl animate-popup">
        <iframe 
            src=""
            id="editTagFrame"
            class="w-full h-[90vh] border-none rounded-xl bg-transparent"
            loading="lazy">
        </iframe>
    </div>
</div>

<script>
function openEditTagModal(id) {
    const modal = document.getElementById("editTagModal");
    const iframe = document.getElementById("editTagFrame");

    iframe.src = "edit_tag.php?id=" + id;
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeEditTagModal() {
    const modal = document.getElementById("editTagModal");
    const iframe = document.getElementById("editTagFrame");

    iframe.src = "";
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>
