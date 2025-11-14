// =============================================================
// 🔹 Project UNAH Systems - Módulo Software
// =============================================================
document.addEventListener("DOMContentLoaded", async () => {
  const API_BASE = "/api/resource";
  const token = localStorage.getItem("accessToken");
  if (!token) {
    window.location.href = "./../login.html";
    return;
  }
//public\assets\js\librarySoftware\librarySoftware.js
  // Elementos base
  const myProjects = document.getElementById("myProjects");
  const allProjects = document.getElementById("allProjects");
  const btnNew = document.getElementById("btnNew");
  const modalCreate = new bootstrap.Modal(document.getElementById("modalCreate"));
  const modalDetail = new bootstrap.Modal(document.getElementById("modalDetail"));
  const searchInput = document.getElementById("searchInput");
  const formError = document.getElementById("formError");
  const btnBack = document.getElementById("btnBack");

  if (btnBack) {
    btnBack.addEventListener("click", () => (window.location.href = "../../index.php"));
  }

  let resources = [];
  let userCtx = null;
  let getAuthors = null;
  let getTags = null;
  let editingId = null;

  // ==========================================================
  //  Bootstrap Alerts Helper
  // ==========================================================
  function showAlert(message, type = "info", timeout = 3500) {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow`;
    alert.style.zIndex = 2000;
    alert.innerHTML = `
      <strong>${type === "success" ? "✅" : type === "danger" ? "❌" : "ℹ️"} </strong> ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    setTimeout(() => {
      alert.classList.remove("show");
      setTimeout(() => alert.remove(), 400);
    }, timeout);
  }

  // ==========================================================
  // 🔸 Fetch utilitario
  // ==========================================================
  async function apiFetch(endpoint, options = {}) {
    const res = await fetch(`${API_BASE}/${endpoint}`, {
      headers: { Authorization: `Bearer ${token}`, ...(options.headers || {}) },
      ...options,
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error("Respuesta no JSON:", text);
      throw new Error("Respuesta inválida del servidor");
    }
  }

  // ==========================================================
  //  Contexto de usuario
  // ==========================================================
  async function getUserContext() {
    const res = await fetch("/api/auth/me.php", {
      headers: { Authorization: `Bearer ${token}` },
    });
    showAlert("No tiene acceso a este modulo", "warning");
    window.location.href = "./../login.html";
    const data = await res.json();
    userCtx = data.user;
  }

  // ==========================================================
  //  Cargar metadata + inicializar chips
  // ==========================================================
  async function loadMetadata(retry = 0) {
    try {
      const authorsInput = document.getElementById("authorsField");
      const tagsInput = document.getElementById("tagsField");
      const licenseSelect = document.getElementById("licenseId");

      if (!authorsInput || !tagsInput || !licenseSelect) {
        if (retry < 10) return setTimeout(() => loadMetadata(retry + 1), 300);
        throw new Error("Campos de metadata no encontrados");
      }

      const res = await fetch("/api/resource/metadata.php?module=Software");
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Error metadata");
      const { authors, tags, licenses } = data.data;

      licenseSelect.innerHTML = licenses
        .map((l) => `<option value="${l.idLicense}">${l.name}</option>`)
        .join("");

      getAuthors = initChipAutocomplete(authorsInput, authors);
      getTags = initChipAutocomplete(tagsInput, tags);
      console.log("Metadata cargada correctamente", "success");
    } catch (err) {
      console.error("❌ Error metadata:", err);
      
    }
  }

  // ==========================================================
  //  Chips con autocompletado
  // ==========================================================
  function initChipAutocomplete(input, suggestions = []) {
    if (!input || !input.parentNode) return null;

    const container = document.createElement("div");
    container.className = "chip-container form-control d-flex flex-wrap align-items-center";
    input.parentNode.insertBefore(container, input);
    container.appendChild(input);

    const selected = [];

    function addChip(value) {
      if (!value || selected.includes(value)) return;
      selected.push(value);

      const chip = document.createElement("span");
      chip.className = "badge bg-primary text-white me-1 mb-1 d-flex align-items-center";
      chip.innerHTML = `
        ${value}
        <button type="button" class="btn-close btn-close-white ms-2" style="font-size:.6rem;"></button>
      `;
      chip.querySelector("button").addEventListener("click", () => {
        selected.splice(selected.indexOf(value), 1);
        chip.remove();
      });
      container.insertBefore(chip, input);
      input.value = "";
    }

    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === ",") {
        e.preventDefault();
        addChip(input.value.trim());
      }
    });

    const list = document.createElement("div");
    list.className = "list-group position-absolute shadow-sm w-100 mt-1";
    list.style.zIndex = 1000;
    list.style.display = "none";
    input.parentNode.appendChild(list);

    input.addEventListener("input", () => {
      const term = input.value.trim().toLowerCase();
      if (term.length < 2) return (list.style.display = "none");
      const matches = suggestions.filter(
        (s) => s.toLowerCase().includes(term) && !selected.includes(s)
      );
      if (!matches.length) return (list.style.display = "none");
      list.innerHTML = matches
        .map((m) => `<button type="button" class="list-group-item list-group-item-action py-1">${m}</button>`)
        .join("");
      list.querySelectorAll("button").forEach((btn) =>
        btn.addEventListener("click", () => {
          addChip(btn.textContent.trim());
          list.style.display = "none";
        })
      );
      list.style.display = "block";
    });

    document.addEventListener("click", (e) => {
      if (!container.contains(e.target)) list.style.display = "none";
    });

    return {
      setValues(values = []) {
        selected.splice(0, selected.length);
        container.querySelectorAll(".badge").forEach((c) => c.remove());
        values.forEach((v) => addChip(v));
      },
      getValues() {
        return [...selected];
      },
    };
  }

  // ==========================================================
  // Abrir modal "Nuevo Proyecto"
  // ==========================================================
  if (btnNew) {
    btnNew.addEventListener("click", () => {
      editingId = null;
      document.getElementById("newProjectForm").reset();
      formError.classList.add("d-none");
      modalCreate.show();
    });
  }
  // ==========================================================
  //  Cargar y renderizar proyectos
  // ==========================================================
  async function loadProjects() {
    try {
      const res = await apiFetch(`list.php?module=Software`);
      if (!res.ok) throw new Error(res.error || "Error al cargar proyectos");
      const data = res.data || [];
      resources = [];

      for (const r of data) {
        const detail = await apiFetch(`detail.php?id=${r.idResource}`);
        if (detail.ok) resources.push(detail.data);
      }

      renderProjects();
      console.log("Proyectos cargados correctamente.", "success");
    } catch (err) {
      console.error("❌ Error al cargar proyectos:", err);
      showAlert("Error al cargar proyectos: " + err.message, "danger");
    }
  }

  // ==========================================================
  // 🧩 Renderizar tarjetas
  // ==========================================================
  function renderProjects() {
    const role = userCtx.role.toLowerCase();
    const userId = userCtx.userId;
    const myList = [];
    const publicList = [];

    resources.forEach((r) => {
      const isOwner = r.createdByPersonId === userId;
      const isAdmin = ["admin", "coordinator", "teacher"].includes(role);
      const isPublished = r.status === "Approved";
      if (isOwner || isAdmin) myList.push(r);
      if (isPublished) publicList.push(r);
    });

    myProjects.innerHTML = generateCards(myList, true);
    allProjects.innerHTML = generateCards(publicList, false);
  }

  // ==========================================================
  // 🧩 Generar tarjetas de proyectos
  // ==========================================================
  function generateCards(list, showActions) {
    if (!list.length)
      return `<p class="text-muted text-center">No hay proyectos disponibles.</p>`;

    return list
      .map((r) => {
        const color = getStatusColor(r.status);
        const isOwner = r.createdByPersonId === userCtx.userId;
        const isAdmin = ["admin", "teacher", "coordinator"].includes(
          userCtx.role.toLowerCase()
        );
        const canEdit = isOwner || isAdmin;

        const actions =
          showActions && canEdit
            ? `
          <div class="d-flex justify-content-end gap-2 mt-2">
            <button class="btn btn-sm btn-outline-primary" onclick="editProject(${r.idResource})">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteProject(${r.idResource})">
              <i class="bi bi-trash"></i>
            </button>
          </div>`
            : "";

        return `
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <h5 class="card-title text-primary">${r.title}</h5>
              <p class="card-text small text-muted">${r.description || "Sin descripción"}</p>
              <p class="card-text small mb-2"><strong>Autores:</strong> ${
                (r.authors || []).map((a) => a.authorName).join(", ") || "Sin autores"
              }</p>
              <p class="card-text small mb-2"><strong>Tags:</strong> ${
                (r.tags || []).map((t) => t.name).join(", ") || "Sin etiquetas"
              }</p>
              <span class="badge bg-${color}">${r.status}</span>
              <button class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="viewProjectDetails(${r.idResource})">
                <i class="bi bi-eye"></i> Ver Detalles
              </button>
              ${actions}
            </div>
          </div>
        </div>`;
      })
      .join("");
  }

  // ==========================================================
  // 🔍 Buscador global (Mis proyectos + Biblioteca)
  // ==========================================================
  searchInput.addEventListener("input", (e) => {
    const term = e.target.value.trim().toLowerCase();
    if (!term) return renderProjects();

    const filtered = resources.filter((r) => {
      const t = r.title?.toLowerCase().includes(term);
      const a = r.authors?.some((x) =>
        x.authorName.toLowerCase().includes(term)
      );
      const g = r.tags?.some((tg) => tg.name.toLowerCase().includes(term));
      return t || a || g;
    });

    const role = userCtx.role.toLowerCase();
    const userId = userCtx.userId;
    const myList = filtered.filter(
      (r) =>
        r.createdByPersonId === userId ||
        ["admin", "teacher", "coordinator"].includes(role)
    );
    const publicList = filtered.filter((r) => r.status === "Approved");

    myProjects.innerHTML = generateCards(myList, true);
    allProjects.innerHTML = generateCards(publicList, false);
  });

  // ==========================================================
  // 🧩 Crear o Actualizar Proyecto
  // ==========================================================
  document.getElementById("newProjectForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    formError.classList.add("d-none");

    const title = document.getElementById("title").value.trim();
    const description = document.getElementById("description").value.trim();
    const licenseId = document.getElementById("licenseId").value;
    const readmeFile = document.getElementById("readmeFile").files[0];
    const files = Array.from(document.getElementById("projectFiles").files);

    if (!title || !licenseId) {
      formError.textContent = "Completa los campos requeridos.";
      formError.classList.remove("d-none");
      return;
    }

    try {
      const payload = {
        title,
        description,
        module: "Software",
        resourceTypeId: 2,
        licenseId: parseInt(licenseId),
        authors: getAuthors.getValues().map((a) => ({
          authorName: a,
          role: "Author",
        })),
        tags: getTags.getValues(),
      };

      const endpoint = editingId ? "update.php" : "create.php";
      if (editingId) payload.idResource = editingId;

      const res = await apiFetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (!res.ok) throw new Error(res.error || "Error al guardar el proyecto");
      const resourceId = res.data?.idResource || editingId;

      // Subir README y archivos
      if (readmeFile) {
        const fd = new FormData();
        fd.append("resourceId", resourceId);
        fd.append("fileKind", "Readme");
        fd.append("file", readmeFile);
        await fetch(`${API_BASE}/uploadFile.php`, {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
          body: fd,
        });
      }

      if (files.length > 0) {
        for (const f of files) {
          const fd = new FormData();
          fd.append("resourceId", resourceId);
          fd.append("fileKind", "Primary");
          fd.append("file", f);
          await fetch(`${API_BASE}/uploadFile.php`, {
            method: "POST",
            headers: { Authorization: `Bearer ${token}` },
            body: fd,
          });
        }
      }

      showAlert(`Proyecto ${editingId ? "actualizado" : "creado"} correctamente.`, "success");
      modalCreate.hide();
      editingId = null;
      await loadProjects();
    } catch (err) {
      console.error(err);
      formError.textContent = err.message;
      formError.classList.remove("d-none");
      showAlert("Error al guardar proyecto: " + err.message, "danger");
    }
  });
  // ==========================================================
  // 📖 Ver detalles del proyecto
  // ==========================================================
  window.viewProjectDetails = async function (projectId) {
    const modalEl = document.getElementById("modalDetail");
    const modalBody = modalEl.querySelector(".modal-body");
    const modalTitle = modalEl.querySelector(".modal-title");
    const modal = new bootstrap.Modal(modalEl);

    modalBody.innerHTML = "<p class='text-center text-muted'>Cargando detalles...</p>";
    modal.show();

    try {
      const res = await apiFetch(`detail.php?id=${projectId}`);
      if (!res.ok) throw new Error(res.error || "Error al obtener detalles");
      const d = res.data;

      modalTitle.textContent = d.title || "Proyecto sin título";
      modalBody.innerHTML = `
        <p><strong>Autor(es):</strong> ${(d.authors || []).map(a => a.authorName).join(", ") || "Sin autores"}</p>
        <p><strong>Licencia:</strong> ${d.licenseName || "Sin licencia"}</p>
        <p><strong>Estado:</strong> <span class="badge bg-${getStatusColor(d.status)}">${d.status}</span></p>
        <hr>
        <h6>Descripción</h6>
        <p>${d.description || "Sin descripción"}</p>
        <hr>
        <h6>README.md</h6>
        <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${d.readmeText || "No se encontró contenido de README.md."}</pre>
        <hr>
        <h6>Archivos adjuntos</h6>
        ${
          d.files?.length
            ? `<ul class="list-group mb-3">
                ${d.files
                  .map(
                    (f) => `
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${f.originalFilename}</span>
                    <a class="btn btn-sm btn-outline-primary" target="_blank"
                      href="/api/resource/download.php?id=${f.idResourceFile}">
                      Descargar
                    </a>
                  </li>`
                  )
                  .join("")}
              </ul>`
            : `<p class="text-muted">No hay archivos registrados.</p>`
        }
        <h6>Tags</h6>
        ${
          d.tags?.length
            ? d.tags.map((t) => `<span class="badge bg-info me-1">${t.name}</span>`).join("")
            : `<p class="text-muted">Sin etiquetas.</p>`
        }
      `;

      const footer = modalEl.querySelector(".modal-footer");
      const isOwner = d.createdByPersonId === userCtx.userId;
      const isAdmin = ["admin", "teacher", "coordinator"].includes(
        userCtx.role.toLowerCase()
      );

      footer.innerHTML = `
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        ${
          isOwner || isAdmin
            ? `
        <button class="btn btn-primary" onclick="editProject(${d.idResource})">
          <i class="bi bi-pencil"></i> Editar
        </button>
        <button class="btn btn-danger" onclick="deleteProject(${d.idResource})">
          <i class="bi bi-trash"></i> Eliminar
        </button>`
            : ""
        }
        ${
          isAdmin
            ? `
        <button class="btn btn-success" onclick="changeStatus(${d.idResource}, 'Approved')">
          <i class="bi bi-check2-circle"></i> Aprobar
        </button>
        <button class="btn btn-warning" onclick="changeStatus(${d.idResource}, 'Rejected')">
          <i class="bi bi-x-circle"></i> Rechazar
        </button>`
            : ""
        }
      `;
    } catch (err) {
      modalBody.innerHTML = `<p class="text-danger text-center">${err.message}</p>`;
    }
  };

  // ==========================================================
  // ✏️ Editar proyecto
  // ==========================================================
  window.editProject = async function (id) {
    try {
      const detail = await apiFetch(`detail.php?id=${id}`);
      if (!detail.ok) throw new Error("Error al obtener detalles del proyecto");
      const d = detail.data;

      editingId = id;
      document.getElementById("title").value = d.title || "";
      document.getElementById("description").value = d.description || "";
      document.getElementById("licenseId").value = d.licenseId || "";
      getAuthors?.setValues((d.authors || []).map((a) => a.authorName));
      getTags?.setValues((d.tags || []).map((t) => t.name));

      // Ocultar modal de detalles si está abierto
      const detailModalEl = document.getElementById("modalDetail");
      const detailModal = bootstrap.Modal.getInstance(detailModalEl);
      if (detailModal) detailModal.hide();

      modalCreate.show();
      showAlert("Modo edición activado", "info");
    } catch (err) {
      console.error("❌ Error al editar:", err);
      showAlert("Error al editar: " + err.message, "danger");
    }
  };

  // ==========================================================
  // 🔄 Cambiar estado del proyecto
  // ==========================================================
  window.changeStatus = async function (resourceId, decision) {
    const comments = prompt(`Ingrese comentario para ${decision}:`);
    if (comments === null) return;

    try {
      const res = await apiFetch("updateStatus.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ resourceId, decision, comments }),
      });

      if (!res.ok) throw new Error(res.error || "Error al actualizar estado");

      showAlert(`Proyecto ${decision.toLowerCase()} correctamente.`, "success");

      // Cerrar modal activo
      const openModal = document.querySelector(".modal.show");
      if (openModal) {
        const instance = bootstrap.Modal.getInstance(openModal);
        if (instance) instance.hide();
      }

      await loadProjects();
    } catch (err) {
      console.error("❌ Error:", err);
      showAlert("Error al actualizar estado: " + err.message, "danger");
    }
  };

  // ==========================================================
  // 🗑️ Eliminar proyecto
  // ==========================================================
  window.deleteProject = async function (id) {
    if (!confirm("¿Seguro que deseas eliminar este proyecto?")) return;

    try {
      const res = await apiFetch(`delete.php?id=${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error(res.error || "Error al eliminar proyecto");

      showAlert("🗑️ Proyecto eliminado correctamente.", "success");

      const openModal = document.querySelector(".modal.show");
      if (openModal) {
        const instance = bootstrap.Modal.getInstance(openModal);
        if (instance) instance.hide();
      }

      await loadProjects();
    } catch (err) {
      console.error(err);
      showAlert("Error al eliminar: " + err.message, "danger");
    }
  };

  // ==========================================================
  // 🎨 Color de estado
  // ==========================================================
  function getStatusColor(status) {
    switch (status) {
      case "Approved": return "success";
      case "Rejected": return "danger";
      case "Draft": return "secondary";
      default: return "info";
    }
  }

  // ==========================================================
  // 🚀 Inicialización
  // ==========================================================
  await getUserContext();
  await loadMetadata();
  await loadProjects();
});
