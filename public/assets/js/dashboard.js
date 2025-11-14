// public/assets/js/dashboard.js
document.addEventListener("DOMContentLoaded", async () => {
  const token = localStorage.getItem("accessToken");
  if (!token) {
    window.location.href = "./../index.php";
    return;
  }

  const fetchJSON = async (url, options = {}) => {
    const res = await fetch(url, {
      ...options,
      headers: {
        Authorization: `Bearer ${token}`,
        ...(options.headers || {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || "Error al consultar");
    return data;
  };

  try {
    const me = await fetchJSON("/public/api/auth/me.php");
    const user = me.user || {};
    const role = (user.role || "").toLowerCase();
    const name = user.fullName || "Usuario";
    const permissions = user.permissions || [];
    const career = (user.career || "").toLowerCase();

    document.getElementById("userName").textContent = name;
    document.getElementById("userRole").textContent = role;

    // Mostrar secciones según rol
    if (role === "admin") {
      document.getElementById("adminSection")?.classList.remove("d-none");
      document.getElementById("linkCreateUser")?.classList.remove("d-none");
      document.getElementById("linkAdminPanel")?.classList.remove("d-none");
      document.getElementById("linkStudents")?.classList.remove("d-none");
      document.getElementById("linkSoftware")?.classList.remove("d-none");
      document.getElementById("linkVirtualLibrary")?.classList.remove("d-none");
      loadMetrics();

    } else if (["coordinator", "teacher", "depthead"].includes(role)) {
      document.getElementById("studentSection")?.classList.remove("d-none");
      document.getElementById("dashboardTitle").textContent = "Panel Docente";
      document.getElementById("linkSoftware")?.classList.remove("d-none");
      document.getElementById("linkVirtualLibrary")?.classList.remove("d-none");

    } else if (role === "student") {
      document.getElementById("studentSection")?.classList.remove("d-none");
      document.getElementById("studentName").textContent = name;
      document.getElementById("studentRole").textContent = role;
      document.getElementById("dashboardTitle").textContent = "Portal del Estudiante";

      const allowedCareers = [
        "ingeniería en sistemas",
        "ingenieria en sistemas",
        "licenciatura en informática",
        "licenciatura en informatica"
      ];

      if (career && allowedCareers.includes(career)) {
        document.getElementById("linkSoftware")?.classList.remove("d-none");
      } else {
        document.getElementById("linkSoftware")?.classList.add("d-none");
      }

      // Mostrar biblioteca virtual a todos los estudiantes
      document.getElementById("linkVirtualLibrary")?.classList.remove("d-none");

    } else {
      // Cualquier otro rol sin permiso
      document.getElementById("linkSoftware")?.classList.add("d-none");
      document.getElementById("linkVirtualLibrary")?.classList.add("d-none");
    }

  } catch (err) {
    console.error("Error al cargar el contexto:", err);
    localStorage.removeItem("accessToken");
    window.location.href = "./../index.php";
  }

  // Logout
  document.getElementById("btnLogout").addEventListener("click", async () => {
    try {
      await fetch("/public/api/auth/logout.php", { headers: { Authorization: `Bearer ${token}` } });
    } catch {}
    localStorage.removeItem("accessToken");
    window.location.href = "./../index.php";
  });
});

// ====== Métricas solo admin ======
async function loadMetrics() {
  try {
    const [users, students] = await Promise.all([
      fetch("/api/users/list.php").then(r => r.json()),
      fetch("/api/students/list.php").then(r => r.json()),
    ]);

    document.getElementById("totalUsers").textContent = (users.users || []).length;
    document.getElementById("activeStudents").textContent =
      (students.students || []).filter(s => s.status === "Activo").length;
    document.getElementById("coordinators").textContent =
      (users.users || []).filter(u => u.role === "coordinator").length;
  } catch (err) {
    console.warn("Error cargando métricas:", err);
  }
}
