<body>
    <nav-bar></nav-bar>

    <div class="review-layout">
        <!-- Barra superior -->
        <header class="review-topbar">
            <div class="container-fluid d-flex justify-content-end">
                <form action="revisor.php" method="post" class="m-0">
                    <input
                        type="submit"
                        name="logout"
                        value="Cerrar sesión"
                        class="review-btn review-btn-logout"
                    >
                </form>
            </div>
        </header>

        <!-- Cuerpo principal -->
        <main class="review-main container-fluid">
            <div class="row g-3 review-main-row">
                <!-- Lado izquierdo: visor -->
                <section class="col-lg-8 col-md-7">
                    <article class="review-pane review-pane-viewer">
                        <div class="review-pane-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="review-pill">
                                    <i class="bi bi-file-earmark-text"></i>
                                </span>
                                <h5 class="mb-0 fw-semibold">
                                    Visor de solicitud
                                </h5>
                            </div>
                        </div>

                        <div
                            id="viewerContent"
                            class="review-pane-body placeholder-glow"
                        >
                            <span class="placeholder col-12 review-placeholder-full"></span>
                        </div>
                    </article>
                </section>

                <!-- Lado derecho: datos -->
                <aside class="col-lg-4 col-md-5">
                    <section class="review-pane review-pane-info">
                        <h4 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle"></i>
                            <span>Información del documento</span>
                        </h4>

                        <div class="review-info-card placeholder-glow">
                            <div class="review-info-label">Número de solicitud</div>
                            <span id="numero-solicitud" class="placeholder col-12"></span>
                        </div>

                        <div class="review-info-card placeholder-glow">
                            <div class="review-info-label">Solicitante</div>
                            <span id="solicitante" class="placeholder col-12"></span>
                        </div>

                        <div class="review-info-card placeholder-glow">
                            <div class="review-info-label">Tipo de documento</div>
                            <span id="tipo-documento" class="placeholder col-12"></span>
                        </div>

                        <div
                            id="contenedor-observaciones"
                            class="review-info-card placeholder-glow"
                        >
                            <div class="review-info-label">Observaciones</div>
                            <span class="placeholder col-12 review-placeholder-notes"></span>
                        </div>
                    </section>
                </aside>
            </div>
        </main>

        <!-- Barra inferior de acciones -->
        <footer class="review-footer">
            <div class="container-fluid">
                <div class="review-actions d-flex flex-wrap justify-content-center gap-2">
                    <button
                        id="btn-corregir"
                        class="btn review-action-btn review-action-warning"
                        type="button"
                        data-bs-target="#staticBackdrop"
                        onclick="correctRequestHandler()"
                    >
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Enviar a corregir
                    </button>

                    <button
                        id="btn-aceptar"
                        class="btn review-action-btn review-action-success"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#staticBackdrop"
                        onclick="acceptRequestHandler()"
                    >
                        <i class="bi bi-check-circle me-2"></i>
                        Aceptar solicitud
                    </button>

                    <button
                        id="btn-formato"
                        class="btn review-action-btn review-action-primary"
                        type="button"
                        onclick="downloadReportFormat()"
                    >
                        <i class="bi bi-download me-2"></i>
                        Formato de calificaciones
                    </button>

                    <button
                        id="btn-subir-calificaciones"
                        class="btn review-action-btn review-action-primary"
                        type="button"
                    >
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>
                        Cargar calificaciones
                    </button>
                </div>
            </div>
        </footer>
    </div>

    <!-- Modal de confirmación (ids se conservan) -->
    <div
        id="modal"
        class="modal fade"
        id="staticBackdrop"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        tabindex="-1"
        aria-labelledby="staticBackdropLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"></h1>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <!-- Se llena por JS -->
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary modal-reject"
                        data-bs-dismiss="modal"
                    >
                        Cerrar
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary modal-accept"
                        data-bs-dismiss="modal"
                    >
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div
        id="toast"
        class="toast align-items-center text-bg-primary border-0 position-absolute bottom-0 end-0"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        <div class="d-flex">
            <div class="toast-body"></div>
            <button
                type="button"
                class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast"
                aria-label="Close"
            ></button>
        </div>
    </div>

    <!-- Templates (ids intactos) -->
    <template id="template-observaciones">
        <textarea
            id="observaciones"
            class="form-control"
            rows="4"
            placeholder="Agregar observaciones..."
        ></textarea>
    </template>

    <template id="template-iframe">
        <iframe
            src="/public/api/Admisiones/get/archivoSolicitud/2"
            height="100%"
            class="col-12"
            frameborder="0"
        ></iframe>
    </template>

    <template id="template-upload-grades-form">
        <form id="resourceForm" enctype="multipart/form-data">
            <input type="hidden" name="tipo_recurso" value="calificaciones">
            <input type="hidden" name="visible" value="1">
            <input type="hidden" name="descargable" value="1">

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-file-upload me-2"></i>
                    Archivo de calificaciones
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label">Calificaciones en CSV</label>
                        <div class="file-upload csv-upload-area" id="csvUploadArea">
                            <i class="fas fa-file-csv"></i>
                            <h5>Arrastre y suelte su archivo CSV aquí</h5>
                            <p class="text-muted">o haga clic para seleccionar</p>
                            <input
                                type="file"
                                id="csvInput"
                                name="archivo_csv"
                                class="d-none"
                                accept=".csv"
                            >
                            <div class="file-info" id="csvInfo">
                                Formato soportado: CSV (Max. 20MB)
                            </div>
                            <div class="progress d-none" id="csvProgress">
                                <div
                                    class="progress-bar"
                                    role="progressbar"
                                    style="width: 0%"
                                ></div>
                            </div>
                        </div>
                        <div class="invalid-feedback" id="csvError">
                            El archivo CSV debe ser menor a 20MB.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="./../../../assets/js/homeReviewer.js"></script>
</body>
