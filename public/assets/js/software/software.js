// public/assets/js/software/software.js
document.addEventListener("DOMContentLoaded", async () => {
  const API_BASE = "/api/software";
  const token = localStorage.getItem("accessToken");
  const restricted = document.getElementById("restrictedSection");
  const projectsSection = document.getElementById("projectsSection");
  const projectsContainer = document.getElementById("projectsContainer");
  const btnNew = document.getElementById("btnNewProject");
  const btnBack = document.getElementById("btnBack");
  if (btnBack) {
    btnBack.addEventListener("click", () => {
      window.location.href = "../dashboard.html";
    });
  }

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  // ================================
  // 🔹 Funciones utilitarias (fetch)
  // ================================

  // Fetch con JSON + Auth
  const fetchJSONAuth = async (url, options = {}) => {
    const res = await fetch(url, {
      ...options,
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
        ...(options.headers || {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || "Error en la solicitud");
    return data;
  };

  // Fetch con FormData + Auth
  const fetchFormAuth = async (url, formData) => {
    const res = await fetch(url, {
      method: "POST",
      headers: { Authorization: `Bearer ${token}` },
      body: formData,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || "Error al subir archivos");
    return data;
  };

  // ===================================
  // 🔹 Obtener contexto del usuario
  // ===================================
  let me;
  try {
    me = await fetchJSONAuth("/api/auth/me.php");
  } catch (err) {
    console.error("No se pudo obtener el usuario:", err);
    localStorage.removeItem("accessToken");
    window.location.href = "../../index.php";
    return;
  }
  const userRole = me.user.role.toLowerCase();

  // ===================================
  // 🔹 Cargar proyectos disponibles
  // ===================================
  try {
    const projects = await fetchJSONAuth(`${API_BASE}/list.php`);
    renderProjects(projects);
    projectsSection.classList.remove("d-none");
  } catch (err) {
    console.error("Error al cargar proyectos:", err);
    restricted.classList.remove("d-none");
  }

  // ===================================
  // 🔹 Modal de nuevo proyecto
  // ===================================
  const modal = new bootstrap.Modal(document.getElementById("newProjectModal"));
  btnNew.addEventListener("click", async () => {
    await loadLicenses();
    modal.show();
  });

  // ============================
  // 🔹 Cargar licencias
  // ============================
  async function loadLicenses() {
    const select = document.getElementById("licenseId");
    select.innerHTML = `<option value="">Cargando...</option>`;
    try {
      const res = await fetch(`${API_BASE}/licenses/list.php`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const licenses = await res.json();
      select.innerHTML =
        `<option value="">Seleccione...</option>` +
        licenses
          .map(
            (l) =>
              `<option value="${l.id}">${l.name} (${l.licenseKey})</option>`
          )
          .join("");
    } catch {
      select.innerHTML = `<option value="">Error al cargar</option>`;
    }
  }

  // ============================
  // 🔹 Validación de archivos
  // ============================
  const allowedExt = [
    "php", "py", "jar", "java", "c", "cpp", "js", "css", "html", "jsp", "class", "7z",
  ];
  const maxTotalBytes = 20 * 1024 * 1024;

  function validateFiles(files) {
    let total = 0;
    for (const f of files) {
      total += f.size;
      const ext = (f.name.split(".").pop() || "").toLowerCase();
      if (!allowedExt.includes(ext))
        return `Archivo no permitido: ${f.name}`;
    }
    if (total > maxTotalBytes)
      return `Tamaño total supera 20MB (actual: ${(total / 1024 / 1024).toFixed(2)} MB)`;
    return null;
  }

  // ============================
  // 🔹 Envío del formulario
  // ============================
  const form = document.getElementById("newProjectForm");
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const errBox = document.getElementById("formError");
    errBox.classList.add("d-none");

    const title = document.getElementById("title").value.trim();
    const description = document.getElementById("description").value.trim();
    const tagsRaw = document.getElementById("tags").value.trim();
    const licenseId = document.getElementById("licenseId").value;
    const readme = document.getElementById("readmeFile").files[0];
    const files = document.getElementById("projectFiles").files;

    if (!title || !licenseId || !readme || !files.length) {
      showError("Complete los campos obligatorios y adjunte los archivos requeridos.");
      return;
    }
    if (readme.name.toLowerCase() !== "readme.md") {
      showError("El archivo README debe llamarse exactamente README.md.");
      return;
    }
    const fileErr = validateFiles(files);
    if (fileErr) {
      showError(fileErr);
      return;
    }

    const tags = tagsRaw ? tagsRaw.split(",").map((t) => t.trim()) : [];

    try {
      const meta = await fetchJSONAuth(`${API_BASE}/create.php`, {
        method: "POST",
        body: JSON.stringify({ title, description, licenseId, tags }),
      });

      if (!meta.ok && !meta.projectId)
        throw new Error("Error creando metadatos");

      const fd = new FormData();
      fd.append("projectId", meta.projectId);
      fd.append("readme", readme);
      for (const f of files) fd.append("files[]", f);

      await fetchFormAuth(`${API_BASE}/uploadFiles.php`, fd);

      modal.hide();
      form.reset();

      const refreshed = await fetch(`${API_BASE}/list.php`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const updated = await refreshed.json();
      renderProjects(updated);
    } catch (err) {
      showError(err.message);
    }
  });

  function showError(msg) {
    const errBox = document.getElementById("formError");
    errBox.textContent = msg;
    errBox.classList.remove("d-none");
  }

  // ============================
  // 🔹 Renderizar proyectos
  // ============================
  function renderProjects(projects = []) {
    projectsContainer.innerHTML = "";
    if (!projects.length) {
      projectsContainer.innerHTML = `<p class="text-muted text-center">No hay proyectos registrados aún.</p>`;
      return;
    }

    projectsContainer.innerHTML = projects.map((p) => {
      const statusBadge = getStatusBadge(p.status);
      let actions = `
        <button class="btn btn-outline-primary btn-sm mt-2" onclick="viewProjectDetails(${p.id})">
          <i class="bi bi-eye"></i> Ver Detalles
        </button>
      `;

      // Mostrar botones de revisión si el rol lo permite
      if (["coordinator", "teacher", "depthead", "admin"].includes(userRole)) {
        actions += `
          <div class="mt-2">
            <button class="btn btn-success btn-sm me-2" onclick="updateStatus(${p.id}, 'approved')">
              <i class="bi bi-check-circle"></i> Aprobar
            </button>
            <button class="btn btn-danger btn-sm me-2" onclick="updateStatus(${p.id}, 'vetoed')">
              <i class="bi bi-x-circle"></i> Rechazar
            </button>
            <button class="btn btn-info btn-sm" onclick="updateStatus(${p.id}, 'published')">
              <i class="bi bi-globe"></i> Publicar
            </button>
          </div>
        `;
      }

      return `
        <div class="col-md-6 col-lg-4">
          <div class="card shadow-sm p-3 h-100">
            <h5 class="fw-bold">${p.title}</h5>
            <p class="text-muted mb-1">${p.author || "Autor desconocido"}</p>
            <p class="small">${p.description || "Sin descripción"}</p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge bg-secondary">${p.licenseName || "Sin licencia"}</span>
              ${statusBadge}
            </div>
            ${actions}
          </div>
        </div>
      `;
    }).join("");
  }

  // Badge de color según estado
  function getStatusBadge(status) {
    const map = {
      submitted: "secondary",
      under_review: "info",
      approved: "success",
      vetoed: "danger",
      published: "primary",
    };
    const label = {
      submitted: "Pendiente",
      under_review: "En revisión",
      approved: "Aprobado",
      vetoed: "Vetado",
      published: "Publicado",
    };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color}">${label[status] || status}</span>`;
  }
});

// =======================================
// 🔹 Función global updateStatus()
// =======================================
async function updateStatus(projectId, status) {
  const token = localStorage.getItem("accessToken");
  if (!confirm(`¿Seguro que deseas cambiar el estado a "${status}"?`)) return;
  try {
    const res = await fetch("/api/software/updateStatus.php", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ projectId, status }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Error al actualizar estado");
    alert("Estado actualizado correctamente.");
    location.reload();
  } catch (err) {
    alert(err.message);
  }
}

// =======================================
// 🔹 Función global viewProjectDetails()
// =======================================
async function viewProjectDetails(projectId) {
  const token = localStorage.getItem("accessToken");
  const modalEl = document.getElementById("viewProjectModal");
  const modalBody = modalEl.querySelector(".modal-body");
  const modalTitle = modalEl.querySelector(".modal-title");
  const modal = new bootstrap.Modal(modalEl);

  modalBody.innerHTML = "<p class='text-center text-muted'>Cargando detalles...</p>";
  modal.show();

  try {
    const res = await fetch(`/api/software/detail.php?id=${projectId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Error al obtener detalles");

    modalTitle.textContent = data.title || "Proyecto sin título";
    modalBody.innerHTML = `
      <p><strong>Autor:</strong> ${data.author}</p>
      <p><strong>Licencia:</strong> ${data.licenseName}</p>
      <p><strong>Estado:</strong> ${data.status}</p>
      <hr>
      <h6>Descripción</h6>
      <p>${data.description || "Sin descripción"}</p>
      <hr>
      <h6>README.md</h6>
      <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${data.readmeText || "No se encontró contenido de README.md."}</pre>
      <hr>
      <h6>Archivos adjuntos</h6>
      <ul id="filesList" class="list-group mb-3"></ul>
      ${data.files?.length
        ? `<ul id="filesList" class="list-group">
            ${data.files.map(f => `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${(f.fileName || '').replace(/[<>]/g, '')}</span>
                <a class="btn btn-sm btn-outline-primary" target="_blank"
                  href="/api/software/download.php?path=${encodeURIComponent(f.filePath)}">
                  Descargar
                </a>
              </li>`).join("")}
          </ul>`
        : `<p class="text-muted">No hay archivos registrados.</p>`}
      <hr>
      <h6>Tags</h6>
      ${data.tags?.length
        ? data.tags.map(t => `<span class="badge bg-info me-1">${t}</span>`).join("")
        : `<p class="text-muted">Sin etiquetas.</p>`}
    `;
  } catch (err) {
    modalBody.innerHTML = `<p class="text-danger text-center">${err.message}</p>`;
  }
    

  
}
