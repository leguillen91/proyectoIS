// URL base de la API
const baseUrl = "/public/api/auth/";

document.addEventListener("DOMContentLoaded", () => {
  const btnLogin = document.getElementById("btnLogin");
  const btnLogout = document.getElementById("btnLogout");
  const btnBack = document.getElementById("btnBack");

  // LOGIN
  
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault(); // Evita recargar la página
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();
        const msg = document.getElementById("msg");

        try {
          const res = await fetch(`${baseUrl}/login.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password }),
          });

          const data = await res.json();

          if (!res.ok) throw new Error(data.error || "Error en login");

          localStorage.setItem("accessToken", data.token);

          // Redirección según rol
          if (data.user.role === "admin") {
            window.location.href = "../views/adminPanel.html";
          } else {
            window.location.href = "../views/dashboard.html";
          }
        } catch (err) {
          msg.textContent = err.message;
        }
      });
    }


  // DASHBOARD / ADMIN PANEL
  if (window.location.pathname.includes("dashboard.html")) {
    loadUserDashboard();
  }
  if (window.location.pathname.includes("adminPanel.html")) {
    loadAdminPanel();
  }

  // LOGOUT
  if (btnLogout) {
    btnLogout.addEventListener("click", async () => {
      const token = localStorage.getItem("accessToken");
      await fetch(`/public/api/auth/logout.php`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      localStorage.removeItem("accessToken");
      window.location.href = "./../index.php";
    });
  }

  if (btnBack) {
    btnBack.addEventListener("click", () => {
      window.location.href = "../views/dashboard.html";
    });
  }
});

async function loadUserDashboard() {
  const token = localStorage.getItem("accessToken");
  if (!token) return (window.location.href = "../views/login.html");

  const res = await fetch(`${baseUrl}/me.php`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  const data = await res.json();

  if (!res.ok) return (window.location.href = "../views/login.html");

  document.getElementById("userName").textContent = data.user.fullName;
  document.getElementById("userRole").textContent = data.user.role;
}

async function loadAdminPanel() {
  const token = localStorage.getItem("accessToken");
  if (!token) return (window.location.href = "../views/login.html");

  const res = await fetch(`/api/users/list.php`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  const data = await res.json();
  if (!res.ok || !data.ok) {
    alert("Acceso denegado, no tienes permiso para realizar esta acción.");
    return (window.location.href = "../views/login.html");
  }

  const tbody = document.querySelector("#userTable tbody");
  const adminInfo = document.getElementById("adminInfo");

  adminInfo.textContent = `Usuario: ${data.users[0].email} (Administrador)`;

  data.users.forEach((user) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${user.id}</td>
      <td>${user.fullName}</td>
      <td>${user.email}</td>
      <td>${user.role}</td>
    `;
    tbody.appendChild(row);
  });
}
