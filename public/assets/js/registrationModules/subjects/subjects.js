document.addEventListener("DOMContentLoaded", async () => {

  /* ============================================================
     CONFIGURACIÓN GENERAL
  ============================================================ */

  const token = localStorage.getItem("accessToken");
  if (!token) {
    window.location.href = "/index.php";
    return;
  }

  const authHeaders = {
    "Authorization": "Bearer " + token,
    "Content-Type": "application/json"
  };

  const tableBody = document.querySelector("#subjectsTable");
  const searchInput = document.querySelector("#searchInput");

  const modalCreate = new bootstrap.Modal("#modalCreate");
  const modalEdit = new bootstrap.Modal("#modalEdit");

  /* ============================================================
     FUNCIONES DE ALERTAS BOOTSTRAP
  ============================================================ */

  function showAlert(message, type = "success") {
    const div = document.createElement("div");
    div.className = `alert alert-${type}`;
    div.innerText = message;
    document.querySelector(".main-content").prepend(div);
    setTimeout(() => div.remove(), 3000);
  }

  /* ============================================================
     CARGAR DEPARTAMENTOS
  ============================================================ */

  async function loadDepartments() {
    const res = await fetch("/api/registrationModule/subjects/listDepartments.php", {
      headers: authHeaders
    });

    const data = await res.json();

    if (!data.ok) {
      showAlert("Error cargando departamentos", "danger");
      return;
    }

    const options = `
      <option value="">Seleccione un departamento</option>
      ${data.departments
        .map(d => `<option value="${d.id}">${d.name}</option>`)
        .join("")}
    `;

    document.getElementById("createDepartment").innerHTML = options;
    document.getElementById("editDepartment").innerHTML = options;
  }

  await loadDepartments();

  /* ============================================================
     CARGAR MATERIAS (MEGA CONSULTA)
  ============================================================ */

  async function loadSubjects() {
    const res = await fetch("/api/registrationModule/subjects/listAllWithCareerInfo.php", {
      headers: authHeaders
    });

    const data = await res.json();

    if (!data.ok) {
      showAlert("Error cargando materias.", "danger");
      return;
    }

    renderSubjectsTable(data.subjects);
  }

  loadSubjects();

  /* ============================================================
     RENDERIZAR TABLA COMPLETA
  ============================================================ */

  function renderSubjectsTable(subjects) {

    tableBody.innerHTML = "";

    let rows = "";

    // Agrupar carreras por materia
    const grouped = {};

    subjects.forEach(s => {
      if (!grouped[s.subjectId]) {
        grouped[s.subjectId] = {
          ...s,
          careers: []
        };
      }

      if (s.careerId) {
        grouped[s.subjectId].careers.push({
          id: s.careerId,
          name: s.careerName,
          semester: s.semester
        });
      }
    });

    Object.values(grouped).forEach((s, i) => {

      const careerBadges = s.careers.length
        ? s.careers.map(c =>
            `<span class="badge-career">${c.name} <span class="badge-semester">S${c.semester}</span></span>`
          ).join("")
        : `<span class="text-muted">Sin carrera</span>`;

      rows += `
        <tr>
          <td>${i + 1}</td>
          <td>${s.code}</td>
          <td>${s.name}</td>
          <td>${s.uv}</td>
          <td>${s.departmentName ?? "N/D"}</td>

          <td>${careerBadges}</td>

          <td>
            <button class="btn btn-warning btn-sm" onclick="editSubject(${s.subjectId})">
              <i class="bi bi-pencil"></i>
            </button>

            <button class="btn btn-danger btn-sm" onclick="deleteSubject(${s.subjectId})">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });

    tableBody.innerHTML = rows;
  }

  /* ============================================================
     BUSCAR MATERIAS
  ============================================================ */

  searchInput.addEventListener("input", async () => {
    const keyword = searchInput.value.trim();

    if (keyword === "") {
      loadSubjects();
      return;
    }

    const res = await fetch("/api/registrationModule/subjects/search.php?keyword=" + keyword, {
      headers: authHeaders
    });

    const data = await res.json();

    if (!data.ok) return;

    renderSubjectsTable(data.subjects);
  });

  /* ============================================================
     CREAR MATERIA
  ============================================================ */

  document.getElementById("btnNew").addEventListener("click", () => {
    document.getElementById("createCode").value = "";
    document.getElementById("createName").value = "";
    document.getElementById("createUv").value = "";
    document.getElementById("createDepartment").value = "";

    modalCreate.show();
  });

  document.getElementById("btnSaveCreate").addEventListener("click", async () => {

    const payload = {
      code: document.getElementById("createCode").value,
      name: document.getElementById("createName").value,
      uv: document.getElementById("createUv").value,
      departmentId: document.getElementById("createDepartment").value
    };

    const res = await fetch("/api/registrationModule/subjects/create.php", {
      method: "POST",
      headers: authHeaders,
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (!data.ok) {
      showAlert(data.error || "Error al crear la materia", "danger");
      return;
    }

    showAlert("Materia creada correctamente");
    modalCreate.hide();
    loadSubjects();
  });

  /* ============================================================
     EDITAR MATERIA
  ============================================================ */

  window.editSubject = async (id) => {

    // Obtener datos desde la tabla
    const res = await fetch("/api/registrationModule/subjects/list.php", {
      headers: authHeaders
    });

    const data = await res.json();
    const subject = data.subjects.find(s => s.id == id);

    if (!subject) {
      showAlert("No se encontró la materia.", "danger");
      return;
    }

    document.getElementById("editId").value = subject.id;
    document.getElementById("editCode").value = subject.code;
    document.getElementById("editName").value = subject.fullName ?? subject.name;
    document.getElementById("editUv").value = subject.uv;
    document.getElementById("editDepartment").value = subject.departmentId;

    modalEdit.show();
  };

  document.getElementById("btnSaveEdit").addEventListener("click", async () => {

    const payload = {
      id: document.getElementById("editId").value,
      code: document.getElementById("editCode").value,
      name: document.getElementById("editName").value,
      uv: document.getElementById("editUv").value,
      departmentId: document.getElementById("editDepartment").value
    };

    const res = await fetch("/api/registrationModule/subjects/update.php", {
      method: "PUT",
      headers: authHeaders,
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (!data.ok) {
      showAlert(data.error || "Error al actualizar", "danger");
      return;
    }

    showAlert("Materia actualizada correctamente");
    modalEdit.hide();
    loadSubjects();
  });

  /* ============================================================
     ELIMINAR MATERIA
  ============================================================ */

  window.deleteSubject = async (id) => {

    if (!confirm("¿Seguro que desea eliminar esta materia?")) return;

    const res = await fetch("/api/registrationModule/subjects/delete.php", {
      method: "DELETE",
      headers: authHeaders,
      body: JSON.stringify({ id })
    });

    const data = await res.json();

    if (!data.ok) {
      showAlert(data.error || "Error al eliminar", "danger");
      return;
    }

    showAlert("Materia eliminada correctamente");
    loadSubjects();
  };

});
