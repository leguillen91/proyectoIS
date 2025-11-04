const API_URL = "/api/auth/register.php";
const token = localStorage.getItem("accessToken");

if (!token) {
  window.location.href = "./login.html";
}

document.getElementById("createUserForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const fullName = document.getElementById("fullName").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const role = document.getElementById("role").value.trim();

  if (!fullName || !email || !password || !role) {
    alert("Por favor completa todos los campos.");
    return;
  }

  try {
    const res = await fetch(API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify({ fullName, email, password, role })
    });

    const data = await res.json();

    if (!res.ok) throw new Error(data.error || "Error al crear usuario");

    alert(` Usuario creado correctamente (${role})`);
    document.getElementById("createUserForm").reset();
  } catch (err) {
    alert(" Error al crear el usuario" + err.message);
  }
});

// Logout
document.getElementById("btnLogout").addEventListener("click", async () => {
  await fetch("/api/auth/logout.php", { headers: { Authorization: `Bearer ${token}` } });
  localStorage.removeItem("accessToken");
  window.location.href = "./../index.php";
});
