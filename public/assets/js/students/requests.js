document.addEventListener("DOMContentLoaded", () => {

  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  const table = document.getElementById("requestsTable");
  const btnSendRequest = document.getElementById("btnSendRequest");


  // ========================================================
  // 1. CARGAR SOLICITUDES
  // ========================================================
  async function loadRequests() {
    table.innerHTML = `
      <tr>
        <td colspan="4" class="text-center text-muted">Cargando...</td>
      </tr>
    `;

    try {
      const res = await fetch("/api/students/listRequests.php", {
        headers: {
          "Authorization": "Bearer " + token
        }
      });

      const data = await res.json();

      if (!data.ok) {
        table.innerHTML = `
          <tr>
            <td colspan="4" class="text-center text-danger">Error al cargar solicitudes</td>
          </tr>
        `;
        return;
      }

      const rows = data.requests;

      if (!rows.length) {
        table.innerHTML = `
          <tr>
            <td colspan="4" class="text-center text-muted">No hay solicitudes</td>
          </tr>
        `;
        return;
      }

      let html = "";
      rows.forEach(r => {
        html += `
          <tr>
            <td>${formatType(r.type)}</td>
            <td>${r.reason}</td>
            <td>${r.status || "Pendiente"}</td>
            <td>${r.createdAt}</td>
          </tr>
        `;
      });

      table.innerHTML = html;

    } catch (e) {
      console.error(e);
      table.innerHTML = `
        <tr>
          <td colspan="4" class="text-center text-danger">Error en el servidor</td>
        </tr>
      `;
    }
  }


  // ========================================================
  // 2. ENVIAR SOLICITUD
  // ========================================================
  btnSendRequest.addEventListener("click", async () => {

    const type = document.getElementById("requestType").value;
    const reason = document.getElementById("requestReason").value.trim();

    if (!type || !reason) {
      alert("Debe completar el tipo y la justificación.");
      return;
    }

    try {
      const res = await fetch("/api/students/createRequest.php", {
        method: "POST",
        headers: {
          "Authorization": "Bearer " + token,
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ type, reason })
      });

      const data = await res.json();

      if (data.ok) {
        alert("Solicitud enviada correctamente.");
        loadRequests();
        document.getElementById("requestReason").value = "";
        document.getElementById("requestType").value = "";
      } else {
        alert("Error al enviar la solicitud.");
      }

    } catch (e) {
      console.error(e);
      alert("Error en el servidor");
    }
  });


  // ========================================================
  // 3. FORMATEAR TIPO
  // ========================================================
  function formatType(t) {
    const map = {
      cambio_carrera: "Cambio de carrera",
      revision_nota: "Revisión de nota",
      cancelacion_excepcional: "Cancelación excepcional",
      pago_reposicion: "Pago de reposición",
      problema_administrativo: "Problema administrativo",
      otro: "Otro"
    };
    return map[t] ?? t;
  }


  // ========================================================
  // 4. LOGOUT
  // ========================================================
  document.getElementById("btnLogout").addEventListener("click", () => {
    localStorage.removeItem("accessToken");
    window.location.href = "../../index.php";
  });


  // ========================================================
  // INICIO
  // ========================================================
  loadRequests();
});
