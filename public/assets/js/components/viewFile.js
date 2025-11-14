// =============================================================
// 🔹 Project UNAH Systems - Visor PDF Final (Render Seguro)
// ✅ Evita render simultáneo + controles estables
// =============================================================

const viewFile = (() => {
  let pdfDoc = null;
  let currentPage = 1;
  let scale = 1.2;
  let ctx, canvas, isRendering = false, pendingRender = null;

  // === Renderizar una página ===
  async function renderPage(num) {
    if (!pdfDoc || isRendering) {
      pendingRender = num;
      return;
    }

    isRendering = true;
    const page = await pdfDoc.getPage(num);
    const viewport = page.getViewport({ scale });

    // Escalar según el ancho visible del contenedor
    const container = document.getElementById("pdfFrame");
    const ratio = container.clientWidth / viewport.width;
    const adjustedViewport = page.getViewport({ scale: scale * ratio });

    // Ajustar tamaño del canvas
    canvas.height = adjustedViewport.height;
    canvas.width = adjustedViewport.width;

    const renderCtx = { canvasContext: ctx, viewport: adjustedViewport };
    const renderTask = page.render(renderCtx);

    try {
      await renderTask.promise;
    } catch (err) {
      console.warn(" Render cancelado:", err);
    }

    isRendering = false;
    document.getElementById("pageNum").textContent = num;
    document.getElementById("pageCount").textContent = pdfDoc.numPages;

    // Si hubo otro render pendiente, ejecutarlo después
    if (pendingRender) {
      const next = pendingRender;
      pendingRender = null;
      renderPage(next);
    }
  }

  // === Cambiar página ===
  function changePage(offset) {
    if (!pdfDoc) return;
    const newPage = currentPage + offset;
    if (newPage < 1 || newPage > pdfDoc.numPages) return;
    currentPage = newPage;
    renderPage(currentPage);
  }

  // === Zoom ===
  function zoom(delta) {
    if (!pdfDoc) return;
    scale = Math.min(Math.max(scale + delta, 0.5), 3.0);
    renderPage(currentPage);
  }

  // === Reset zoom ===
  function resetZoom() {
    scale = 1.2;
    renderPage(currentPage);
  }

  // === Cargar PDF ===
  async function loadPDF(url) {
    try {
      const loadingTask = window.pdfjsLib.getDocument({ url });
      pdfDoc = await loadingTask.promise;
      document.getElementById("loaderPdf").classList.add("d-none");
      document.getElementById("pdfControls").classList.remove("d-none");
      renderPage(currentPage);
    } catch (err) {
      console.error(" Error cargando PDF:", err);
      document.getElementById("loaderText").textContent = "Error al cargar el documento.";
    }
  }

  // === Abrir visor ===
  function open(pdfUrl, title = "Documento") {
    const modalEl = document.getElementById("pdfModal");
    const modal = new bootstrap.Modal(modalEl);

    // Reset del visor
    document.getElementById("pdfTitle").textContent = title;
    document.getElementById("loaderPdf").classList.remove("d-none");
    document.getElementById("pdfControls").classList.add("d-none");
    document.getElementById("loaderText").textContent = "Cargando documento...";
    document.getElementById("pdfFrame").innerHTML = `<canvas id="pdfCanvas"></canvas>`;

    canvas = document.getElementById("pdfCanvas");
    ctx = canvas.getContext("2d");

    modalEl.addEventListener("shown.bs.modal", () => {
      waitForPDFJS(() => loadPDF(pdfUrl));
    }, { once: true });

    modal.show();
    setTimeout(initControls, 400);
  }

  // === Inicializar controles ===
  function initControls() {
    const prev = document.getElementById("prevPage");
    const next = document.getElementById("nextPage");
    const zoomIn = document.getElementById("zoomIn");
    const zoomOut = document.getElementById("zoomOut");
    const zoomReset = document.getElementById("zoomReset");

    if (prev) prev.onclick = () => changePage(-1);
    if (next) next.onclick = () => changePage(1);
    if (zoomIn) zoomIn.onclick = () => zoom(0.2);
    if (zoomOut) zoomOut.onclick = () => zoom(-0.2);
    if (zoomReset) zoomReset.onclick = resetZoom;
  }

  // === Esperar PDF.js cargado ===
  function waitForPDFJS(callback) {
    if (window.pdfjsLib) return callback();
    const check = setInterval(() => {
      if (window.pdfjsLib) {
        clearInterval(check);
        window.pdfjsLib.GlobalWorkerOptions.workerSrc =
          "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
        callback();
      }
    }, 150);
  }

  // === Cargar PDF.js si no está presente ===
  function ensurePDFJS() {
    if (!window.pdfjsLib) {
      const script = document.createElement("script");
      script.src = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js";
      document.head.appendChild(script);
    }
  }

  document.addEventListener("DOMContentLoaded", ensurePDFJS);

  return { open };
})();

window.viewFile = viewFile;
