<div id="addTagModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center backdrop-blur-sm">
    <div class="w-full max-w-xl animate-popup">
        <iframe 
            src="add_tag.php"
            class="w-full h-[90vh] border-none rounded-xl bg-transparent"
            loading="lazy">
        </iframe>
    </div>
</div>

<script>
function openAddTagModal() {
    const modal = document.getElementById("addTagModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeAddTagModal() {
    const modal = document.getElementById("addTagModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>
