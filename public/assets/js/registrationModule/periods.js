document.addEventListener("DOMContentLoaded", () => {

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const headers = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    const container = document.getElementById("periodsContainer");
    const searchInput = document.getElementById("searchInput");

    const modalCreate = new bootstrap.Modal(document.getElementById("modalCreatePeriod"));
    const modalEdit   = new bootstrap.Modal(document.getElementById("modalEditPeriod"));
    const modalStatus = new bootstrap.Modal(document.getElementById("modalStatusPeriod"));

    let allPeriods = [];


    // ============================================================
    // RENDER DE UNA TARJETA DE PERIODO
    // ============================================================
    function renderStatusBadge(status) {
        return {
            creado: `<span class="badge bg-secondary">Creado</span>`,
            abierto: `<span class="badge bg-success">Abierto</span>`,
            cerrado: `<span class="badge bg-danger">Cerrado</span>`,
            finalizado: `<span class="badge bg-primary">Finalizado</span>`
        }[status];
    }

    function renderCardBorder(status) {
        return {
            creado: "border-secondary",
            abierto: "border-success",
            cerrado: "border-danger",
            finalizado: "border-primary"
        }[status];
    }


    // ============================================================
    // LISTAR PERIODOS (CON BÚSQUEDA)
    // ============================================================
    async function loadPeriods() {

        const res = await fetch("/api/registrationModule/periods/list.php", { headers });
        const data = await res.json();

        if (!data.ok) {
            container.innerHTML = `<p class='text-danger'>Error al cargar los periodos</p>`;
            return;
        }

        allPeriods = data.periods;
        renderPeriods();
    }


    function renderPeriods() {

        const text = searchInput.value.toLowerCase();
        const results = allPeriods.filter(p => {

            return (
                p.code.toLowerCase().includes(text) ||
                p.startDate.toLowerCase().includes(text) ||
                p.endDate.toLowerCase().includes(text) ||
                p.status.toLowerCase().includes(text)
            );

        });

        container.innerHTML = "";

        if (results.length === 0) {
            container.innerHTML = `<div class="col-12 text-center text-muted">No hay periodos que coincidan</div>`;
            return;
        }

        results.forEach(p => {

            container.innerHTML += `
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card shadow-sm mb-4 border ${renderCardBorder(p.status)}">

                        <div class="card-body">

                            <h5 class="fw-bold">
                                ${p.code} ${renderStatusBadge(p.status)}
                            </h5>

                            <p class="mb-1"><strong>Inicio:</strong> ${p.startDate}</p>
                            <p><strong>Fin:</strong> ${p.endDate}</p>

                            <button class="btn btn-warning btn-sm me-2"
                                onclick="editPeriod(${p.id}, '${p.code}', '${p.startDate}', '${p.endDate}', '${p.status}')">
                                <i class="bi bi-pencil-fill"></i> Editar
                            </button>

                            <button class="btn btn-info btn-sm text-white"
                                onclick="changeStatus(${p.id})">
                                <i class="bi bi-shuffle"></i> Estado
                            </button>

                        </div>
                    </div>
                </div>
            `;
        });
    }

    searchInput.addEventListener("input", renderPeriods);



    // ============================================================
    // CREAR PERIODO
    // ============================================================
    document.getElementById("btnNewPeriod").addEventListener("click", () => {
        modalCreate.show();
    });

    document.getElementById("btnSaveCreate").addEventListener("click", async () => {

        const payload = {
            code: document.getElementById("createCode").value.trim(),
            startDate: document.getElementById("createStart").value,
            endDate: document.getElementById("createEnd").value,
            status: document.getElementById("createStatus").value
        };

        if (!payload.code || !payload.startDate || !payload.endDate) {
            alert("Complete todos los campos.");
            return;
        }

        const res = await fetch("/api/registrationModule/periods/create.php", {
            method: "POST",
            headers,
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!data.ok) {
            alert(data.error || "Error al crear");
            return;
        }

        alert("Periodo creado correctamente");
        modalCreate.hide();
        loadPeriods();
    });



    // ============================================================
    // EDITAR PERIODO
    // ============================================================
    window.editPeriod = (id, code, start, end, status) => {

        document.getElementById("editId").value = id;
        document.getElementById("editCode").value = code;
        document.getElementById("editStart").value = start;
        document.getElementById("editEnd").value = end;
        document.getElementById("editStatus").value = status;

        modalEdit.show();
    };


    document.getElementById("btnSaveEdit").addEventListener("click", async () => {

        const payload = {
            id: document.getElementById("editId").value,
            code: document.getElementById("editCode").value.trim(),
            startDate: document.getElementById("editStart").value,
            endDate: document.getElementById("editEnd").value,
            status: document.getElementById("editStatus").value
        };

        if (!payload.code || !payload.startDate || !payload.endDate) {
            alert("Complete todos los campos.");
            return;
        }

        const res = await fetch("/api/registrationModule/periods/update.php", {
            method: "PUT",
            headers,
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!data.ok) {
            alert(data.error || "Error al actualizar");
            return;
        }

        alert("Periodo actualizado correctamente");
        modalEdit.hide();
        loadPeriods();
    });



    // ============================================================
    // CAMBIAR ESTADO
    // ============================================================
    window.changeStatus = (id) => {
        document.getElementById("statusId").value = id;
        modalStatus.show();
    };

    document.getElementById("btnSaveStatus").addEventListener("click", async () => {

        const payload = {
            id: document.getElementById("statusId").value,
            status: document.getElementById("statusNew").value
        };

        const res = await fetch("/api/registrationModule/periods/changeStatus.php", {
            method: "PUT",
            headers,
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!data.ok) {
            alert(data.error || "Error al cambiar estado");
            return;
        }

        alert("Estado actualizado correctamente");
        modalStatus.hide();
        loadPeriods();
    });


    // ============================================================
    // INICIO
    // ============================================================
    loadPeriods();

});
