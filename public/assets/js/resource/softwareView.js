document.addEventListener("DOMContentLoaded", async () => {
  const token = localStorage.getItem("accessToken");
  if (!token) return;

  // 1️⃣ Obtener contexto del usuario
  const res = await fetch("/api/auth/me.php", { headers: { Authorization: `Bearer ${token}` } });
  const data = await res.json();
  const user = data?.user;
  if (!user) return;

  const role = user.role.toLowerCase();
  const container = document.getElementById("resourceContainer");

  // 2️⃣ Modificar comportamiento según rol
  const observer = new MutationObserver(() => {
    container.querySelectorAll(".card").forEach((card) => {
      const statusBadge = card.querySelector(".badge.bg-info, .badge.bg-secondary");
      const footer = card.querySelector(".card-footer");

      // --- Docente o Coordinador ---
      if (["teacher", "coordinator", "admin"].includes(role)) {
        if (!footer.querySelector(".btn-approve")) {
          const approveBtn = document.createElement("button");
          approveBtn.className = "btn btn-success btn-sm me-1 btn-approve";
          approveBtn.textContent = "Aprobar";
          approveBtn.addEventListener("click", () => changeStatus(card, "Approved"));

          const rejectBtn = document.createElement("button");
          rejectBtn.className = "btn btn-danger btn-sm me-1 btn-reject";
          rejectBtn.textContent = "Rechazar";
          rejectBtn.addEventListener("click", () => changeStatus(card, "Rejected"));

          const publishBtn = document.createElement("button");
          publishBtn.className = "btn btn-info btn-sm btn-publish";
          publishBtn.textContent = "Publicar";
          publishBtn.addEventListener("click", () => changeStatus(card, "Published"));

          footer.prepend(approveBtn, rejectBtn, publishBtn);
        }
      }

      // --- Estudiante ---
      if (role === "student") {
        // El estudiante solo puede editar/eliminar los suyos
        // Verifica si el autor coincide con su nombre
        const title = card.querySelector(".card-title")?.textContent || "";
        if (!title.toLowerCase().includes(user.fullName.toLowerCase())) {
          // Bloquear edición/eliminación
          const btns = footer.querySelectorAll(".btn-outline-primary, .btn-outline-danger");
          btns.forEach(b => b.remove());
        }
      }
    });
  });

  observer.observe(container, { childList: true, subtree: true });

  // 3️⃣ Cambiar estado del recurso
  async function changeStatus(card, status) {
    const id = card.querySelector("button").dataset.id;
    if (!confirm(`¿Seguro que deseas marcar este recurso como ${status}?`)) return;

    try {
      const res = await fetch("/api/software/changeStatus.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ idResource: id, newStatus: status }),
      });

      const result = await res.json();
      if (result.ok) {
        alert(`✅ Estado actualizado a ${status}`);
        card.querySelector(".badge").className = "badge bg-success text-uppercase";
        card.querySelector(".badge").textContent = status;
      } else {
        alert(`❌ Error: ${result.error}`);
      }
    } catch (err) {
      console.error("Error cambiando estado:", err);
      alert("⚠️ No se pudo cambiar el estado");
    }
  }
});
