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

    const tbody = document.querySelector("#subjectsTable tbody");

    let allSubjects = [];
    let prereqCreate = [];
    let prereqEdit = [];

    const modalCreate = new bootstrap.Modal("#modalCreateSubject");
    const modalEdit = new bootstrap.Modal("#modalEditSubject");

    // ======================================================
    // ALERT BOOTSTRAP
    // ======================================================
    function showAlert(message, type = "danger") {
        const container = document.getElementById("alertContainer");
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    // ======================================================
    // LOAD DEPARTMENTS
    // ======================================================
    async function loadDepartments() {
        const res = await fetch("/api/registrationModule/subjects/departments.php", {
            headers: authHeaders
        });

        const data = await res.json();

        let options = '<option value="">Seleccione un departamento</option>';

        data.departments.forEach(dep => {
            options += `<option value="${dep.id}">${dep.name}</option>`;
        });

        document.getElementById("createDepartment").innerHTML = options;
        document.getElementById("editDepartment").innerHTML = options;
    }

    // ======================================================
    // LOAD SUBJECTS
    // ======================================================
    async function loadSubjects() {
        const res = await fetch("/api/registrationModule/subjects/list.php", {
            headers: authHeaders
        });

        const data = await res.json();

        allSubjects = data.subjects;

        tbody.innerHTML = "";

        data.subjects.forEach((s, i) => {
            tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.code}</td>
                    <td>${s.name}</td>
                    <td>${s.uv}</td>
                    <td>${s.departmentName}</td>

                    <td>
                        <button class="btn btn-warning btn-sm"
                            onclick="editSubject(${s.id})">Editar</button>

                        <button class="btn btn-danger btn-sm"
                            onclick="deleteSubject(${s.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });

        fillPrerequisiteSelects();
    }

    // ======================================================
    // FILL PREREQUISITE SELECTS
    // ======================================================
    function fillPrerequisiteSelects() {
        const options = allSubjects.map(s =>
            `<option value="${s.id}">${s.code} - ${s.name}</option>`
        ).join("");

        document.getElementById("prereqSelectCreate").innerHTML = options;
        document.getElementById("prereqSelectEdit").innerHTML = options;
    }

    // inicializar
    await loadDepartments();
    await loadSubjects();

    // ======================================================
    // CREATE SUBJECT
    // ======================================================
    document.getElementById("chkPrerequisites").addEventListener("change", e => {
        document.getElementById("prereqAreaCreate").style.display =
            e.target.checked ? "block" : "none";
    });

    document.getElementById("btnNewSubject").addEventListener("click", () => {
        prereqCreate = [];
        document.getElementById("prereqListCreate").innerHTML = "";
        modalCreate.show();
    });

    document.getElementById("btnAddPrereqCreate").addEventListener("click", () => {
        const id = document.getElementById("prereqSelectCreate").value;
        if (!prereqCreate.includes(id)) prereqCreate.push(id);
        renderPrereqListCreate();
    });

    function renderPrereqListCreate() {
        const cont = document.getElementById("prereqListCreate");
        cont.innerHTML = prereqCreate.map(id => {
            const s = allSubjects.find(x => x.id == id);
            if (!s) return "";
            return `
                <span class="pill">
                    ${s.code}
                    <button onclick="removePrereqCreate('${id}')">×</button>
                </span>
            `;
        }).join("");
    }

    window.removePrereqCreate = id => {
        prereqCreate = prereqCreate.filter(x => x != id);
        renderPrereqListCreate();
    };

    document.getElementById("btnSaveCreate").addEventListener("click", async () => {

        const payload = {
            code: document.getElementById("createCode").value,
            name: document.getElementById("createName").value,
            uv: document.getElementById("createUv").value,
            departmentId: document.getElementById("createDepartment").value
        };

        const res = await fetch("/api/registrationModule/subjects/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.message, "danger");
            return;
        }

        const newSubjectId = json.id;

        // add prerequisites
        for (const pid of prereqCreate) {
            await fetch("/api/registrationModule/subjectPrerequisites/add.php", {
                method: "POST",
                headers: authHeaders,
                body: JSON.stringify({
                    subjectId: newSubjectId,
                    prerequisiteId: pid
                })
            });
        }

        showAlert("Materia creada correctamente", "success");
        modalCreate.hide();
        loadSubjects();
    });

    // ======================================================
    // EDIT SUBJECT
    // ======================================================
    window.editSubject = async id => {

        prereqEdit = [];

        const s = allSubjects.find(x => x.id == id);

        document.getElementById("editId").value = id;
        document.getElementById("editCode").value = s.code;
        document.getElementById("editName").value = s.name;
        document.getElementById("editUv").value = s.uv;
        document.getElementById("editDepartment").value = s.departmentId;

        // get prerequisites
        const res = await fetch(`/api/registrationModule/subjectPrerequisites/listBySubject.php?subjectId=${id}`, {
            headers: authHeaders
        });

        const data = await res.json();

        // if no requirements: show empty UI
        if (!data.prerequisites || data.prerequisites.length === 0) {
            prereqEdit = [];
            renderPrereqListEdit();
        } else {
            prereqEdit = data.prerequisites.map(p => p.prerequisiteId);
            renderPrereqListEdit();
        }

        modalEdit.show();
    };


    function renderPrereqListEdit() {
    const cont = document.getElementById("prereqListEdit");

    // no prerequisites
    if (prereqEdit.length === 0) {
        cont.innerHTML = `
            <div class="text-muted fst-italic">No tiene requisitos asignados.</div>
        `;
        return;
    }

    // has prerequisites
    cont.innerHTML = prereqEdit.map(id => {
        const s = allSubjects.find(x => x.id == id);
        if (!s) return "";
        return `
            <span class="pill">
                ${s.code}
                <button onclick="removePrereqEdit('${id}')">×</button>
            </span>
        `;
    }).join("");
}
    document.getElementById("btnClearPrereqEdit").addEventListener("click", () => {
        prereqEdit = [];
        renderPrereqListEdit();
    });

    window.removePrereqEdit = id => {
        prereqEdit = prereqEdit.filter(x => x != id);
        renderPrereqListEdit();
    };

    document.getElementById("btnSaveEdit").addEventListener("click", async () => {

        const subjectId = document.getElementById("editId").value;

        const payload = {
            id: subjectId,
            code: document.getElementById("editCode").value,
            name: document.getElementById("editName").value,
            uv: document.getElementById("editUv").value,
            departmentId: document.getElementById("editDepartment").value
        };

        const res = await fetch("/api/registrationModule/subjects/update.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.message, "danger");
            return;
        }

        // remove and re-add prereqs
        await fetch("/api/registrationModule/subjectPrerequisites/remove.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ subjectId })
        });

        for (const pid of prereqEdit) {
            await fetch("/api/registrationModule/subjectPrerequisites/add.php", {
                method: "POST",
                headers: authHeaders,
                body: JSON.stringify({
                    subjectId,
                    prerequisiteId: pid
                })
            });
        }

        showAlert("Materia actualizada correctamente", "success");
        modalEdit.hide();
        loadSubjects();
    });

    // ======================================================
    // DELETE SUBJECT
    // ======================================================
    window.deleteSubject = async id => {
        if (!confirm("¿Desea eliminar esta materia?")) return;

        const res = await fetch("/api/registrationModule/subjects/delete.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id })
        });

        const json = await res.json();

        if (!json.ok) {
            showAlert(json.message, "danger");
            return;
        }

        showAlert("Materia eliminada correctamente", "success");
        loadSubjects();
    };

});
