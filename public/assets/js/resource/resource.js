
document.addEventListener("DOMContentLoaded", () => {
  const API_BASE = "/api/resource";
  const token = localStorage.getItem("accessToken");
  if (!token) {
    window.location.href = "/index.php";
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const module = params.get("module") || "Software";

  const moduleInput = document.getElementById("moduleInput");
  if (moduleInput) moduleInput.value = module;

  const moduleTitle = document.getElementById("moduleTitle");
  moduleTitle.textContent = `Módulo: ${module}`;

  const container = document.getElementById("resourceContainer");
  const btnNew = document.getElementById("btnNew");
  const modalCreate = new bootstrap.Modal(document.getElementById("modalCreate"));
  let selectedResource = null;

  // ================================
  // Utilitario fetch con token
  // ================================
  async function apiFetch(endpoint, options = {}) {
    const res = await fetch(`${API_BASE}/${endpoint}`, {
      headers: {
        "Authorization": `Bearer ${token}`,
        ...(options.headers || {})
      },
      ...options
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error("Response was not JSON:", text);
      throw new Error("Invalid JSON response");
    }
  }

  // ================================
  // Cargar lista de recursos
  // ================================
  async function loadResources() {
    try {
      const data = await apiFetch(`list.php?module=${module}`);
      if (!data.ok) throw new Error(data.error || "No se pudieron obtener los recursos");

      container.innerHTML = "";

      if (!data.data || data.data.length === 0) {
        container.innerHTML = `<p class="text-muted">No hay recursos registrados en este módulo.</p>`;
        return;
      }

      data.data.forEach(r => {
        const card = document.createElement("div");
        card.className = "col-md-3";
        card.innerHTML = `
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title text-capitalize">${r.title}</h5>
              <p class="card-text small text-muted">${r.description || ""}</p>
              <span class="badge bg-info text-uppercase">${r.status}</span>
            </div>
            <div class="card-footer bg-white text-end">
              <button class="btn btn-outline-primary btn-sm" data-id="${r.idResource}">Ver</button>
            </div>
          </div>
        `;
        card.querySelector("button").addEventListener("click", () => openDetail(r.idResource));
        container.appendChild(card);
      });
    } catch (err) {
      console.error(err);
      container.innerHTML = `<p class="text-danger">Error cargando recursos: ${err.message}</p>`;
    }
  }

  // ================================
  // Ver detalles del recurso
  // ================================
  async function openDetail(id) {
    try {
      const data = await apiFetch(`detail.php?id=${id}`);
      const d = data.data;
      selectedResource = d;

      // Rellenar campos del modal
      document.getElementById("detailTitle").textContent = d.title || "";
      document.getElementById("detailDescription").textContent = d.description || "";
      document.getElementById("detailType").textContent = d.typeName || "No especificado";
      document.getElementById("detailLicense").textContent = d.licenseName || "Sin licencia";
      document.getElementById("detailAuthors").textContent = (d.authors || []).map(a => a.authorName).join(", ");
      document.getElementById("detailTags").textContent = (d.tags || []).map(t => t.name).join(", ");

      const fileContainer = document.getElementById("detailFiles");
      if (!d.files || d.files.length === 0) {
        fileContainer.innerHTML = `<p class="text-muted mb-0">Sin archivos adjuntos.</p>`;
      } else {
        fileContainer.innerHTML = d.files.map(f => `
          <div class="d-flex justify-content-between align-items-center border-bottom py-1">
            <span>${f.originalFilename}</span>
            <div>
              <a href="/api/resource/preview.php?id=${f.idResourceFile}" target="_blank" class="btn btn-sm btn-outline-secondary me-2">Ver</a>
              <a href="/api/resource/download.php?id=${f.idResourceFile}" class="btn btn-sm btn-outline-primary">Descargar</a>
            </div>
          </div>
        `).join("");
      }

      // Mostrar modal de detalle
      const modalDetailEl = document.getElementById("modalDetail");
      let modalInstance = bootstrap.Modal.getInstance(modalDetailEl);
      if (!modalInstance) modalInstance = new bootstrap.Modal(modalDetailEl);
      modalInstance.show();

      // Limpiar eventos previos
      const btnEdit = document.getElementById("btnEdit");
      const btnDelete = document.getElementById("btnDelete");
      btnEdit.replaceWith(btnEdit.cloneNode(true));
      btnDelete.replaceWith(btnDelete.cloneNode(true));

      const newBtnEdit = document.getElementById("btnEdit");
      const newBtnDelete = document.getElementById("btnDelete");

      // --- Editar ---
      newBtnEdit.addEventListener("click", async () => {
        const form = document.getElementById("formCreate");
        form.reset();

        form.querySelector('[name="resourceId"]').value = d.idResource;
        form.querySelector('[name="title"]').value = d.title || "";
        form.querySelector('[name="description"]').value = d.description || "";
        form.querySelector('[name="licenseId"]').value = d.licenseId || "";
        

        if (form.querySelector('[name="resourceTypeId"]') && d.resourceTypeId)
          form.querySelector('[name="resourceTypeId"]').value = d.resourceTypeId;

        form.querySelector('[name="authors"]').value = (d.authors || []).map(a => a.authorName).join(", ");
        form.querySelector('[name="tags"]').value = (d.tags || []).map(t => t.name).join(", ");

        const fileList = document.getElementById("currentFiles");
        if (fileList) {
          fileList.innerHTML = (d.files || []).map(f => `
            <div class="border rounded p-1 px-2 mb-1 small bg-light">
              ${f.originalFilename}
            </div>
          `).join("");
        }

        modalInstance.hide();
        modalCreate.show();
      });

      // --- Eliminar ---
      newBtnDelete.addEventListener("click", async () => {
        if (!confirm("¿Eliminar este recurso?")) return;
        await apiFetch(`delete.php?id=${d.idResource}`, { method: "DELETE" });
        alert("Recurso eliminado correctamente.");
        modalInstance.hide();
        await loadResources();
      });

    } catch (err) {
      console.error(err);
      alert("Error al cargar el detalle: " + err.message);
    }
  }

  // ================================
  // Crear o actualizar recurso
  // ================================
  const formCreate = document.getElementById("formCreate");
  if (formCreate) {
    formCreate.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        const f = e.target;
        const payload = {
          idResource: f.resourceId.value || null,
          title: f.title.value.trim(),
          description: f.description.value.trim(),
          module: f.module.value,
          resourceTypeId: parseInt(f.resourceTypeId.value),
          licenseId: f.licenseId.value ? parseInt(f.licenseId.value) : null,
          authors: f.authors.value
            ? f.authors.value.split(",").map(a => ({ authorName: a.trim(), role: "Author" }))
            : [],
          tags: f.tags.value
            ? f.tags.value.split(",").map(t => t.trim())
            : []
        };

        const isEdit = !!f.querySelector('[name="resourceId"]').value;
        const endpoint = isEdit ? "update.php" : "create.php";

        const createRes = await apiFetch(endpoint, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        if (!createRes.ok) throw new Error(createRes.error || "Error al guardar el recurso");
        const resourceId = createRes.data.idResource;

        // Subir README
        const readmeInput = f.querySelector('[name="readmeFile"]');
        if (readmeInput && readmeInput.files.length > 0) {
          const readme = readmeInput.files[0];
          const fd = new FormData();
          fd.append("resourceId", resourceId);
          fd.append("fileKind", "Readme");
          fd.append("file", readme);
          await fetch(`${API_BASE}/uploadFile.php`, {
            method: "POST",
            headers: { "Authorization": `Bearer ${token}` },
            body: fd
          });
        }

        // Subir múltiples archivos
        const filesInput = f.querySelector('[name="files[]"]');
        if (filesInput && filesInput.files.length > 0) {
          const fileList = Array.from(filesInput.files);
          for (const file of fileList) {
            const fd = new FormData();
            fd.append("resourceId", resourceId);
            fd.append("fileKind", "Primary");
            fd.append("file", file);
            await fetch(`${API_BASE}/uploadFile.php`, {
              method: "POST",
              headers: { "Authorization": `Bearer ${token}` },
              body: fd
            });
          }
        }

        alert("Recurso guardado correctamente.");
        const modalCreateInstance = bootstrap.Modal.getInstance(document.getElementById("modalCreate"));
        modalCreateInstance.hide();
        await loadResources();

      } catch (err) {
        console.error(err);
        alert("Error: " + err.message);
      }
    });
  }

  // ================================
  // Inicialización
  // ================================
  if (btnNew) {
    btnNew.addEventListener("click", () => {
      const form = document.getElementById("formCreate");
      form.reset();
      modalCreate.show();
    });
  }
  
  loadResources();
});
