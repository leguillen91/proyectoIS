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

    const logsContainer = document.getElementById("logsContainer");

    // =========================================
    // LOAD LOGS
    // =========================================
    async function loadLogs() {

        const res = await fetch("/api/registrationModule/enrollmentLogs/listByStudent.php", {
            headers: authHeaders
        });

        const data = await res.json();

        logsContainer.innerHTML = "";

        data.logs.forEach(log => {

            logsContainer.innerHTML += `
                <div class="log-card">

                    <div class="log-title">
                        ${log.actionType === "enroll" ? "Enrollment" : "Withdrawal"}
                        — ${log.subjectCode} (${log.sectionCode})
                    </div>

                    <div class="log-item">
                        <strong>Student:</strong> ${log.studentName} (${log.enrollmentCode})
                    </div>

                    <div class="log-item">
                        <strong>Period:</strong> ${log.periodCode}
                    </div>

                    <div class="log-item">
                        <strong>Date:</strong> ${log.actionDate}
                    </div>

                    <div class="log-item">
                        <strong>User:</strong> ${log.userName}
                    </div>

                </div>
            `;
        });

    }

    loadLogs();

});
