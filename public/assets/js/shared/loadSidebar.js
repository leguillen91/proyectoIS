document.addEventListener("DOMContentLoaded", async () => {
    const container = document.getElementById("sidebarContainer");
    if (!container) return;

    try {
        const res = await fetch("../components/studentSidebar.html");
        const html = await res.text();
        container.innerHTML = html;

        // Activar item según página
        const path = window.location.pathname;

        document.querySelectorAll(".menu a").forEach(a => {
            if (path.includes(a.getAttribute("data-item"))) {
                a.classList.add("active");
            }
        });

        // Logout
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", () => {
                localStorage.removeItem("accessToken");
                window.location.href = "../../index.php";
            });
        }

    } catch (e) {
        console.error("ERROR cargando sidebar:", e);
    }
});
