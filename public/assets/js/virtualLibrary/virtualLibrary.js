document.addEventListener("DOMContentLoaded", async () => {
  const API_BASE = "/api/resource";
  const token = localStorage.getItem("accessToken");
  const resourceContainer = document.getElementById("resourceContainer");
  const uploadResourceBtn = document.getElementById("uploadResource");
  const searchInput = document.getElementById("searchInput");
  const btnBack = document.getElementById("btnBack");
  const formRecurso = document.getElementById("formRecurso");

  if (!token) {
    window.location.href = "./../login.html";
    return;
  }

  btnBack.addEventListener("click", () => {
    window.location.href = "../../index.php";
  });

  let resources = [];
  let userCtx = null;

  async function getUserContext() {
    try {
      const res = await fetch("/public/api/auth/me.php", {
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = await res.json();
      if (data.ok) {
        userCtx = data.user;
        toggleUploadButton(userCtx.role);
      }
    } catch (err) {
      console.error("❌ Error al obtener contexto:", err);
    }
  }

  function toggleUploadButton(role) {
    const allowed = ["admin", "teacher", "coordinator"];
    if (allowed.includes(role.toLowerCase())) {
      uploadResourceBtn.classList.remove("d-none");
    }
  }

  // ==========================
  //  Cargar recursos (list + detail)
  // ==========================
  async function loadResources() {
    try {
      const res = await fetch(`${API_BASE}/list.php?module=library`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Error al obtener recursos");

      const basicList = data.data || [];
      resources = [];

      // 🔸 Cargar detalles para cada recurso
      for (const item of basicList) {
        try {
          const detailRes = await fetch(`${API_BASE}/detail.php?id=${item.idResource}`, {
            headers: { Authorization: `Bearer ${token}` },
          });
          const detailData = await detailRes.json();

          if (detailData.ok) {
            const d = detailData.data;
            resources.push({
              id: d.idResource,
              title: d.title || "Sin título",
              description: d.description || "Sin descripción",
              authors: d.authors?.map(a => a.authorName) || ["No especificado"],
              tags: d.tags?.map(t => t.name) || [],
              fileName: d.files?.[0]?.originalFilename || "Sin archivo",
              visibility: d.visibility || "Privado",
              status: d.status || "Desconocido"
            });
          }
        } catch (err) {
          console.warn(`⚠️ Error obteniendo detalle del recurso ${item.idResource}`, err);
        }
      }

      renderResources(resources);
    } catch (err) {
      console.error(" Error al cargar recursos:", err);
      resourceContainer.innerHTML = `<p class="text-center text-muted mt-3">Error al cargar los recursos.</p>`;
    }
  }

  // ==========================
  //  Renderizar recursos
  // ==========================
  function renderResources(data) {
    if (!data.length) {
      resourceContainer.innerHTML = `<p class="text-center text-muted mt-4">No hay recursos disponibles.</p>`;
      return;
    }

    resourceContainer.innerHTML = data.map((r) => {
      const role = userCtx?.role?.toLowerCase() || "";
      const isStudent = role === "student";
      const btnView = `<button class="btn btn-primary w-100" onclick="viewFile.open('/api/resource/preview.php?id=${r.id}', '${r.title}')">
          <i class="bi bi-file-earmark-pdf"></i> Ver PDF
        </button>`;

      const btnAdmin = ["admin", "teacher", "coordinator"].includes(role)
        ? `<div class="d-flex justify-content-end gap-2 mt-2">
            <button class="btn btn-sm btn-outline-primary" onclick="editResource(${r.id})">
              <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteResource(${r.id})">
              <i class="bi bi-trash"></i> Eliminar
            </button>
          </div>`
        : "";

      return `
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title">${r.title}</h5>
              <p class="card-text mb-1"><small>Descripción: ${r.description}</small></p>
              <p class="card-text mb-1"><small>Autor(es): ${r.authors.join(", ")}</small></p>
              <p class="card-text mb-1"><small>Etiquetas: ${r.tags.join(", ") || "Sin etiquetas"}</small></p>
              <p class="card-text mb-1"><small><i class="bi bi-file-earmark-pdf"></i> ${r.fileName}</small></p>
              <p class="card-text mb-2"><small><i class="bi bi-lock"></i> ${r.visibility}</small></p>
              ${btnView}
              ${btnAdmin}
            </div>
          </div>
        </div>`;
    }).join("");
  }

  // =============================================================
  //  Buscador dinámico (por título, autor o etiqueta)
  // =============================================================
  searchInput.addEventListener("input", (e) => {
    const term = e.target.value.trim().toLowerCase();

    if (!term) {
      renderResources(resources);
      return;
    }

    const filtered = resources.filter((r) => {
      const titleMatch = r.title.toLowerCase().includes(term);
      const authorMatch = r.authors?.some(a => a.toLowerCase().includes(term));
      const tagMatch = r.tags?.some(t => t.toLowerCase().includes(term));
      return titleMatch || authorMatch || tagMatch;
    });

    if (filtered.length > 0) {
      renderResources(filtered);
    } else {
      resourceContainer.innerHTML = `
        <div class="text-center mt-5">
          <i class="bi bi-search text-secondary fs-1"></i>
          <p class="mt-3 text-muted">No se encontraron resultados para "<strong>${term}</strong>"</p>
        </div>`;
    }
  });

  // =============================================================
  //  CREAR NUEVO RECURSO
  // =============================================================
  formRecurso.addEventListener("submit", async (e) => {
    e.preventDefault();

    const title = document.getElementById("title").value.trim();
    const authors = document.getElementById("authors").value.split(",").map(a => a.trim()).filter(a => a);
    const tags = document.getElementById("tags").value.split(",").map(t => t.trim()).filter(t => t);
    const file = document.getElementById("file").files[0];

    if (!title || !file) {
      alert("Por favor completa los campos requeridos");
      return;
    }

    try {
      const payload = {
        title,
        description: document.getElementById("description").value.trim() || "",
        module: "library",
        resourceTypeId: 1,
        authors: authors.map((a) => ({ authorName: a })),
        tags,
        createdByPersonId: userCtx?.userId || null,
      };

      const res = await fetch(`${API_BASE}/create.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Error al crear recurso");

      const resourceId = data.data?.idResource || data.idResource;
      const formData = new FormData();
      formData.append("resourceId", resourceId);
      formData.append("fileKind", "Primary");
      formData.append("file", file);

      const uploadRes = await fetch(`${API_BASE}/uploadFile.php`, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
      });

      const uploadData = await uploadRes.json();
      if (!uploadData.ok) throw new Error(uploadData.error || "Error al subir archivo");

      alert("✅ Recurso creado correctamente");
      document.querySelector("#modalRecurso .btn-close").click();
      await loadResources();
    } catch (err) {
      console.error("❌ Error al crear recurso:", err);
      alert(`Error al crear recurso: ${err.message}`);
    }
  });

  // =============================================================
  //  Editar recurso
  // =============================================================
  async function editResource(id) {
    ensureEditModal();
    const resource = resources.find(r => r.id === id);
    if (!resource) return alert("No se encontró el recurso.");

    document.getElementById("editResourceId").value = id;
    document.getElementById("editTitle").value = resource.title || "";
    document.getElementById("editDescription").value = resource.description || "";
    document.getElementById("editTags").value = (resource.tags || []).join(", ");

    editModal.show();

    document.getElementById("btnSaveEdit").onclick = async () => {
      const payload = {
        idResource: id,
        title: document.getElementById("editTitle").value.trim(),
        description: document.getElementById("editDescription").value.trim(),
        tags: document.getElementById("editTags").value.split(",").map(t => t.trim()).filter(Boolean)
      };

      try {
        const res = await fetch(`${API_BASE}/update.php`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || "Error al actualizar recurso");

        alert("✅ Recurso actualizado correctamente.");
        editModal.hide();
        loadResources();

      } catch (err) {
        console.error("❌ Error al actualizar:", err);
        alert("Error al actualizar: " + err.message);
      }
    };
  }

  // =============================================================
  //  Eliminar recurso
  // =============================================================
  async function deleteResource(id) {
    if (!confirm("¿Seguro que deseas eliminar este recurso?")) return;

    try {
      const res = await fetch(`${API_BASE}/delete.php?id=${id}`, {
        method: "DELETE",
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.error || "Error al eliminar recurso");

      alert("🗑️ Recurso eliminado correctamente.");
      loadResources();
    } catch (err) {
      console.error("❌ Error al eliminar:", err);
      alert("Error al eliminar: " + err.message);
    }
  }

// ============================================================
//  Modal completo: Editar todos los datos del recurso
// ============================================================
let editModal = null;

function ensureEditModal() {
  if (document.getElementById("editResourceModal")) {
    editModal = new bootstrap.Modal(document.getElementById("editResourceModal"));
    return;
  }

  const modalHTML = `
  <div class="modal fade" id="editResourceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Recurso</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editResourceForm" enctype="multipart/form-data">
            <input type="hidden" id="editResourceId">

            <div class="mb-3">
              <label class="form-label fw-semibold">Título</label>
              <input type="text" class="form-control" id="editTitle" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Descripción</label>
              <textarea class="form-control" id="editDescription" rows="3"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Autor(es) <small class="text-muted">(separados por coma)</small></label>
              <input type="text" class="form-control" id="editAuthors">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Etiquetas <small class="text-muted">(separadas por coma)</small></label>
              <input type="text" class="form-control" id="editTags">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Archivo PDF (opcional)</label>
              <input type="file" class="form-control" id="editFile" accept="application/pdf">
              <small class="text-muted">Si no seleccionas un archivo, se conservará el actual.</small>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" id="btnSaveEdit"><i class="bi bi-save"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>
  </div>`;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
  editModal = new bootstrap.Modal(document.getElementById("editResourceModal"));
}

// =============================================================
//  Función de edición completa
// =============================================================
async function editResource(id) {
  ensureEditModal();

  const resource = resources.find(r => r.id === id);
  if (!resource) return alert("No se encontró el recurso.");

  // Precargar datos
  document.getElementById("editResourceId").value = id;
  document.getElementById("editTitle").value = resource.title || "";
  document.getElementById("editDescription").value = resource.description || "";
  document.getElementById("editAuthors").value = (resource.authors || []).join(", ");
  document.getElementById("editTags").value = (resource.tags || []).join(", ");

  editModal.show();

  document.getElementById("btnSaveEdit").onclick = async () => {
  const title = document.getElementById("editTitle").value.trim();
  const description = document.getElementById("editDescription").value.trim();
  const authors = document.getElementById("editAuthors").value
    .split(",").map(a => a.trim()).filter(Boolean);
  const tags = document.getElementById("editTags").value
    .split(",").map(t => t.trim()).filter(Boolean);
  const file = document.getElementById("editFile").files[0];

  if (!title) return alert("El título es obligatorio.");

  try {
    // 1️ Actualizar metadatos del recurso
    const payload = {
  idResource: id,
  title,
  description,
  module: "library",
  resourceTypeId: 1,
  licenseId: null, //  agregamos este campo
  authors: authors.map(a => ({ authorName: a })),
  tags,
  createdByPersonId: userCtx?.userId || null
};

    const res = await fetch(`${API_BASE}/update.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.error || "Error al actualizar recurso");

    // 2️ Subir nuevo archivo si aplica
    if (file) {
      const formData = new FormData();
      formData.append("resourceId", id);
      formData.append("fileKind", "Primary");
      formData.append("file", file);

      const uploadRes = await fetch(`${API_BASE}/uploadFile.php`, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
      });

      const uploadData = await uploadRes.json();
      if (!uploadData.ok) throw new Error(uploadData.error || "Error al subir nuevo archivo");
    }

    alert(" Recurso actualizado correctamente.");
    editModal.hide();
    loadResources();

  } catch (err) {
    console.error(" Error al actualizar:", err);
    alert("Error al actualizar: " + err.message);
  }
};

}


  // =============================================================
  // Inicialización
  // =============================================================
  window.editResource = editResource;
  window.deleteResource = deleteResource;
  await getUserContext();
  await loadResources();
});
