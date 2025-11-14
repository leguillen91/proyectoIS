document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formRecurso");
  const modal = new bootstrap.Modal(document.getElementById("modalRecurso"));
  const token = localStorage.getItem("accessToken");

  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const titulo = document.getElementById("titulo").value.trim();
    const autores = document.getElementById("autores").value.trim();
    const tags = document.getElementById("tags").value.trim();
    const archivo = document.getElementById("archivo").files[0];

    if (!titulo || !autores || !archivo) {
      alert("Por favor completa todos los campos obligatorios.");
      return;
    }

    if (archivo.type !== "application/pdf") {
      alert("Solo se permiten archivos PDF.");
      return;
    }

    const formData = new FormData();
    formData.append("title", titulo);
    formData.append("authors", autores);
    formData.append("tags", tags);
    formData.append("file", archivo);
    formData.append("category", "library"); // Identifica que pertenece a la biblioteca

    try {
      const res = await fetch("/api/resources/create.php", {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      const data = await res.json();
      if (!res.ok || !data.ok) {
        console.error("Error:", data);
        alert(data.error || "Error al subir el recurso.");
        return;
      }

      alert("📚 Recurso subido exitosamente.");
      form.reset();
      modal.hide();

      // Actualizar la lista de recursos después de subir
      if (typeof loadResources === "function") {
        loadResources();
      }

    } catch (err) {
      console.error("Error en subida:", err);
      alert("Ocurrió un error al conectar con el servidor.");
    }
  });
});
