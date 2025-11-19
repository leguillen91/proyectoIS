document.addEventListener("DOMContentLoaded", async () => {

    console.log("pensum.js cargado");

    // ================================================
    // VARIABLES GLOBALES
    // ================================================
    const token = localStorage.getItem("accessToken");

    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const authHeaders = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    let allSubjects = [];
    let allCareers = [];
    let allDepartments = [];
    let allPrerequisites = [];
    let selectedSubjectId = null;
    let careerSelectedId = null;
    let totalPeriods = 0;

    // ================================================
    // ELEMENTOS DEL DOM
    // ================================================
    const careerSelect = document.getElementById("careerSelect");
    const subjectSearch = document.getElementById("subjectSearch");
    const departmentFilter = document.getElementById("subjectDepartmentFilter");
    const periodSelect = document.getElementById("periodSelect");
    const availableSubjectsBox = document.getElementById("availableSubjects");
    const pensumMatrix = document.getElementById("pensumMatrix");

    const btnAddToPeriod = document.getElementById("btnAddToPeriod");
    const btnAddCareer = document.getElementById("btnAddCareer");
    const btnEditCareer = document.getElementById("btnEditCareer");

    // ================================================
    // CARGA INICIAL DE DATOS
    // ================================================
    await loadDepartments();
    await loadSubjects();
    await loadPrerequisites();
    await loadCareers();

    renderAvailableSubjects(allSubjects);


    // ======================================================
    // CARGAR DEPARTAMENTOS
    // ======================================================
    async function loadDepartments() {
        const res = await fetch("/api/registrationModule/subjects/departments.php", {
            headers: authHeaders
        });
        const json = await res.json();

        if (!json.ok) return;

        allDepartments = json.departments;

        departmentFilter.innerHTML = `
            <option value="">Todos</option>
        ` + allDepartments.map(d => `
            <option value="${d.id}">${d.name}</option>
        `).join("");
    }

    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });


    // ======================================================
    // CARGAR TODAS LAS MATERIAS
    // ======================================================
    async function loadSubjects() {
        const res = await fetch("/api/registrationModule/subjects/list.php", { headers: authHeaders });
        const json = await res.json();

        if (!json.ok) return;

        allSubjects = json.subjects;
    }

    // ======================================================
    // CARGAR TODOS LOS PRERREQUISITOS
    // ======================================================
    async function loadPrerequisites() {
        const res = await fetch("/api/registrationModule/subjectPrerequisites/all.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (json.ok) {
            allPrerequisites = json.list;
        }
    }

    // ======================================================
    // CARGAR CARRERAS
    // ======================================================
    async function loadCareers() {
        const res = await fetch("/api/registrationModule/careers/list.php", { headers: authHeaders });
        const json = await res.json();

        if (!json.ok) return;

        allCareers = json.careers;

        careerSelect.innerHTML = `
            <option value="">Seleccione una carrera...</option>
        ` + allCareers.map(c => `
            <option value="${c.id}">${c.name}</option>
        `).join("");
    }

    // ======================================================
    // EVENTO: Cambio de carrera
    // ======================================================
    careerSelect.addEventListener("change", async () => {
        careerSelectedId = parseInt(careerSelect.value);

        if (!careerSelectedId) {
            pensumMatrix.innerHTML = "";
            return;
        }

        const career = allCareers.find(c => c.id == careerSelectedId);
        totalPeriods = career.totalPeriods;

        loadPeriodOptions(totalPeriods);

        await loadCareerPlan();
    });

    // ======================================================
    // CARGAR LISTA DE PERIODOS
    // ======================================================
    function loadPeriodOptions(count) {
        periodSelect.innerHTML = "";
        for (let i = 1; i <= count; i++) {
            periodSelect.innerHTML += `<option value="${i}">Período ${i}</option>`;
        }
    }

    // ======================================================
    // BUSCAR MATERIAS (texto)
    // ======================================================
    subjectSearch.addEventListener("input", () => {
        filterSubjects();
    });

    departmentFilter.addEventListener("change", () => {
        filterSubjects();
    });

    function filterSubjects() {
        const text = subjectSearch.value.toLowerCase();
        const dep = departmentFilter.value;

        let filtered = allSubjects.filter(s =>
            s.code.toLowerCase().includes(text) ||
            s.name.toLowerCase().includes(text)
        );

        if (dep) {
            filtered = filtered.filter(s => s.departmentId == dep);
        }

        renderAvailableSubjects(filtered);
    }

    // ======================================================
    // RENDERIZAR MATERIAS DISPONIBLES (Clickeables)
    // ======================================================
    function renderAvailableSubjects(list) {

        availableSubjectsBox.innerHTML = "";

        if (list.length === 0) {
            availableSubjectsBox.innerHTML = `<p class="text-muted">No hay materias encontradas.</p>`;
            return;
        }

        list.forEach(sub => {
            const div = document.createElement("div");
            div.className = "subject-item selectable-subject";
            div.dataset.id = sub.id;
            div.innerHTML = `<strong>${sub.code}</strong> — ${sub.name} (${sub.uv} UV)`;

            // Evento de selección
            div.addEventListener("click", () => {
                selectedSubjectId = sub.id;

                // reset estilo
                document.querySelectorAll(".selectable-subject").forEach(x => {
                    x.style.borderLeft = "4px solid #003366";
                    x.style.background = "#f3f6ff";
                });

                // marcar seleccionada
                div.style.borderLeft = "6px solid #007bff";
                div.style.background = "#e7f0ff";
            });

            availableSubjectsBox.appendChild(div);
        });
    }

    // ======================================================
    // AGREGAR MATERIA AL PERIODO
    // ======================================================
    btnAddToPeriod.addEventListener("click", async () => {

        if (!careerSelectedId) {
            alert("Seleccione una carrera.");
            return;
        }

        if (!selectedSubjectId) {
            alert("Seleccione una materia. ¡No puedes agregar la misma materia más de una vez al pensum!");
            return;
        }

        const periodNumber = parseInt(periodSelect.value);

        const payload = {
            careerId: careerSelectedId,
            subjectId: selectedSubjectId,
            periodNumber
        };

        const res = await fetch("/api/registrationModule/careerSubjects/add.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        alert(json.message);

        selectedSubjectId = null;

        await loadCareerPlan();
    });

    // ======================================================
    // CARGAR PENSUM DE LA CARRERA
    // ======================================================
    async function loadCareerPlan() {

        const res = await fetch(`/api/registrationModule/careerSubjects/listByCareer.php?careerId=${careerSelectedId}`, {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) return;

        renderPensum(json.plan);
    }

    // ======================================================
    // RENDERIZAR MATRIZ 3xN
    // ======================================================
    function renderPensum(plan) {

        pensumMatrix.innerHTML = "";

        for (let p = 1; p <= totalPeriods; p++) {

            const col = document.createElement("div");
            col.className = "period-box";

            col.innerHTML = `<div class="period-title">Período ${p}</div>`;

            const subs = plan.filter(x => x.periodNumber == p);

            if (subs.length === 0) {
                col.innerHTML += `<p class="text-muted">Sin materias</p>`;
            }

            subs.forEach(s => {
                col.innerHTML += `
                    <div class="subject-item">
                        <strong>${s.code}</strong> — ${s.name} (${s.uv} UV)
                        <span class="subject-remove" onclick="removeSubject(${s.id})">✖</span>
                        <br><small class="text-muted">Req: ${listSubjectReq(s.subjectId)}</small>
                    </div>
                `;
            });

            pensumMatrix.appendChild(col);
        }
    }

    // Mostrar requisitos de la materia
    function listSubjectReq(subjectId) {
        const reqs = allPrerequisites.filter(r => r.subjectId == subjectId);

        if (reqs.length === 0) return "Ninguno";

        return reqs.map(r => r.prereqCode).join(", ");
    }

    // ======================================================
    // REMOVER MATERIA DEL PENSUM
    // ======================================================
    window.removeSubject = async (careerSubjectId) => {

        const ok = confirm("¿Quitar esta materia del período?");
        if (!ok) return;

        const res = await fetch("/api/registrationModule/careerSubjects/remove.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id: careerSubjectId })
        });

        const json = await res.json();
        alert(json.message);

        await loadCareerPlan();
    };

    // ======================================================
    // MODAL NUEVA CARRERA
    // ======================================================
    const modalAddCareer = new bootstrap.Modal("#modalAddCareer");

    btnAddCareer.addEventListener("click", () => {
        document.getElementById("newCareerName").value = "";
        document.getElementById("newCareerDepartment").innerHTML =
            allDepartments.map(d => `<option value="${d.id}">${d.name}</option>`).join("");

        modalAddCareer.show();
    });

    document.getElementById("btnSaveNewCareer").addEventListener("click", async () => {

        const name = document.getElementById("newCareerName").value;
        const departmentId = document.getElementById("newCareerDepartment").value;
        const totalPeriods = document.getElementById("newCareerPeriods").value;

        const payload = { name, departmentId, totalPeriods };

        const res = await fetch("/api/registrationModule/careers/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        alert(json.message);

        modalAddCareer.hide();
        await loadCareers();
    });

    // ======================================================
    // MODAL EDITAR CARRERA
    // ======================================================
    const modalEditCareer = new bootstrap.Modal("#modalEditCareer");

    btnEditCareer.addEventListener("click", () => {

        if (!careerSelectedId) {
            alert("Seleccione una carrera.");
            return;
        }

        const c = allCareers.find(x => x.id == careerSelectedId);

        document.getElementById("editCareerId").value = c.id;
        document.getElementById("editCareerName").value = c.name;
        document.getElementById("editCareerPeriods").value = c.totalPeriods;

        document.getElementById("editCareerDepartment").innerHTML =
            allDepartments.map(d => `
                <option value="${d.id}" ${d.id == c.departmentId ? "selected" : ""}>${d.name}</option>
            `).join("");

        modalEditCareer.show();
    });

    document.getElementById("btnSaveCareerChanges").addEventListener("click", async () => {
        const id = document.getElementById("editCareerId").value;
        const name = document.getElementById("editCareerName").value;
        const departmentId = document.getElementById("editCareerDepartment").value;
        const totalPeriods = document.getElementById("editCareerPeriods").value;

        const payload = { id, name, departmentId, totalPeriods };

        const res = await fetch("/api/registrationModule/careers/update.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        alert(json.message);
        modalEditCareer.hide();

        await loadCareers();
    });

});
