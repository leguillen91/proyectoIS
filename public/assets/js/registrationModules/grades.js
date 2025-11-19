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
    const studentsBody = document.getElementById("studentsBody");

    // ====================================
    // LOAD SECTIONS OF THE TEACHER
    // ====================================
    async function loadTeacherSections() {

        const res = await fetch("/api/registrationModule/sections/list.php", {
            headers: authHeaders
        });

        const data = await res.json();

        // Filter only sections assigned to this teacher
        const teacherSections = data.sections.filter(s =>
            s.teacherUserId === data.userId ||
            s.teacher === data.userFullName
        );

        sectionSelect.innerHTML = teacherSections
            .map(s => `
                <option value="${s.id}">
                    ${s.subjectCode} - ${s.subjectName} (Sec ${s.sectionCode})
                </option>
            `).join("");

        loadStudents();
    }

    // ====================================
    // LOAD STUDENTS BY SECTION
    // ====================================
    async function loadStudents() {

        const sectionId = sectionSelect.value;

        const res = await fetch(`/api/registrationModule/grades/getBySection.php?sectionId=${sectionId}`, {
            headers: authHeaders
        });

        const data = await res.json();

        studentsBody.innerHTML = "";

        data.students.forEach((s, i) => {

            studentsBody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.fullName}</td>
                    <td>${s.enrollmentCode}</td>

                    <td>
                        <input type="number" id="grade_${s.studentId}"
                            class="form-control form-control-sm"
                            min="0" max="100" value="${s.grade ?? ''}">
                    </td>

                    <td>
                        ${s.status ?? 'N/A'}
                    </td>

                    <td>
                        <button class="btn btn-primary btn-sm"
                            onclick="saveGrade(${s.studentId}, ${sectionId})">
                            Save
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    sectionSelect.addEventListener("change", loadStudents);

    // ====================================
    // SAVE GRADE
    // ====================================
    window.saveGrade = async (studentId, sectionId) => {

        const gradeValue = document.getElementById(`grade_${studentId}`).value;

        if (gradeValue === "") {
            alert("Enter a grade.");
            return;
        }

        const payload = {
            studentId: studentId,
            sectionId: sectionId,
            grade: gradeValue,
            status: gradeValue >= 65 ? "Aprobado" : "Reprobado"
        };

        const res = await fetch("/api/registrationModule/grades/assign.php", {
            method: "POST",
            headers: authHeaders,
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        alert(data.message);
        loadStudents();
    };

    // INIT
    loadTeacherSections();

});
