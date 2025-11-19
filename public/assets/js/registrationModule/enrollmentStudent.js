document.addEventListener("DOMContentLoaded", async () => {

    console.log("enrollmentStudent.js cargado");

    // ========================================================
    // CONFIGURACIÓN INICIAL
    // ========================================================
    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const authHeaders = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    let user = null;
    let periodId = null;
    let uvMax = 0;
    let enrolled = [];
    let oferta = [];
    let schedule = [];

    // ========================================================
    // 1. OBTENER DATOS DEL ESTUDIANTE (auth/me.php)
    // ========================================================
    async function loadUser() {
        const res = await fetch("/api/auth/me.php", { headers: authHeaders });
        const json = await res.json();

        if (!json.ok) {
            alert("Error cargando sesión.");
            return;
        }

        user = json.user;
        document.getElementById("studentName").innerText = user.fullName;
        document.getElementById("studentAccount").innerText = user.enrollmentCode || "";
        document.getElementById("studentIndex").innerText = user.index || "0";

        // UV máximas según reglamento UNAH
        uvMax = (user.index >= 80) ? 16 : 12;
        document.getElementById("uvMax").innerText = uvMax;
    }

    // ========================================================
    // 2. CARGAR PERÍODO ACTIVO
    // ========================================================
    async function loadActivePeriod() {
        const res = await fetch("/api/registrationModule/periods/list.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) {
            alert("Error al cargar periodos.");
            return;
        }

        const abierto = json.periods.find(p => p.status === "abierto");

        if (!abierto) {
            alert("No hay período activo.");
            return;
        }

        periodId = abierto.id;
        document.getElementById("periodLabel").innerText = abierto.code;
    }

    // ========================================================
    // 3. CARGAR OFERTA ACADÉMICA
    // ========================================================
    async function loadOferta() {
        const res = await fetch("/api/registrationModule/enrollment/available.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert("No puedes ver la oferta todavía: " + json.error, "danger");
            return;
        }

        oferta = json.available;
        renderOferta();
    }

    // ========================================================
    // 4. CARGAR ASIGNATURAS INSCRITAS
    // ========================================================
    async function loadInscritas() {
        const res = await fetch("/api/registrationModule/enrollment/list.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) {
            alert("Error cargando inscritas.");
            return;
        }

        enrolled = json.enrolled;

        let totalUV = enrolled.reduce((a, c) => a + parseInt(c.uv), 0);
        document.getElementById("uvCurrent").innerText = totalUV;

        renderInscritas();
    }

    // ========================================================
    // 5. CARGAR HORARIO SEMANAL
    // ========================================================
    async function loadSchedule() {
        const res = await fetch("/api/registrationModule/enrollment/schedule.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) return;

        schedule = json.schedule;

        renderSchedule();
    }

    // ========================================================
    // RENDER — OFERTA ACADÉMICA
    // ========================================================
    function renderOferta() {
        const tbody = document.querySelector("#tableOferta tbody");
        tbody.innerHTML = "";

        oferta.forEach(sec => {

            // Mostrar requisitos como badges visuales
            let reqBadge = `<span class="badge bg-secondary">N/A</span>`;

            let tr = `
                <tr>
                    <td>${sec.subjectCode}</td>
                    <td>${sec.subjectName}</td>
                    <td>${sec.uv}</td>
                    <td>${sec.sectionCode}</td>
                    <td>${sec.teacherName || "—"}</td>
                    <td>${sec.cupo}</td>
                    <td>${reqBadge}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="enroll(${sec.sectionId})">
                            Inscribir
                        </button>
                    </td>
                </tr>
            `;

            tbody.insertAdjacentHTML("beforeend", tr);
        });
    }

    // ========================================================
    // RENDER — ASIGNATURAS INSCRITAS
    // ========================================================
    function renderInscritas() {
        const tbody = document.getElementById("tableInscritas");
        tbody.innerHTML = "";

        enrolled.forEach(sec => {
            const tr = `
                <tr>
                    <td>${sec.subjectCode}</td>
                    <td>${sec.subjectName}</td>
                    <td>${sec.sectionCode}</td>
                    <td>${sec.teacherName || "—"}</td>
                    <td>${sec.uv}</td>
                    <td>Ver en Horario</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="removeSection(${sec.sectionId})">
                            Retirar
                        </button>
                    </td>
                </tr>
            `;

            tbody.insertAdjacentHTML("beforeend", tr);
        });
    }

    // ========================================================
    // RENDER — HORARIO SEMANAL
    // ========================================================
    function renderSchedule() {
        const tbody = document.getElementById("scheduleBody");
        tbody.innerHTML = "";

        // construir horas base
        const hours = [];
        for (let h = 6; h <= 20; h++) {
            hours.push(`${String(h).padStart(2, "0")}:00`);
        }

        hours.forEach(hour => {
            let row = `<tr><th>${hour}</th>`;

            const days = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];

            days.forEach(day => {
                const cell = schedule.find(s =>
                    s.day === day &&
                    hour >= s.startTime &&
                    hour < s.endTime
                );

                if (cell) {
                    row += `<td><div class="schedule-cell">${cell.subjectCode}<br>${cell.sectionCode}</div></td>`;
                } else {
                    row += `<td></td>`;
                }
            });

            row += `</tr>`;

            tbody.insertAdjacentHTML("beforeend", row);
        });
    }

    // ========================================================
    // INSCRIBIR ASIGNATURA
    // ========================================================
    window.enroll = async function (sectionId) {

        const res = await fetch("/api/registrationModule/enrollment/add.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify({ sectionId })
        });

        const json = await res.json();

        if (!json.ok) {
            alert(json.error);
            return;
        }

        alert(json.message);
        await loadInscritas();
        await loadSchedule();
    };

    // ========================================================
    // RETIRAR ASIGNATURA
    // ========================================================
    window.removeSection = async function (sectionId) {

        if (!confirm("¿Seguro que quieres retirar esta asignatura?")) return;

        const res = await fetch("/api/registrationModule/enrollment/remove.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ sectionId })
        });

        const json = await res.json();

        if (!json.ok) {
            alert(json.error);
            return;
        }

        alert(json.message);
        await loadInscritas();
        await loadSchedule();
    };

    // ========================================================
    // FORMA 03 — PREVIEW Y PDF
    // ========================================================
    document.getElementById("btnPDF").addEventListener("click", async () => {
        const res = await fetch("/api/registrationModule/enrollment/forma03.php", {
            headers: authHeaders
        });

        const json = await res.json();

        if (!json.ok) {
            alert("Error generando Forma 03");
            return;
        }

        renderForma03(json.forma03);
    });

    function renderForma03(data) {
        const div = document.getElementById("forma03Content");
        div.innerHTML = "";

        data.forEach(row => {
            div.innerHTML += `
                <div class="border p-2 mb-2">
                    <b>${row.subjectCode} - ${row.subjectName}</b><br>
                    Sección: ${row.sectionCode}<br>
                    Día: ${row.day}<br>
                    ${row.startTime} - ${row.endTime}<br>
                    Aula: ${row.building} ${row.room}<br>
                    UV: ${row.uv}
                </div>
            `;
        });

        alert("Forma 03 generada (solo visual). Para PDF se genera después.");
    }

    // ========================================================
    // BUSCADOR
    // ========================================================
    document.getElementById("searchSubject").addEventListener("keyup", () => {
        const q = document.getElementById("searchSubject").value.toLowerCase();

        const rows = document.querySelectorAll("#tableOferta tbody tr");

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();

            row.style.display = text.includes(q) ? "" : "none";
        });
    });

    // ========================================================
    // RECARGAR MODULO
    // ========================================================
    document.getElementById("btnReload").addEventListener("click", async () => {
        await loadOferta();
        await loadInscritas();
        await loadSchedule();
    });

    // ========================================================
    // INICIALIZACIÓN TOTAL
    // ========================================================
    await loadUser();
    await loadActivePeriod();
    await loadOferta();
    await loadInscritas();
    await loadSchedule();

});
