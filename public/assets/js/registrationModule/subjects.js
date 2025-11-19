document.addEventListener("DOMContentLoaded", async () => {

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const headers = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    const tbody             = document.getElementById("subjectsTable");
    const searchInput       = document.getElementById("searchInput");
    const filterDepartment  = document.getElementById("filterDepartment");
    const filterMode        = document.getElementById("filterMode");

    const smartSearch       = document.getElementById("smartSearch");
    const smartDepartment   = document.getElementById("smartDepartment");
    const prMain            = document.getElementById("prereqMainSubject");

    const prAvailList       = document.getElementById("prAvailableList");
    const prCurrentList     = document.getElementById("prCurrentList");

    const modalCreateEl = document.getElementById("modalCreateSubject");
    const modalEditEl   = document.getElementById("modalEditSubject");

    const modalCreate = new bootstrap.Modal(modalCreateEl);
    const modalEdit   = new bootstrap.Modal(modalEditEl);

    let allSubjects = [];
    let filteredSubjects = [];
    let prFiltered = [];

    let prMainChoices = null;

    // --------------------------------------------------------------
    // Bootstrap alerts
    // --------------------------------------------------------------
    function showAlert(message, type = "success") {
        const wrapper = document.createElement("div");
        wrapper.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed"
                 style="top:20px; right:20px; z-index:2000;"
                 role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.appendChild(wrapper);
        setTimeout(() => wrapper.remove(), 3500);
    }

    // --------------------------------------------------------------
    // Load Departments
    // --------------------------------------------------------------
    async function loadDepartments() {
        const res = await fetch("/api/registrationModule/subjects/departments.php", { headers });
        const data = await res.json();
        if (!data.ok) return;

        const html = `
            <option value="">Seleccione</option>
            ${data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join("")}
        `;

        document.getElementById("createDepartment").innerHTML = html;
        document.getElementById("editDepartment").innerHTML   = html;

        filterDepartment.innerHTML = `
            <option value="">Todos</option>
            ${data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join("")}
        `;

        smartDepartment.innerHTML = `
            <option value="">Todos los departamentos</option>
            ${data.departments.map(d => `<option value="${d.name}">${d.name}</option>`).join("")}
        `;
    }

    await loadDepartments();

    // --------------------------------------------------------------
    // Load Subjects
    // --------------------------------------------------------------
    async function loadSubjects() {
        const res = await fetch("/api/registrationModule/subjects/list.php", { headers });
        const data = await res.json();

        if (!data.ok) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Error al cargar</td></tr>`;
            return;
        }

        allSubjects = data.subjects.map(s => ({
            id: s.id,
            code: s.code,
            subjectName: s.name,
            uv: s.uv,
            departmentId: s.departmentId,
            departmentName: s.departmentName || ""
        }));

        applyFilters();
    }

    // --------------------------------------------------------------
    // Table Filters
    // --------------------------------------------------------------
    function applyFilters() {
        const text = searchInput.value.toLowerCase();
        const dep  = filterDepartment.value;
        const mode = filterMode.value;

        filteredSubjects = allSubjects.filter(s => {

            if (dep && s.departmentId != dep) return false;

            if (text.length > 0) {
                const match =
                    s.code.toLowerCase().includes(text) ||
                    s.subjectName.toLowerCase().includes(text);

                if (mode === "dept") {
                    if (!dep) return false;
                    return match;
                }

                return match;
            }

            return true;
        });

        renderSubjectsTable();
    }

    searchInput.addEventListener("input", applyFilters);
    filterDepartment.addEventListener("change", applyFilters);
    filterMode.addEventListener("change", applyFilters);

    // --------------------------------------------------------------
    // Render Subjects Table
    // --------------------------------------------------------------
    function renderSubjectsTable() {

        tbody.innerHTML = "";

        if (filteredSubjects.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Sin coincidencias</td></tr>`;
            return;
        }

        filteredSubjects.forEach((s, i) => {
            tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.code}</td>
                    <td>${s.subjectName}</td>
                    <td>${s.uv}</td>
                    <td>${s.departmentName}</td>

                    <td>
                        <button class="btn btn-warning btn-sm me-1"
                            onclick="editSubject(${s.id}, '${s.code}', '${s.subjectName}', ${s.uv}, ${s.departmentId})">
                            Editar
                        </button>

                        <button class="btn btn-danger btn-sm"
                            onclick="deleteSubject(${s.id})">
                            Eliminar
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    // --------------------------------------------------------------
    // Create Subject
    // --------------------------------------------------------------
    document.getElementById("btnNewSubject").addEventListener("click", () => {
        document.getElementById("createCode").value = "";
        document.getElementById("createName").value = "";
        document.getElementById("createUv").value   = "";
        document.getElementById("createDepartment").value = "";
        modalCreate.show();
    });

    document.getElementById("btnSaveCreate").addEventListener("click", async () => {

        const payload = {
            code: document.getElementById("createCode").value.trim(),
            name: document.getElementById("createName").value.trim(),
            uv: document.getElementById("createUv").value,
            departmentId: document.getElementById("createDepartment").value
        };

        if (!payload.code || !payload.name || !payload.uv || !payload.departmentId) {
            showAlert("Complete todos los campos.", "warning");
            return;
        }

        const res = await fetch("/api/registrationModule/subjects/create.php", {
            method: "POST",
            headers,
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!data.ok) {
            showAlert(data.error || "Error al crear", "danger");
            return;
        }

        showAlert("Materia creada correctamente");
        modalCreate.hide();
        loadSubjects();
    });

    // --------------------------------------------------------------
    // Edit Subject
    // --------------------------------------------------------------
    window.editSubject = (id, code, name, uv, departmentId) => {
        document.getElementById("editId").value = id;
        document.getElementById("editCode").value = code;
        document.getElementById("editName").value = name;
        document.getElementById("editUv").value   = uv;
        document.getElementById("editDepartment").value = departmentId;
        modalEdit.show();
    };

    document.getElementById("btnSaveEdit").addEventListener("click", async () => {

        const payload = {
            id: document.getElementById("editId").value,
            code: document.getElementById("editCode").value.trim(),
            name: document.getElementById("editName").value.trim(),
            uv: document.getElementById("editUv").value,
            departmentId: document.getElementById("editDepartment").value
        };

        if (!payload.code || !payload.name || !payload.uv || !payload.departmentId) {
            showAlert("Complete todos los campos.", "warning");
            return;
        }

        const res = await fetch("/api/registrationModule/subjects/update.php", {
            method: "PUT",
            headers,
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

    // --------------------------------------------------------------
    // Delete Subject
    // --------------------------------------------------------------
    window.deleteSubject = async (id) => {

        if (!confirm("¿Eliminar esta materia?")) return;

        const res = await fetch("/api/registrationModule/subjects/delete.php", {
            method: "DELETE",
            headers,
            body: JSON.stringify({ id })
        });

        const data = await res.json();

        if (!data.ok) {
            showAlert(data.error || "Error eliminando", "danger");
            return;
        }

        showAlert("Materia eliminada");
        loadSubjects();
    };

    // --------------------------------------------------------------
    // TAB Change → Initialize Prereq UI
    // --------------------------------------------------------------
    document.querySelectorAll("#subjectTabs .nav-link").forEach(tab => {

        tab.addEventListener("click", () => {

            document.querySelectorAll("#subjectTabs .nav-link")
                .forEach(x => x.classList.remove("active"));
            tab.classList.add("active");

            const target = tab.getAttribute("data-tab");

            document.getElementById("tab-subjects").style.display = "none";
            document.getElementById("tab-prerequisites").style.display = "none";

            document.getElementById(target).style.display = "block";

            if (target === "tab-prerequisites") {
                initChoices();
                updateSmartList();
                renderPrereqCandidates();
            }
        });
    });

    // --------------------------------------------------------------
    // Initialize Choices.js for main selector
    // --------------------------------------------------------------
    function initChoices() {

        if (prMainChoices) prMainChoices.destroy();

        prMainChoices = new Choices(prMain, {
            placeholderValue: "Seleccione una materia",
            searchPlaceholderValue: "Buscar...",
            itemSelectText: "",
            allowHTML: true,
            shouldSort: false,
            searchFields: ["label", "value"],
            fuseOptions: {
                keys: ["label", "customProperties.code", "customProperties.name", "customProperties.department"],
                threshold: 0.3
            }
        });
    }

    // --------------------------------------------------------------
    // Update smart list of subjects for Choices
    // --------------------------------------------------------------
    function updateSmartList() {

        const text = smartSearch.value.toLowerCase();
        const dep  = smartDepartment.value.toLowerCase();

        prFiltered = allSubjects.filter(s => {
            if (s.id == prMain.value) return false;
            if (dep && s.departmentName.toLowerCase() !== dep) return false;

            if (text.length > 0) {
                return (
                    s.code.toLowerCase().includes(text) ||
                    s.subjectName.toLowerCase().includes(text) ||
                    s.departmentName.toLowerCase().includes(text)
                );
            }

            return true;
        });

        prMainChoices.clearChoices();

        prMainChoices.setChoices(
            prFiltered.map(s => ({
                value: s.id,
                label: `${s.code} — ${s.subjectName}`,
                customProperties: {
                    code: s.code.toLowerCase(),
                    name: s.subjectName.toLowerCase(),
                    department: s.departmentName.toLowerCase()
                }
            })),
            "value",
            "label",
            true
        );
    }

    // Sincronizar buscadores
    smartSearch.addEventListener("input", () => {
        updateSmartList();
        renderPrereqCandidates();
    });

    smartDepartment.addEventListener("change", () => {

        // Limpiar selección actual
        prMainChoices.clearStore();
        prMainChoices.clearChoices();
        prMainChoices.clearInput();
        prMain.value = "";

        // Volver a cargar lista inteligente
        updateSmartList();

        // Actualizar lista inferior
        renderPrereqCandidates();

        // Limpiar la lista de prerrequisitos actuales
        prCurrentList.innerHTML = `<p class="text-muted">Seleccione una materia</p>`;
    });


    // --------------------------------------------------------------
    // On subject select → load prereqs
    // --------------------------------------------------------------
    prMain.addEventListener("change", async () => {

        const subjectId = prMain.value;

        if (!subjectId) {
            prCurrentList.innerHTML = `<p class="text-muted">Seleccione una materia</p>`;
            prAvailList.innerHTML = "";
            return;
        }

        const res = await fetch(
            `/api/registrationModule/subjectPrerequisites/listBySubject.php?subjectId=${subjectId}`,
            { headers }
        );

        const data = await res.json();

        if (!data.ok) {
            prCurrentList.innerHTML = `<p class="text-danger">Error al cargar prerrequisitos</p>`;
            return;
        }

        renderPrereqCandidates();
        renderCurrentPrereqs(data.prerequisites);

        data.prerequisites.forEach(p => {
            const chk = document.getElementById(`chk_pr_${p.prereqId}`);
            if (chk) chk.checked = true;
        });
    });

    // --------------------------------------------------------------
    // List of checkboxes
    // --------------------------------------------------------------
    function renderPrereqCandidates() {

    const subjectId = prMain.value;

    // Obtener IDs ya existentes como prerequisito
    const existingPrereqs = Array.from(
        document.querySelectorAll("#prCurrentList button")
    ).map(btn => parseInt(btn.getAttribute("onclick").match(/\d+/g)[1]));

    // Filtrar
    const candidates = prFiltered.filter(s => 
        s.id != subjectId &&             // No mostrar la misma materia principal
        !existingPrereqs.includes(s.id)  // No mostrar los ya agregados
    );

    if (candidates.length === 0) {
        prAvailList.innerHTML = `<p class="text-muted">Sin materias disponibles</p>`;
        return;
    }

    prAvailList.innerHTML = candidates.map(s => `
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input"
                   id="chk_pr_${s.id}" value="${s.id}">
            <label class="form-check-label" for="chk_pr_${s.id}">
                <strong>${s.code}</strong> — ${s.subjectName}
            </label>
        </div>
    `).join("");
    }


    // --------------------------------------------------------------
    // Current prereqs list
    // --------------------------------------------------------------
    function renderCurrentPrereqs(list) {

    // eliminar duplicados por prereqId
    const unique = [];
    const seen = new Set();

    list.forEach(item => {
        if (!seen.has(item.prereqId)) {
            unique.push(item);
            seen.add(item.prereqId);
        }
    });

    if (unique.length === 0) {
        prCurrentList.innerHTML = `<p class="text-muted">Sin prerrequisitos</p>`;
        return;
    }

    prCurrentList.innerHTML = unique.map(p => {
        const s = allSubjects.find(x => x.id == p.prereqId);
        if (!s) return "";
        return `
            <div class="d-flex justify-content-between align-items-center border p-2 mb-2 rounded">
                <span><strong>${s.code}</strong> — ${s.subjectName}</span>
                <button class="btn btn-danger btn-sm"
                        onclick="removePrereq(${p.subjectId}, ${p.prereqId})">
                    Quitar
                </button>
            </div>
        `;
    }).join("");
    }


    // --------------------------------------------------------------
    // Remove single prereq
    // --------------------------------------------------------------
    window.removePrereq = async (subjectId, prereqId) => {

        const res = await fetch("/api/registrationModule/subjectPrerequisites/remove.php", {
            method: "DELETE",
            headers,
            body: JSON.stringify({ subjectId, prereqId })
        });

        const data = await res.json();

        if (!data.ok) {
            showAlert(data.error || "Error eliminando prerequisito", "danger");
            return;
        }

        showAlert("Prerrequisito eliminado");
        prMain.dispatchEvent(new Event("change"));
    };

    // --------------------------------------------------------------
    // Save all prereqs
    // --------------------------------------------------------------
    window.savePrerequisites = async () => {

        const subjectId = prMain.value;

        if (!subjectId) {
            showAlert("Seleccione una materia.", "warning");
            return;
        }

        const selected = Array.from(
            document.querySelectorAll('#prAvailableList input[type=checkbox]:checked')
        ).map(x => parseInt(x.value));

        const res = await fetch("/api/registrationModule/subjectPrerequisites/add.php", {
            method: "POST",
            headers,
            body: JSON.stringify({
                subjectId,
                selectedPrereqs: selected
            })
        });

        let data;
        try {
            data = await res.json();
        } catch {
            showAlert("Error inesperado del servidor", "danger");
            return;
        }

        if (!data.ok) {
            showAlert(data.error || "Error al guardar prerrequisitos", "danger");
            return;
        }

        if (data.added?.length > 0) {
            showAlert(`Se agregaron ${data.added.length} prerrequisitos.`, "success");
        }

        if (data.duplicates?.length > 0) {
            showAlert(`Se omitieron ${data.duplicates.length} porque ya existían.`, "warning");
        }


        showAlert("Prerrequisitos actualizados correctamente");
        prMain.dispatchEvent(new Event("change"));
    };

    // --------------------------------------------------------------
    // Start
    // --------------------------------------------------------------
    await loadSubjects();

});
