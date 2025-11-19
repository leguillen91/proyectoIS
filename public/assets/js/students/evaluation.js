document.addEventListener("DOMContentLoaded", () => {

  const token = localStorage.getItem("accessToken");

  if (!token) {
    window.location.href = "../../index.php";
    return;
  }

  const btnSubmit = document.getElementById("btnSubmitEvaluation");

  btnSubmit.addEventListener("click", async () => {

    const rating = document.getElementById("rating").value;
    const comments = document.getElementById("comments").value.trim();

    const payload = {
      teacherId: 1, // En el módulo académico se actualizará esto
      subjectCode: "IS-110",
      period: "2025-I",
      rating,
      comments
    };

    try {
      const res = await fetch("/api/students/submitEvaluation.php", {
        method: "POST",
        headers: {
          "Authorization": "Bearer " + token,
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.ok) {
        alert("Evaluación enviada. Ahora puedes ver tus notas.");
        window.location.href = "grades.html";
      } else {
        alert("No se pudo enviar la evaluación.");
      }

    } catch (e) {
      console.error(e);
      alert("Error en el servidor");
    }
  });

});
