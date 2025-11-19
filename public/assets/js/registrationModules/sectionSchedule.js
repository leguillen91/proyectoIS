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

    const sectionSelect = document.getElementById("sectionSelect");
    const scheduleBody = document.getElementById("scheduleBody");

    const modal = new bootstrap.Modal("#modalAddSchedule");

    // ==============================
    // LOAD SECTIONS FOR SELECT
    // ==============================
    async function loadSections() {
        const res = await fetch("/api/registrationModule/sections/list.php", {
            headers: authHeaders
        });

        const data = await res.json();

        sectionSelect.innerHTML = data.sections
            .map(s => `<option value="${s.id}">
                ${s.subjectCode} - ${s.subjectName} (Sec ${s.sectionCode})
            </option>`)
            .join("");

        loadSchedule();
    }

    // ==============================
    // LOAD SCHEDULE FOR SECTION
    // ==============================
    async function loadSchedule() {
        const sectionId = sectionSelect.value;

        const res = await fetch(`/api/registrationModule/sectionSchedule/listBySection.php?sectionId=${sectionId}`, {
            headers: authHeaders
        });

        const data = await res.json();
        scheduleBody.innerHTML = "";

        data.schedule.forEach((s, i) => {
            scheduleBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.day}</td>
                    <td>${s.startTime}</td>
                    <td>${s.endTime}</td>

                    <td>
                        <button class="btn btn-danger btn-sm"
                            onclick="removeSchedule(${s.id})">
                            Remove
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    sectionSelect.addEventListener("change", loadSchedule);

    // ==============================
    // ADD SCHEDULE
    // ==============================
    document.getElementById("btnAddSchedule").addEventListener("click", () => {
        modal.show();
    });

    document.getElementById("btnSaveSchedule").addEventListener("click", async () => {

        const payload = {
            sectionId: sectionSelect.value,
            day: document.getElementById("addDay").value,
            startTime: document.getElementById("addStart").value,
            endTime: document.getElementById("addEnd").value
        };

        const res = await fetch("/api/registrationModule/sectionSchedule/add.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modal.hide();
        loadSchedule();
    });

    // ==============================
    // REMOVE SCHEDULE
    // ==============================
    window.removeSchedule = async (id) => {

        if (!confirm("Remove this schedule?")) return;

        const res = await fetch("/api/registrationModule/sectionSchedule/remove.php", {
            method: "DELETE",
            headers: authHeaders,
            body: JSON.stringify({ id })
        });

        const data = await res.json();
        alert(data.message);

        loadSchedule();
    };

    // INITIAL LOAD
    loadSections();
});
