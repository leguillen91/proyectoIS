document.addEventListener("DOMContentLoaded", async () => {

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    const authHeaders = {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

    const tbody = document.querySelector("#sectionsTable tbody");

    const modalCreate = new bootstrap.Modal("#modalCreateSection");
    const modalEdit = new bootstrap.Modal("#modalEditSection");

    // LOAD SUBJECTS
    async function loadSubjectsSelect() {
        const res = await fetch("/api/registrationModule/subjects/list.php", {
            headers: authHeaders
        });
        const data = await res.json();

        const sel = document.getElementById("createSubject");
        sel.innerHTML = data.subjects
            .map(s => `<option value="${s.id}">${s.code} - ${s.name}</option>`)
            .join("");
    }

    // LOAD PERIODS
    async function loadPeriodsSelect() {
        const res = await fetch("/api/registrationModule/periods/list.php", {
            headers: authHeaders
        });
        const data = await res.json();

        const sel = document.getElementById("createPeriod");
        sel.innerHTML = data.periods
            .map(p => `<option value="${p.id}">${p.code}</option>`)
            .join("");
    }

    // LOAD SECTIONS
    async function loadSections() {
        const res = await fetch("/api/registrationModule/sections/list.php", {
            headers: authHeaders
        });
        const data = await res.json();

        tbody.innerHTML = "";

        data.sections.forEach((s, i) => {
            tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.subjectCode} - ${s.subjectName}</td>
                    <td>${s.sectionCode}</td>
                    <td>${s.periodCode}</td>
                    <td>${s.teacher ?? 'N/A'}</td>
                    <td>${s.capacity}</td>

                    <td>
                        <button class="btn btn-warning btn-sm"
                            onclick="editSection(${s.id}, '${s.sectionCode}', '${s.teacher}', ${s.capacity})">
                            Edit
                        </button>

                        <button class="btn btn-danger btn-sm"
                            onclick="deleteSection(${s.id})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    // INIT
    loadSections();

    // OPEN CREATE MODAL
    document.getElementById("btnNewSection").addEventListener("click", async () => {
        await loadSubjectsSelect();
        await loadPeriodsSelect();
        modalCreate.show();
    });

    // SAVE CREATE
    document.getElementById("btnSaveCreate").addEventListener("click", async () => {

        const payload = {
            subjectId: document.getElementById("createSubject").value,
            periodId: document.getElementById("createPeriod").value,
            sectionCode: document.getElementById("createCode").value,
            teacher: document.getElementById("createTeacher").value,
            capacity: document.getElementById("createCapacity").value
        };

        const res = await fetch("/api/registrationModule/sections/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modalCreate.hide();
        loadSections();
    });

    // EDIT SECTION
    window.editSection = (id, code, teacher, capacity) => {
        document.getElementById("editId").value = id;
        document.getElementById("editCode").value = code;
        document.getElementById("editTeacher").value = teacher;
        document.getElementById("editCapacity").value = capacity;

        modalEdit.show();
    };

    // SAVE EDIT
    document.getElementById("btnSaveEdit").addEventListener("click", async () => {

        const payload = {
            id: document.getElementById("editId").value,
            sectionCode: document.getElementById("editCode").value,
            teacher: document.getElementById("editTeacher").value,
            capacity: document.getElementById("editCapacity").value
        };

        const res = await fetch("/api/registrationModule/sections/update.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modalEdit.hide();
        loadSections();
    });

    // DELETE SECTION
    window.deleteSection = async (id) => {
        if (!confirm("Delete this section?")) return;

        const res = await fetch("/api/registrationModule/sections/delete.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id })
        });

        const data = await res.json();
        alert(data.message);

        loadSections();
    };

});
