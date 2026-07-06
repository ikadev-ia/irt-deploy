document.addEventListener("click", (event) => {
    const link = event.target.closest("[data-confirm]");
    if (!link) {
        return;
    }
    if (!window.confirm(link.dataset.confirm)) {
        event.preventDefault();
    }
});
