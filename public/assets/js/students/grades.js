document.addEventListener("DOMContentLoaded", () => {

  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  const table = document.getElementById("gradesTable");
  const alertBox = document.getElementById("alertBox");

  // ============================================================
  // 1. CARGAR NOTAS
  // ============================================================
  async function loadGrades() {
    table.innerHTML = `
      <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
    `;

    try {
      const res = await fetch("/api/students/getGrades.php", {
        headers: { "Authorization": "Bearer " + token }
      });

      const data = await res.json();

      // Caso: NECESITA EVALUAR DOCENTES
      if (data.requiresEvaluation) {
        alertBox.innerHTML = `
          <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>
              <strong>No puedes ver tus notas.</strong> Debes completar la evaluación docente.
            </div>
            <a href="evaluation.html" class="btn btn-sm btn-primary">
              Ir a Evaluación
            </a>
          </div>
        `;

        table.innerHTML = `
          <tr><td colspan="5" class="text-center text-muted">Evalúa a tus docentes para ver las notas.</td></tr>
        `;
        return;
      }

      // Caso: error general
      if (!data.ok) {
        table.innerHTML = `
          <tr><td colspan="5" class="text-center text-danger">Error al cargar notas.</td></tr>
        `;
        return;
      }

      const grades = data.grades;

      if (!grades.length) {
        table.innerHTML = `
          <tr><td colspan="5" class="text-center text-muted">No hay notas disponibles.</td></tr>
        `;
        return;
      }

      let html = "";
      grades.forEach(g => {
        html += `
          <tr>
            <td>${g.subjectCode}</td>
            <td>${g.subjectName}</td>
            <td>${g.grade}</td>
            <td>${g.period}</td>
            <td>${g.status}</td>
          </tr>
        `;
      });

      table.innerHTML = html;

    } catch (e) {
      console.error(e);
      table.innerHTML = `
        <tr><td colspan="5" class="text-center text-danger">Error en el servidor.</td></tr>
      `;
    }
  }


  // Eventos
  document.getElementById("btnReload").addEventListener("click", loadGrades);

  document.getElementById("btnLogout").addEventListener("click", () => {
    localStorage.removeItem("accessToken");
    window.location.href = "../../index.php";
  });

  // Iniciar
  loadGrades();

});
