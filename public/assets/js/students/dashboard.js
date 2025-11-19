document.addEventListener("DOMContentLoaded", async () => {
  const API_BASE = "/api/resource";
  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "./../login.html";
    return;
  }
  try {
    const res = await fetch("/api/auth/me.php", {
      headers: { Authorization: `Bearer ${token}` },
    });

    if (!res.ok) throw new Error("Error al autenticar sesión");
    const data = await res.json();

    if (!data.ok || !data.user) {
      showAlert("Sesión inválida. Inicie sesión nuevamente.", "danger");
      setTimeout(() => (window.location.href = "../../index.php"), 2000);
      return;
    }

    userCtx = data.user;
    console.log(` Sesión activa: ${userCtx.fullName} (${userCtx.role})`);

  } catch (err) {
    console.error(" Error al obtener contexto:", err);
    showAlert("Error de sesión. Redirigiendo al inicio...", "danger");
    setTimeout(() => (window.location.href = "../../index.php"), 2000);
  }


  async function loadDashboard() {
    try {
      const response = await fetch("/api/students/getDashboard.php", {
        headers: {
          "Authorization": "Bearer " + token
        }
      });

      const data = await response.json();

      if (!data.ok) {
        alert("Acceso denegado o sesión expirada.");
        window.location.href = "../../index.php";
        return;
      }

      const s = data.student;
      document.getElementById("studentName").textContent = s.fullName;
      document.getElementById("studentCareer").textContent = s.career;
      document.getElementById("studentAccount").textContent = s.enrollmentCode;
      document.getElementById("studentCenter").textContent = s.academicCenter;
      document.getElementById("studentPeriod").textContent = s.currentPeriod;

    } catch (e) {
      console.error("Error cargando dashboard:", e);
    }
  }

  // Recargar dashboard
  document.getElementById("btnReload").addEventListener("click", loadDashboard);

  // Cerrar sesión
  document.getElementById("btnLogout").addEventListener("click", async () => {
        try {
          await fetch("/api/auth/logout.php", { headers: { Authorization: `Bearer ${token}` } });
        } catch {}
        localStorage.removeItem("accessToken");
        window.location.href = "../../index.php";
      });

  // Primera carga
  loadDashboard();
});
