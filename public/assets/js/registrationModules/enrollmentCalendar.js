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

    const periodSelect = document.getElementById("periodSelect");
    const calendarBody = document.getElementById("calendarBody");

    const modalCreate = new bootstrap.Modal("#modalCreate");
    const modalEdit = new bootstrap.Modal("#modalEdit");

    // ======================================
    // LOAD PERIODS
    // ======================================
    async function loadPeriods() {
        const res = await fetch("/api/registrationModule/periods/list.php", {
            headers: authHeaders
        });

        const data = await res.json();

        periodSelect.innerHTML = data.periods
            .map(p => `<option value="${p.id}">${p.code}</option>`)
            .join("");

        loadCalendar();
    }

    // ======================================
    // LOAD CALENDAR FOR SELECTED PERIOD
    // ======================================
    async function loadCalendar() {
        const periodId = periodSelect.value;

        const res = await fetch(`/api/registrationModule/enrollmentCalendar/list.php?periodId=${periodId}`, {
            headers: authHeaders
        });

        const data = await res.json();

        calendarBody.innerHTML = "";

        data.calendar.forEach((c, i) => {

            calendarBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${c.minIndex}</td>
                    <td>${c.maxIndex}</td>
                    <td>${c.startDate}</td>
                    <td>${c.endDate}</td>

                    <td>
                        <button class="btn btn-warning btn-sm"
                            onclick="editRange(${c.id}, ${c.minIndex}, ${c.maxIndex}, '${c.startDate}', '${c.endDate}')">
                            Edit
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    periodSelect.addEventListener("change", loadCalendar);

    // ======================================
    // CREATE NEW RANGE
    // ======================================
    document.getElementById("btnNewRange").addEventListener("click", () => {
        modalCreate.show();
    });

    document.getElementById("btnSaveCreate").addEventListener("click", async () => {

        const payload = {
            periodId: periodSelect.value,
            minIndex: document.getElementById("createMin").value,
            maxIndex: document.getElementById("createMax").value,
            startDate: document.getElementById("createStart").value,
            endDate: document.getElementById("createEnd").value
        };

        const res = await fetch("/api/registrationModule/enrollmentCalendar/create.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modalCreate.hide();
        loadCalendar();
    });

    // ======================================
    // EDIT RANGE
    // ======================================
    window.editRange = (id, min, max, start, end) => {
        document.getElementById("editId").value = id;
        document.getElementById("editMin").value = min;
        document.getElementById("editMax").value = max;
        document.getElementById("editStart").value = start;
        document.getElementById("editEnd").value = end;

        modalEdit.show();
    };

    document.getElementById("btnSaveEdit").addEventListener("click", async () => {

        const payload = {
            id: document.getElementById("editId").value,
            minIndex: document.getElementById("editMin").value,
            maxIndex: document.getElementById("editMax").value,
            startDate: document.getElementById("editStart").value,
            endDate: document.getElementById("editEnd").value
        };

        const res = await fetch("/api/registrationModule/enrollmentCalendar/update.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modalEdit.hide();
        loadCalendar();
    });

    // INIT
    loadPeriods();

});
