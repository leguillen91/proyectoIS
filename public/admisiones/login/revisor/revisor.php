<?php
    // include_once __DIR__.'/../../../../classes/utilities/Auth.php';
    session_start();
    include $_SERVER['DOCUMENT_ROOT'].'/config.php';
    $baseURL = APP_ENV == 'PROD' ? FRONTEND_BASE_PATH : '/public/';
    $privatePath = APP_ENV == 'PROD' ? $_SERVER['DOCUMENT_ROOT'].'/../' : './../../../../';
    include $privatePath.'classes/utilities/Auth.php';
    $loginPath = "{$baseURL}admisiones/log-in/login.php";
    
    if (!Auth::isAuthenticated()){
        header("Location: $loginPath");
    }

    if(isset($_POST["logout"])){
        session_destroy();
        header("Location: $loginPath");
    }
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./../../../assets/css/revisorHomeAdmision.css">
</head>

<body>

    <nav-bar></nav-bar>

    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="container-fluid">
                <form action="revisor.php" method="post">
                    <input type="submit" name="logout" value='Cerrar Sesión' class="back-button">
                        <!-- <i class="bi bi-arrow-left me-2">Cerrar Sessión</i> -->
                    </input>
                </form>
            </div>
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <div class="container-fluid h-100">
                <div class="row h-100">
                    <!-- Document Viewer -->
                    <div class="col-lg-8 col-md-7 p-0">
                        <div class="document-viewer">
                            <div class="viewer-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    Visor de solicitud
                                </h5>

                            </div>
                            <div class="viewer-content container placeholder-glow" id="viewerContent">
                                <span class="placeholder col-12" style="height: 100%;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Data Panel -->
                    <div class="col-lg-4 col-md-5 p-0">
                        <div class="data-panel">
                            <h4 class="mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Información del Documento
                            </h4>

                            <div class="data-card placeholder-glow">
                                <div class="data-label">Número de Solicitud</div>
                                <span id="numero-solicitud" class="placeholder col-12"></span>
                            </div>

                            <div class="data-card placeholder-glow">
                                <div class="data-label">Solicitante</div>
                                <span id="solicitante" class="placeholder col-12"></span>
                            </div>

                            <div class="data-card placeholder-glow">
                                <div class="data-label">Tipo de Documento</div>
                                <!-- <div class="data-value">Certificado de Estudios</div> -->
                                <span id="tipo-documento" class="placeholder col-12"></span>
                            </div>

                            <div id="contenedor-observaciones" class="data-card placeholder-glow">
                                <div class="data-label">Observaciones</div>
                                <span class="placeholder col-12" style="height: 10vh;"></span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <div class="container-fluid">
                <div class="text-center">
                    <!-- <button class="btn action-button btn-cancel" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop" onclick="cancelAction()">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button> -->
                    <button id="btn-corregir" class="btn action-button btn-correct" type="button" data-bs-target="#staticBackdrop" onclick="correctRequestHandler()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Enviar a Corregir
                    </button>
                    <button id="btn-aceptar" class="btn action-button btn-accept" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop" onclick="acceptRequestHandler()">
                        <i class="bi bi-check-circle me-2"></i>Aceptar Solicitud
                    </button>
                    <button id="btn-formato" class="btn action-button btn-primary" type="button" onclick="downloadReportFormat()">
                        <i class="bi bi-download me-2"></i>Formato de calificaciones
                    </button>
                    <button id="btn-subir-calificaciones" class="btn action-button btn-primary" type="button" >
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Cargar calificaciones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="staticBackdropLabel"></h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-reject" data-bs-dismiss="modal" >Cerrar</button>
            <button type="button" class="btn btn-primary modal-accept" data-bs-dismiss="modal" >Aceptar</button>
        </div>
        </div>
    </div>
    </div>

    <div id="toast" class="toast align-items-center text-bg-primary border-0 position-absolute bottom-0 end-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">

            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <template id="template-observaciones" >
        <textarea id="observaciones" class="form-control" rows="4" placeholder="Agregar observaciones..."></textarea>
    </template>

    <template id="template-iframe">
        <iframe src="/public/api/Admisiones/get/archivoSolicitud/2" height="100%" class="col-12" frameborder="0"></iframe>
    </template>

    <template id="template-upload-grades-form" >
        <form id="resourceForm" enctype="multipart/form-data">
            <!-- Campos ocultos para valores fijos -->
            <input type="hidden" name="tipo_recurso" value="calificaciones">
            <input type="hidden" name="visible" value="1">
            <input type="hidden" name="descargable" value="1">

            <!-- Archivos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-file-upload me-2"></i>Archivo de Calificaciones
                </div>
                <div class="card-body">

                    <!-- Archivo CSV (Partitura en CSV) -->
                    <div class="mb-4">
                        <label class="form-label">Calificaciones en CSV</label>
                        <div class="file-upload csv-upload-area" id="csvUploadArea">
                            <i class="fas fa-file-csv"></i>
                            <h5>Arrastre y suelte su archivo CSV aquí</h5>
                            <p class="text-muted">o haga clic para seleccionar</p>
                            <input type="file" id="csvInput" name="archivo_csv" class="d-none" accept=".csv">
                            <div class="file-info" id="csvInfo">
                                Formato soportado: CSV (Max. 20MB)
                            </div>
                            <div class="progress d-none" id="csvProgress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
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
    <script type="module" src="./../../../assets/js/homeReviewer.js" ></script>
</body>

</html>