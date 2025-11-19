document.addEventListener("DOMContentLoaded", async () => {

  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  async function loadRecord() {
    const table = document.getElementById("recordTable");
    table.innerHTML = `
      <tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>
    `;

    try {
      const response = await fetch("/api/students/getAcademicRecord.php", {
        headers: {
          "Authorization": "Bearer " + token
        }
      });

      const data = await response.json();

      if (!data.ok) {
        table.innerHTML = `
          <tr><td colspan="6" class="text-center text-danger">No se pudo cargar el historial</td></tr>
        `;
        return;
      }

      const rows = data.academicRecord;

      if (!rows.length) {
        table.innerHTML = `
          <tr><td colspan="6" class="text-center text-muted">No hay registros académicos</td></tr>
        `;
        return;
      }

      let html = "";
      rows.forEach(r => {
        html += `
          <tr>
            <td>${r.subjectCode}</td>
            <td>${r.subjectName}</td>
            <td>${r.grade ?? "-"}</td>
            <td>${r.period}</td>
            <td>${r.status}</td>
            <td>${r.credits ?? "-"}</td>
          </tr>
        `;
      });

      table.innerHTML = html;

    } catch (e) {
      console.error("Error cargando historial:", e);
      table.innerHTML = `
        <tr><td colspan="6" class="text-center text-danger">Error en el servidor</td></tr>
      `;
    }
  }

  document.getElementById("btnReload").addEventListener("click", loadRecord);

  document.getElementById("btnLogout").addEventListener("click", () => {
    localStorage.removeItem("accessToken");
    window.location.href = "../../index.php";
  });

  loadRecord();
});
