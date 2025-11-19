document.addEventListener("DOMContentLoaded", () => {

  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  loadCertificate();

  async function loadCertificate() {
    try {
      const res = await fetch("/api/students/getCertificateData.php", {
        headers: { "Authorization": "Bearer " + token }
      });

      const data = await res.json();

      if (!data.ok) {
        alert("No se pudo cargar el certificado.");
        return;
      }

      const studentDiv = document.getElementById("studentInfo");
      const recordTbody = document.querySelector("#recordTable tbody");

      // Datos del estudiante
      studentDiv.innerHTML = `
        <p><strong>Nombre:</strong> ${data.student.fullName}</p>
        <p><strong>Número de Cuenta:</strong> ${data.student.enrollmentCode}</p>
        <p><strong>Carrera:</strong> ${data.student.career}</p>
        <p><strong>Centro:</strong> ${data.student.academicCenter}</p>
      `;

      // Historial
      let html = "";
      data.record.forEach(r => {
        html += `
          <tr>
            <td>${r.subjectCode}</td>
            <td>${r.subjectName}</td>
            <td>${r.grade}</td>
            <td>${r.period}</td>
            <td>${r.status}</td>
          </tr>
        `;
      });
      recordTbody.innerHTML = html;

      // Fecha
      document.getElementById("dateField").textContent = 
        "Emitido el: " + new Date().toLocaleDateString();

    } catch (e) {
      console.error(e);
      alert("Error al cargar datos.");
    }
  }

});
