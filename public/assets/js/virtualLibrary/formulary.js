document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formRecurso");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const archivo = document.getElementById("archivo").files[0];

    if (!archivo) {
      alert("Por favor, selecciona un archivo PDF.");
      return;
    }

    if (archivo.type !== "application/pdf") {
      alert("El archivo debe ser un PDF.");
      return;
    }

    alert("✅ Recurso enviado correctamente (simulado).");

    const modal = bootstrap.Modal.getInstance(document.getElementById("modalRecurso"));
    modal.hide();

    form.reset();
  });
});
