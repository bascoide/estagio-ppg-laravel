function toggleDropdown(id, button) {
    var submenu = document.getElementById(id);
    submenu.classList.toggle("hidden");

    var arrow = button.querySelector("[data-sidebar-arrow]");
    if (submenu.classList.contains("hidden")) {
        arrow.textContent = "\u25BC";
    } else {
        arrow.textContent = "\u25B2";
    }
}
