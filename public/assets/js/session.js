
document.addEventListener("DOMContentLoaded", async () => {

  const token = localStorage.getItem("accessToken");
  const navSessionArea = document.getElementById("navSessionArea");
  const loginBtn = document.getElementById("btnLogin");

  // Si NO hay token, no hacemos nada (se muestra "Acceder")
  if (!token) return;

  try {
    const res = await fetch("/api/auth/me.php", {
      headers: { "Authorization": "Bearer " + token }
    });
    const data = await res.json();

    if (!data.ok) throw new Error("Sesión inválida");

    const name = data.user.name.split(" ")[0]; // Nombre corto
    const role = data.user.role;

    // Reemplazar botón "Acceder" por menú de usuario
    navSessionArea.innerHTML = `
      <div class="dropdown">
        <a class="nav-link dropdown-toggle fw-semibold text-white" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i> ${name}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item disabled">Rol: ${role}</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" id="btnLogout">Cerrar sesión</a></li>
        </ul>
      </div>
    `;

    // Acción cerrar sesión
    document.getElementById("btnLogout").addEventListener("click", () => {
      localStorage.removeItem("accessToken");
      window.location.href = "index.php";
    });

  } catch (err) {
    console.error("Error obteniendo sesión:", err);
    localStorage.removeItem("accessToken");
  }

});
