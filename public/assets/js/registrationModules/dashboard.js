document.addEventListener("DOMContentLoaded", async () => {

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "/index.php";
        return;
    }

    // ============================
    // Load user context
    // ============================
    let ctx = null;
    try {
        const res = await fetch("/api/auth/me.php", {
            headers: { "Authorization": "Bearer " + token }
        });
        const data = await res.json();

        if (!data.ok) {
            alert("Error loading session.");
            window.location.href = "/index.php";
            return;
        }

        ctx = data.user;

    } catch (e) {
        console.error(e);
        window.location.href = "/index.php";
        return;
    }

    // ============================
    // Helper functions
    // ============================
    function hide(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    }

    function show(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = "";
    }

    // ============================
    // Access Control by role
    // ============================
    const role = ctx.role.toLowerCase();

    // IDs of every dashboard card
    const cards = {
        periods: "cardPeriods",
        subjects: "cardSubjects",
        prerequisites: "cardPrerequisites",
        sections: "cardSections",
        sectionSchedule: "cardSectionSchedule",
        enrollment: "cardEnrollment",
        grades: "cardGrades",
        academicRecord: "cardAcademicRecord",
        enrollmentCalendar: "cardEnrollmentCalendar",
        logs: "cardLogs"
    };

    // Show all by default (admin will see everything)
    Object.values(cards).forEach(show);

    // ============================
    // ROLE-BASED VISIBILITY
    // ============================

    if (role === "student") {
        // Student: ONLY enrollment, academicRecord, logs
        hide(cards.periods);
        hide(cards.subjects);
        hide(cards.prerequisites);
        hide(cards.sections);
        hide(cards.sectionSchedule);
        hide(cards.grades);
        hide(cards.enrollmentCalendar);

        // Student uses:
        // - enrollment
        // - academicRecord
        // - logs

    } else if (role === "teacher") {
        // Teacher: ONLY grades, logs (optional), sections (optional)
        hide(cards.periods);
        hide(cards.subjects);
        hide(cards.prerequisites);
        hide(cards.sectionSchedule);
        hide(cards.enrollment);
        hide(cards.academicRecord);
        hide(cards.enrollmentCalendar);

        // Keep:
        // - grades
        // - sections (to see assigned ones if needed)
        // - logs (optional)

    } else if (role === "coordinator" || role === "depthead") {
        // Coordinators: full access EXCEPT student-only views
        hide(cards.enrollment);
        hide(cards.academicRecord);

        // Keep:
        // - periods
        // - subjects
        // - prerequisites
        - sections
        - sectionSchedule
        - grades
        - enrollmentCalendar
        - logs

    } else if (role === "admin") {
        // Admin sees EVERYTHING
        // No changes
    }

    // Done
});
