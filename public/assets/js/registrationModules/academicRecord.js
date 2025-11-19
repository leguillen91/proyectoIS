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

    const recordBody = document.getElementById("recordBody");
    const indexCard = document.getElementById("indexCard");

    // ==============================
    // LOAD ACADEMIC RECORD
    // ==============================
    async function loadRecord() {
        const res = await fetch("/api/registrationModule/academicRecord/getRecord.php", {
            headers: authHeaders
        });

        const data = await res.json();

        recordBody.innerHTML = "";

        data.record.forEach((r, i) => {
            recordBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${r.subjectCode} - ${r.subjectName}</td>
                    <td>${r.grade ?? "N/A"}</td>
                    <td>${r.uv}</td>
                    <td>${r.periodCode}</td>
                    <td>${r.status}</td>
                </tr>
            `;
        });
    }

    // ==============================
    // LOAD INDEX
    // ==============================
    async function loadIndex() {

        const res = await fetch("/api/registrationModule/academicRecord/calculateIndex.php", {
            headers: authHeaders
        });

        const data = await res.json();

        indexCard.innerHTML = `
            <strong>Academic Index:</strong> ${data.index}%<br>
            <strong>Approved UV:</strong> ${data.approvedUv}<br>
            <strong>Total UV:</strong> ${data.totalUv}
        `;
    }

    // INIT
    loadRecord();
    loadIndex();
});
