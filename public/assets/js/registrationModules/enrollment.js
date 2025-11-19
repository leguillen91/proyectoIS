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

    const availableContainer = document.getElementById("availableContainer");
    const enrolledBody = document.getElementById("enrolledBody");
    const scheduleGrid = document.getElementById("scheduleGrid");

    const modalEnroll = new bootstrap.Modal("#modalEnroll");
    const modalEnrollText = document.getElementById("modalEnrollText");

    let selectedSectionId = null;

    // ================================
    // LOAD AVAILABLE SECTIONS
    // ================================
    async function loadAvailable() {
        const res = await fetch("/api/registrationModule/enrollment/listAvailable.php", {
            headers: authHeaders
        });

        const data = await res.json();

        availableContainer.innerHTML = "";

        data.sections.forEach(sec => {

            availableContainer.innerHTML += `
                <div class="col-md-4">
                    <div class="p-3 section-card bg-white">

                        <h5>${sec.subjectCode} - ${sec.subjectName}</h5>
                        <p class="mb-1"><strong>Section:</strong> ${sec.sectionCode}</p>
                        <p class="mb-1"><strong>Teacher:</strong> ${sec.teacher ?? "N/A"}</p>
                        <p class="mb-1"><strong>Period:</strong> ${sec.periodCode}</p>

                        <button class="btn btn-primary btn-sm mt-2"
                            onclick="prepareEnroll(${sec.id}, '${sec.subjectCode}', '${sec.sectionCode}')">
                            Enroll
                        </button>

                    </div>
                </div>
            `;
        });

    }

    // ================================
    // PREPARE ENROLL
    // ================================
    window.prepareEnroll = (id, code, sectionCode) => {
        selectedSectionId = id;
        modalEnrollText.textContent = `Enroll in ${code} section ${sectionCode}?`;
        modalEnroll.show();
    };

    // ================================
    // CONFIRM ENROLL
    // ================================
    document.getElementById("btnConfirmEnroll").addEventListener("click", async () => {

        const payload = { sectionId: selectedSectionId };

        const res = await fetch("/api/registrationModule/enrollment/enroll.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        alert(data.message);

        modalEnroll.hide();
        loadAvailable();
        loadEnrolled();
        loadSchedule();
    });

    // ================================
    // LOAD ENROLLED CLASSES
    // ================================
    async function loadEnrolled() {
        const res = await fetch("/api/registrationModule/enrollment/studentEnrollments.php", {
            headers: authHeaders
        });

        const data = await res.json();

        enrolledBody.innerHTML = "";

        data.enrollments.forEach((e, i) => {
            enrolledBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${e.subjectCode} - ${e.subjectName}</td>
                    <td>${e.sectionCode}</td>
                    <td>${e.periodCode}</td>
                    <td>${e.teacher ?? "N/A"}</td>
                    <td>${e.schedule ?? "N/A"}</td>

                    <td>
                        <button class="btn btn-danger btn-sm"
                            onclick="withdraw(${e.id})">
                            Withdraw
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    // ================================
    // WITHDRAW CLASS
    // ================================
    window.withdraw = async (enrollmentId) => {

        if (!confirm("Withdraw this class?")) return;

        const res = await fetch("/api/registrationModule/enrollment/withdraw.php", {
            method: "PUT",
            headers: authHeaders,
            body: JSON.stringify({ id: enrollmentId })
        });

        const data = await res.json();
        alert(data.message);

        loadAvailable();
        loadEnrolled();
        loadSchedule();
    };

    // ================================
    // WEEKLY SCHEDULE RENDER
    // ================================
    async function loadSchedule() {

        const res = await fetch("/api/registrationModule/enrollment/studentEnrollments.php", {
            headers: authHeaders
        });

        const data = await res.json();
        const list = data.enrollments;

        const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        scheduleGrid.innerHTML = `
            <div class="schedule-header">Time</div>
            ${days.map(d => `<div class="schedule-header">${d}</div>`).join("")}
        `;

        const hours = [...Array(15).keys()].map(h => 6 + h); // 6am–9pm

        hours.forEach(hour => {
            scheduleGrid.innerHTML += `<div>${hour}:00</div>`;
            days.forEach(day => {

                // Find a class at this time/day
                const match = list.find(cls =>
                    cls.rawSchedule?.some(sc =>
                        sc.day === day &&
                        sc.startHour === hour
                    )
                );

                scheduleGrid.innerHTML += `<div>
                    ${match ? `${match.subjectCode}-Sec${match.sectionCode}` : ""}
                </div>`;
            });
        });

    }

    loadAvailable();
    loadEnrolled();
    loadSchedule();
});
