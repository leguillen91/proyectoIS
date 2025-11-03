const token = localStorage.getItem("accessToken");
if (!token) window.location.href = "./../index.php";

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

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const me = await fetchJSON("/api/auth/me.php");
    const role = me.user.role;
    const name = me.user.fullName;

    document.getElementById("userName").textContent = name;
    document.getElementById("userRole").textContent = role;

    if (role === "admin") {
      // Mostrar panel completo de administración
      document.getElementById("adminSection").classList.remove("d-none");
      document.getElementById("linkCreateUser").classList.remove("d-none");
      document.getElementById("linkAdminPanel").classList.remove("d-none");

      // Cargar métricas
      loadMetrics();
    } else {
      // Mostrar vista estudiante o usuario común
      document.getElementById("studentSection").classList.remove("d-none");
      document.getElementById("studentName").textContent = name;
      document.getElementById("studentRole").textContent = role;
      document.getElementById("dashboardTitle").textContent = "Portal del Estudiante";
    }
  } catch (err) {
    console.error(err);
    localStorage.removeItem("accessToken");
    window.location.href = "./../index.php";
  }

  // Logout
  document.getElementById("btnLogout").addEventListener("click", async () => {
    try {
      await fetch("/api/auth/logout.php", { headers: { Authorization: `Bearer ${token}` } });
    } catch {}
    localStorage.removeItem("accessToken");
    window.location.href = "./../index.php";
  });
});

// ====== Métricas solo admin ======
async function loadMetrics() {
  try {
    const [users, students] = await Promise.all([
      fetchJSON("/api/users/list.php"),
      fetchJSON("/api/students/list.php"),
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
