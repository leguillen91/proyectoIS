document.addEventListener("DOMContentLoaded", async () => {

    // ============================
    //  AUTH
    // ============================
    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const authHeaders = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    // ============================
    //  TABLE BODY
    // ============================
    const periodsTableBody = document.querySelector("#periodsTable tbody");

    // =======================================================
    // 🔵 INSTANCIAS DE MODALES (CORRECCIÓN OBLIGATORIA)
    // =======================================================
    const modalCreate = new bootstrap.Modal(document.getElementById("modalCreatePeriod"));
    const modalEdit = new bootstrap.Modal(document.getElementById("modalEditPeriod"));
    const modalStatus = new bootstrap.Modal(document.getElementById("modalStatusPeriod"));

    // ============================
    //  LOAD PERIODS
    // ============================
    async function loadPeriods() {
        const res = await fetch("/api/registrationModule/periods/list.php", {
            headers: authHeaders
        });

        const data = await res.json();
        if (!data.ok) return alert("Error loading periods");

        periodsTableBody.innerHTML = "";

        data.periods.forEach((p, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${p.code}</td>
                    <td>${p.startDate}</td>
                    <td>${p.endDate}</td>
                    <td class="fw-bold">${p.status}</td>

                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editPeriod(${p.id}, '${p.code}', '${p.startDate}', '${p.endDate}')">
                            Edit
                        </button>

                        <button class="btn btn-info btn-sm text-white" onclick="changeStatus(${p.id})">
                            Status
                        </button>
                    </td>
                </tr>
            `;
            periodsTableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    loadPeriods();

    // =======================================================
    //  CREATE PERIOD
    // =======================================================
        const btnNewPeriod = document.getElementById("btnNewPeriod");
        const btnSaveCreate = document.getElementById("btnSaveCreate");

        btnNewPeriod.addEventListener("click", () => {
            modalCreate.show();
        });

        btnSaveCreate.addEventListener("click", async () => {

        const data = {
            code: document.getElementById("createCode").value,
            startDate: document.getElementById("createStartDate").value,
            endDate: document.getElementById("createEndDate").value,
            status: document.getElementById("createStatus").value
        };

        const res = await fetch("/api/registrationModule/periods/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(data)
        });

        const json = await res.json();

        if (!json.ok) {
            alert(json.message);
            return; // ❌ no cerrar modal
        }

        alert(json.message);
        modalCreate.hide(); // ✔ cerrar solo cuando se crea bien
        loadPeriods();
    });


    // =======================================================
    //  EDIT PERIOD
    // =======================================================
    window.editPeriod = (id, code, start, end) => {
        document.getElementById("editId").value = id;
        document.getElementById("editCode").value = code;
        document.getElementById("editStartDate").value = start;
        document.getElementById("editEndDate").value = end;

        modalEdit.show(); // ✔ YA FUNCIONA
    };

    const btnSaveEdit = document.getElementById("btnSaveEdit");

    btnSaveEdit.addEventListener("click", async () => {

        const data = {
            id: document.getElementById("editId").value,
            code: document.getElementById("editCode").value,
            startDate: document.getElementById("editStartDate").value,
            endDate: document.getElementById("editEndDate").value
        };

        const res = await fetch("/api/registrationModule/periods/update.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(data)
        });

        const json = await res.json();

        if (!json.ok) {
            alert(json.message);
            return; // ❌ no cierres el modal
        }

        alert(json.message);
        modalEdit.hide(); // ✔ solo si ok
        loadPeriods();
    });


    // =======================================================
    //  CHANGE STATUS
    // =======================================================
    window.changeStatus = (id) => {
        document.getElementById("statusId").value = id;
        modalStatus.show(); // ✔ YA FUNCIONA
    };

    const btnSaveStatus = document.getElementById("btnSaveStatus");

    btnSaveStatus.addEventListener("click", async () => {

        const data = {
            id: document.getElementById("statusId").value,
            status: document.getElementById("statusNew").value
        };

        const res = await fetch("/api/registrationModule/periods/changeStatus.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(data)
        });

        const json = await res.json();
        alert(json.message);

        modalStatus.hide(); // ✔ YA FUNCIONA

        loadPeriods();
    });

});
