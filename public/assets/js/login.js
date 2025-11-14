const API_AUTH = "/api/auth";

document.addEventListener("DOMContentLoaded", () => {
  const token = localStorage.getItem("accessToken");
  if (token) {
    // Si ya hay sesión, vamos al dashboard
    window.location.href = "../index.php";
    return;
  }

  const form = document.getElementById("loginForm");
  const msg = document.getElementById("msg");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    msg.textContent = "";

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    try {
      const res = await fetch(`${API_AUTH}/login.php`, {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Error en login");

      localStorage.setItem("accessToken", data.token);

      // Redirección por rol
      if (data.user?.role === "admin") {
        window.location.href = "./dashboard.html";
      } else {
        window.location.href = "../index.php";
      }
    } catch (err) {
      msg.textContent = err.message;
    }
  });
});
