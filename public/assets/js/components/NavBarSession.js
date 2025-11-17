// Importa y registra los componentes de NavBar y Footer
import "/NavBar.js";
import "/components/Footer.js";


document.addEventListener("DOMContentLoaded", async () => {
  const sessionArea = document.getElementById("navSessionArea");
  const token = localStorage.getItem("accessToken");
  
  if (!sessionArea) {
    console.error("navSessionArea no encontrado");
    return;
  }

  if (!token) {
    console.log("No hay token, mostrando Acceder");
    return;
  }

  try {
    const res = await fetch("/public/api/auth/me.php", {
      method: "GET",
      headers: { "Authorization": "Bearer " + token }
    });

    const data = await res.json();
    console.log("SESSION DATA:", data);

    if (!data.ok) {
      console.warn("Sesión inválida. Mostrando Acceder.");
      return;
    }
    const roleMap = {
      student: "Estudiante",
      teacher: "Docente",
      coordinator: "Coordinador",
      depthead: "Jefe de Departamento",
      admin: "Administrador"
    };
    // ---- Validación por carrera ----
    // ---- Validación por carrera + admin ----
const linkSoftware = document.getElementById("linkSoftware");

// Carreras permitidas
const allowedCareers = [
  "Ingeniería en Sistemas",
  "Licenciatura en Informática"
];

const userCareer = data.user.career || "";
const userRole = data.user.role || "";

// Si NO pertenece a las carreras permitidas y NO es admin → ocultar
const isCareerAllowed = allowedCareers.includes(userCareer);
const isAdmin = userRole === "admin";

if (!isCareerAllowed && !isAdmin) {
  if (linkSoftware) linkSoftware.style.display = "none";
}


    const roleEs = roleMap[data.user.role] || data.user.role;
    const rawName = data.user.name || data.user.fullName || data.user.username || "Usuario";
    const userName = rawName.split(" ")[0];
    sessionArea.innerHTML = `
      <div class="dropdown">
        <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i> ${userName}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item disabled">${roleEs}</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" id="btnLogout">Cerrar sesión</a></li>
        </ul>
      </div>
    `;

    document.getElementById("btnLogout").addEventListener("click", () => {
      localStorage.removeItem("accessToken");
      window.location.href = "index.php";
    });

  } catch (error) {
    console.error("Error cargando sesión:", error);
  }
});
