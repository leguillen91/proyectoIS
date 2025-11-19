document.addEventListener("DOMContentLoaded", async () => {

    console.log("%csections.js cargado", "color: #2ecc71; font-weight: bold");

    // ============================================================
    // CONFIGURACIÓN INICIAL
    // ============================================================

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const authHeaders = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    const tableBody = document.querySelector("#sectionsTable tbody");

    // Modals
    const modalSeccion = new bootstrap.Modal("#modalSeccion");
    const modalHorario = new bootstrap.Modal("#modalHorario");

    // Alert container
    createAlertContainer();

    // ============================================================
    // DATOS EN MEMORIA
    // ============================================================

    let periods = [];
    let departments = [];
    let subjects = [];
    let teachers = [];
    let classrooms = [];
    let sections = [];

    // ============================================================
    // CARGA INICIAL DE TODO
    // ============================================================

    await loadBaseData();
    await loadSections();
    setupEventListeners();

    // ============================================================
    // FUNCIÓN: MOSTRAR ALERTAS BOOTSTRAP
    // ============================================================
    function showAlert(message, type = "info", timeout = 3000) {
        const container = document.getElementById("alertContainer");
        const alert = document.createElement("div");

        alert.className = `alert alert-${type} fade show shadow`;
        alert.role = "alert";
        alert.innerText = message;

        container.appendChild(alert);

        setTimeout(() => {
            alert.classList.remove("show");
            setTimeout(() => alert.remove(), 200);
        }, timeout);
    }

    function createAlertContainer() {
        const div = document.createElement("div");
        div.id = "alertContainer";
        div.style.position = "fixed";
        div.style.top = "20px";
        div.style.right = "20px";
        div.style.zIndex = "99999";
        document.body.appendChild(div);
    }

    // ============================================================
    // CARGAR DATOS BASE
    // ============================================================

    async function loadBaseData() {

        // Periodos
        let rp = await fetch("/api/registrationModule/periods/list.php", { headers: authHeaders });
        let jp = await rp.json();
        periods = jp.periods || [];

        // Departamentos
        let rd = await fetch("/api/registrationModule/subjects/departments.php", { headers: authHeaders });
        let jd = await rd.json();
        departments = jd.departments || [];

        // Materias
        let rs = await fetch("/api/registrationModule/subjects/list.php", { headers: authHeaders });
        let js = await rs.json();
        subjects = js.subjects || [];

        // Docentes
        let rt = await fetch("/api/registrationModule/teachers/list.php", { headers: authHeaders });
        let jt = await rt.json();
        teachers = jt.teachers || [];

        // Aulas
        let rc = await fetch("/api/registrationModule/classrooms/list.php", { headers: authHeaders });
        let jc = await rc.json();
        classrooms = jc.classrooms || [];

        fillFilterSelects();
        fillModalSelects();
    }

    // ============================================================
    // CARGAR SECCIONES
    // ============================================================

    async function loadSections() {
        const res = await fetch("/api/registrationModule/sections/list.php", { headers: authHeaders });
        const json = await res.json();

        sections = json.sections || [];

        renderSections();
    }

    // ============================================================
    // RENDERIZAR TABLA
    // ============================================================

    function renderSections() {
        tableBody.innerHTML = "";

        const filterPeriod = document.getElementById("filterPeriod").value;
        const filterDep = document.getElementById("filterDepartment").value;
        const textSearch = document.getElementById("filterSearch").value.toLowerCase();

        const filtered = sections.filter(s => {

            if (filterPeriod && s.periodId != filterPeriod) return false;
            if (filterDep && s.departmentId != filterDep) return false;

            if (textSearch) {
                const hayCoincidencia =
                    s.subjectName.toLowerCase().includes(textSearch) ||
                    s.subjectCode.toLowerCase().includes(textSearch) ||
                    s.sectionCode.toLowerCase().includes(textSearch);

                if (!hayCoincidencia) return false;
            }

            return true;
        });

        filtered.forEach(sec => {
            const tr = document.createElement("tr");

            tr.innerHTML = `
                <td>${sec.sectionCode}</td>
                <td>${sec.subjectCode} — ${sec.subjectName}</td>
                <td>${sec.teacherName || "—"}</td>
                <td>${sec.roomCode || "—"}</td>
                <td>${sec.cupo}</td>
                <td>${sec.periodCode}</td>
                <td>

                    <button class="btn btn-warning btn-sm me-1" onclick="editSection(${sec.id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <button class="btn btn-secondary btn-sm me-1" onclick="manageSchedules(${sec.id})">
                        <i class="bi bi-clock-history"></i>
                    </button>

                    <button class="btn btn-danger btn-sm" onclick="deleteSection(${sec.id})">
                        <i class="bi bi-trash"></i>
                    </button>

                </td>
            `;

            tableBody.appendChild(tr);
        });
    }
        // ============================================================
    // LLENAR SELECTS DEL MODAL
    // ============================================================

    function fillModalSelects() {

        // ===== Periodos =====
        const sp = document.getElementById("secPeriod");
        sp.innerHTML = periods
            .map(p => `<option value="${p.id}">${p.code}</option>`)
            .join("");

        // ===== Departamentos =====
        const sd = document.getElementById("secDepartment");
        sd.innerHTML = `<option value="">Seleccione</option>` +
            departments.map(d => `<option value="${d.id}">${d.name}</option>`).join("");

        // ===== Docentes =====
        const st = document.getElementById("secTeacher");
        st.innerHTML = `<option value="">Seleccione</option>` +
            teachers.map(t => `<option value="${t.id}">${t.fullName}</option>`).join("");

        // ===== Aulas =====
        const sc = document.getElementById("secClassroom");
        if (sc) {
            sc.innerHTML =
                `<option value="">Seleccione aula</option>` +
                classrooms.map(c => {
                    return `<option value="${c.id}">
                                ${c.roomCode}  (Cap: ${c.capacity})
                            </option>`;
                }).join("");
        }
    }

    // ============================================================
    // LLENAR FILTROS SUPERIORES
    // ============================================================

    function fillFilterSelects() {

        document.getElementById("filterPeriod").innerHTML =
            `<option value="">Todos</option>` +
            periods.map(p => `<option value="${p.id}">${p.code}</option>`).join("");

        document.getElementById("filterDepartment").innerHTML =
            `<option value="">Todos</option>` +
            departments.map(d => `<option value="${d.id}">${d.name}</option>`).join("");
    }

    // ============================================================
    // EVENTOS DEL FRONT-END
    // ============================================================

    function setupEventListeners() {

        // Abrir modal de Nueva Sección
        document.getElementById("btnNuevaSeccion").addEventListener("click", () => {
            clearSectionForm();
            document.getElementById("modalSeccionTitle").innerText = "Nueva Sección";
            modalSeccion.show();
        });

        // Cuando cambia el departamento, cargar materias filtradas
        document.getElementById("secDepartment").addEventListener("change", e => {
            const depId = e.target.value;
            const ss = document.getElementById("secSubject");

            if (!depId) {
                ss.innerHTML = "";
                return;
            }

            ss.innerHTML =
                subjects
                    .filter(s => s.departmentId == depId)
                    .map(s => `<option value="${s.id}">${s.code} — ${s.name}</option>`)
                    .join("");
        });

        // Filtros superiores
        document.getElementById("filterPeriod").addEventListener("change", renderSections);
        document.getElementById("filterDepartment").addEventListener("change", renderSections);
        document.getElementById("filterSearch").addEventListener("input", renderSections);

        // Botón Guardar Sección
        document.getElementById("btnGuardarSeccion").addEventListener("click", saveSection);
    }

    // ============================================================
    // LIMPIAR FORMULARIO DEL MODAL
    // ============================================================

    function clearSectionForm() {
        document.getElementById("secPeriod").value = "";
        document.getElementById("secDepartment").value = "";
        document.getElementById("secSubject").innerHTML = "";
        document.getElementById("secTeacher").value = "";
        document.getElementById("secClassroom").value = "";
        document.getElementById("secCapacity").value = "";
        document.getElementById("secMode").value = "presencial";
        document.getElementById("secObs").value = "";
        document.getElementById("secId").value = "";
    }

    // ============================================================
    // CREAR / ACTUALIZAR SECCIÓN (AUTOMÁTICO)
    // ============================================================

    async function saveSection() {

        const id = document.getElementById("secId").value;

        const payload = {
            id: id || undefined,
            periodId: document.getElementById("secPeriod").value,
            subjectId: document.getElementById("secSubject").value,
            teacherId: document.getElementById("secTeacher").value,
            classroomId: document.getElementById("secClassroom").value,
            cupo: document.getElementById("secCapacity").value,
            mode: document.getElementById("secMode").value,
            obs: document.getElementById("secObs").value
        };

        // Validaciones básicas
        if (!payload.periodId || !payload.subjectId || !payload.teacherId || !payload.classroomId) {
            showAlert("Todos los campos obligatorios deben completarse", "danger");
            return;
        }

        const API = id
            ? "/api/registrationModule/sections/update.php"
            : "/api/registrationModule/sections/create.php";

        const res = await fetch(API, {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.error || "Error guardando sección", "danger");
            return;
        }

        showAlert(json.message, "success");
        modalSeccion.hide();
        document.getElementById("filterPeriod").value = "";
        document.getElementById("filterDepartment").value = "";
        document.getElementById("filterSearch").value = "";
        await loadSections();
    }

    // ============================================================
    // EDITAR SECCIÓN
    // ============================================================

    window.editSection = async (id) => {

        clearSectionForm();
        document.getElementById("modalSeccionTitle").innerText = "Editar Sección";

        const sec = sections.find(s => s.id == id);
        if (!sec) {
            showAlert("No se encontró la sección", "danger");
            return;
        }

        document.getElementById("secId").value = sec.id;
        document.getElementById("secPeriod").value = sec.periodId;
        document.getElementById("secDepartment").value = sec.departmentId;

        // Cargar materias según departamento
        document.getElementById("secSubject").innerHTML =
            subjects
                .filter(s => s.departmentId == sec.departmentId)
                .map(s => `<option value="${s.id}">${s.code} — ${s.name}</option>`)
                .join("");

        document.getElementById("secSubject").value = sec.subjectId;
        document.getElementById("secTeacher").value = sec.teacherId;
        document.getElementById("secClassroom").value = sec.classroomId;
        document.getElementById("secCapacity").value = sec.cupo;
        document.getElementById("secMode").value = sec.mode;
        document.getElementById("secObs").value = sec.obs || "";

        modalSeccion.show();
    };

    // ============================================================
    // ELIMINAR SECCIÓN
    // ============================================================

    window.deleteSection = async (id) => {

        if (!confirm("¿Eliminar esta sección?")) return;

        const res = await fetch("/api/registrationModule/sections/delete.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id })
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.error || "Error eliminando sección", "danger");
            return;
        }

        showAlert(json.message, "success");
        loadSections();
    };

        // ============================================================
    //  HORARIOS — GESTIONAR HORARIOS
    // ============================================================

    window.manageSchedules = async (sectionId) => {

        document.getElementById("scheduleSectionId").value = sectionId;

        await loadSchedules(sectionId);

        modalHorario.show();
    };

    // ============================================================
    // CARGAR HORARIOS DE UNA SECCIÓN
    // ============================================================

    async function loadSchedules(sectionId) {

        const res = await fetch(`/api/registrationModule/sectionSchedule/list.php?sectionId=${sectionId}`, {
            headers: authHeaders
        });

        const json = await res.json();

        const ul = document.getElementById("scheduleList");
        ul.innerHTML = "";

        if (!json.ok) {
            showAlert("Error cargando horarios", "danger");
            return;
        }

        json.schedules.forEach(s => {
            const li = document.createElement("li");
            li.classList.add("list-group-item");

            li.innerHTML = `
                <b>${s.startTime} - ${s.endTime}</b> <span class="text-primary">(${s.day})</span>
                <button class="btn btn-sm btn-danger float-end"
                    onclick="deleteSchedule(${s.id}, ${sectionId})">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;

            ul.appendChild(li);
        });
    }

    // ============================================================
    // AGREGAR HORARIO
    // ============================================================

    document.getElementById("btnAgregarHorario").addEventListener("click", async () => {

        const sectionId = document.getElementById("scheduleSectionId").value;
        const start = document.getElementById("schStart").value;
        const end = document.getElementById("schEnd").value;
        const days = document.getElementById("schDays").value;

        if (!start || !end || !days) {
            showAlert("Complete todos los campos para agregar horario", "danger");
            return;
        }

        if (start >= end) {
            showAlert("La hora de inicio debe ser menor que la hora final", "danger");
            return;
        }

        const payload = {
            sectionId: sectionId,
            day: days,
            startTime: start,
            endTime: end
        };

        const res = await fetch("/api/registrationModule/sectionSchedule/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.error || "Error agregando horario", "danger");
            return;
        }

        showAlert("Horario agregado", "success");

        await loadSchedules(sectionId);
    });

    // ============================================================
    // ELIMINAR HORARIO
    // ============================================================

    window.deleteSchedule = async (scheduleId, sectionId) => {

        if (!confirm("¿Eliminar este horario?")) return;

        const res = await fetch("/api/registrationModule/sectionSchedule/delete.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id: scheduleId })
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.error || "Error eliminando horario", "danger");
            return;
        }

        showAlert("Horario eliminado", "success");

        await loadSchedules(sectionId);
    };

});
