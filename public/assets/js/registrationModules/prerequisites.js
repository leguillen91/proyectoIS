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

    const subjectSelect = document.getElementById("subjectSelect");
    const prereqBody = document.getElementById("prereqBody");

    const modal = new bootstrap.Modal("#modalAddPrerequisite");
    const addPrerequisiteSelect = document.getElementById("addPrerequisiteSelect");

    let subjects = [];

    // ===========================
    // LOAD ALL SUBJECTS
    // ===========================
    async function loadSubjects() {
        const res = await fetch("/api/registrationModule/subjects/list.php", {
            headers: authHeaders
        });
        const data = await res.json();

        subjects = data.subjects;

        subjectSelect.innerHTML = subjects
            .map(s => `<option value="${s.id}">${s.code} - ${s.name}</option>`)
            .join("");

        addPrerequisiteSelect.innerHTML = subjects
            .map(s => `<option value="${s.id}">${s.code} - ${s.name}</option>`)
            .join("");

        loadPrerequisites();
    }

    // ===========================
    // LOAD PREREQUISITES BY SUBJECT
    // ===========================
    async function loadPrerequisites() {
        const subjectId = subjectSelect.value;

        const res = await fetch(`/api/registrationModule/subjectPrerequisites/listBySubject.php?subjectId=${subjectId}`, {
            headers: authHeaders
        });
        const data = await res.json();

        prereqBody.innerHTML = "";
        data.prerequisites.forEach((p, i) => {

            prereqBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${p.prerequisiteCode} - ${p.prerequisiteName}</td>

                    <td>
                        <button class="btn btn-danger btn-sm"
                            onclick="removePrerequisite(${p.id})">
                            Remove
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    subjectSelect.addEventListener("change", loadPrerequisites);

    // ===========================
    // ADD PREREQUISITE
    // ===========================
    document.getElementById("btnAddPrerequisite").addEventListener("click", () => {
        modal.show();
    });

    document.getElementById("btnSavePrerequisite").addEventListener("click", async () => {

        const payload = {
            subjectId: subjectSelect.value,
            prerequisiteId: addPrerequisiteSelect.value
        };

        const res = await fetch("/api/registrationModule/subjectPrerequisites/add.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modal.hide();
        loadPrerequisites();
    });

    // ===========================
    // REMOVE PREREQUISITE
    // ===========================
    window.removePrerequisite = async (id) => {

        if (!confirm("Remove this prerequisite?")) return;

        const res = await fetch("/api/registrationModule/subjectPrerequisites/remove.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id })
        });

        const data = await res.json();
        alert(data.message);

        loadPrerequisites();
    };

    loadSubjects();
});
