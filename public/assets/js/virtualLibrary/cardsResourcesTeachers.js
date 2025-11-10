// Datos para pruebas
const recursos = [
  {
    titulo: "Historia de Honduras",
    autor: "Guillermo Valera",
    tipoArchivo: "PDF",
    etiquetas: ["Libro", "Honduras"],
    categoria: "Documento"
  },
  {
    titulo: "Sociologia",
    autor: "Hector Martinez",
    tipoArchivo: "PDF",
    etiquetas: ["Libro", "Sociología"],
    categoria: "Documento"
  },
  {
    titulo: "Discurso del Método",
    autor: "René Descartes",
    tipoArchivo: "PDF",
    etiquetas: ["Libro", "Sociologia"],
    categoria: "Documento"
  }
];

const contenedor = document.getElementById("contenedorCards");

recursos.forEach(recurso => {
  const card = document.createElement("div");
  card.classList.add("col-12", "col-sm-6", "col-md-4", "col-lg-3");

  card.innerHTML = `
    <div class="card position-relative">
      <div class="card-header">${recurso.categoria}</div>
      <div class="card-body text-center">
        <h5 class="card-title">${recurso.titulo}</h5>
        <p class="card-text mb-2">${recurso.autor}</p>
        <span class="badge">${recurso.tipoArchivo}</span>
        <div class="mt-2 mb-2">
          ${recurso.etiquetas.map(tag => `<span class="tag">${tag}</span>`).join("")}
        </div>
        <div class="d-flex justify-content-center gap-2 mt-3">
          <button class="btn btn-view btn-outline-primary flex-fill action-btn">
            <i class="bi bi-eye-fill"></i> Ver
          </button>
          <button class="btn btn-danger flex-fill action-btn">
            <i class="bi bi-trash3-fill"></i> Eliminar
          </button>
        </div>
      </div>
    </div>
  `;

  contenedor.appendChild(card);
});

