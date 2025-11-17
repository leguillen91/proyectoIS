<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Admisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/formAdmision.css" rel="stylesheet">
</head>

<body>

    <nav-bar></nav-bar>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center">Llena este formulario para inscribirte a las admisiones</h2>
                    </div>
                    <div class="card-body">
                        <form id="admissionForm" method="post" action="/api/Admisiones/post/solicitudAdmision/" enctype="multipart/form-data">
                            <!-- Nombre completo -->
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre completo</label>
                                <input placeholder="Ingrese su nombre completo" type="text"
                                    class="form-control"
                                    id="nombre_completo"
                                    name="nombre_completo"
                                    required
                                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+"
                                    title="Solo letras y espacios">
                                <div class="invalid-feedback">Por favor ingrese un nombre válido (solo letras y espacios, sin caracteres especiales).</div>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_identificacion" class="form-label">Tipo de Identificación</label>
                                <select class="form-select" id="tipo_identificacion" name="tipo_identificacion" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    <option value="Hondureño(a)">Hondureño(a)</option>
                                    <option value="Extranjero(a)">Extranjero(a)</option>
                                </select>
                            </div>

                            <!-- Campo para identidad hondureña (inicialmente oculto) -->
                            <div class="mb-3" id="grupo_identidad" style="display: none;">
                                <label for="identidad" class="form-label">Número de Identidad Hondureña</label>
                                <input type="text" class="form-control" id="identidad" name="identidad"
                                    placeholder="0000-0000-00000" disabled>
                                <div class="invalid-feedback">
                                    Por favor ingrese un número de identidad válido.
                                </div>
                            </div>

                            <!-- Campo para pasaporte -->
                            <div class="mb-3" id="grupo_pasaporte" style="display: none;">
                                <label for="pasaporte" class="form-label">Número de Pasaporte</label>
                                <input type="text" class="form-control" id="pasaporte" name="pasaporte"
                                    placeholder="Ej: AB123456" disabled>
                                <div class="invalid-feedback">
                                    Por favor ingrese un número de pasaporte válido.
                                </div>
                            </div>

                            <!-- Número de Teléfono -->
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Número de Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" placeholder="+504 0000-0000" required>
                                <div class="invalid-feedback">Por favor ingrese un número de teléfono válido para Honduras.</div>
                            </div>

                            <!-- Correo Personal -->
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Personal</label>
                                <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
                                <div class="invalid-feedback">Por favor ingrese un correo electrónico válido con dominio existente.</div>
                            </div>

                            <!-- Foto de Certificado -->
                            <div class="mb-3">
                                <label for="certificado" class="form-label">Foto de Certificado de Secundaria</label>
                                <input type="file" class="form-control" id="certificado" name="certificado" accept="image/jpeg,image/jpg,image/png,image/webp,application/pdf" required>
                                <div class="invalid-feedback">
                                    El archivo debe ser JPG, JPEG, PNG, WEBP o PDF, no mayor a 2MB y con dimensiones mínimas de 800x600px.
                                </div>
                                <small class="text-muted">Formatos aceptados: JPG, PNG, PDF. Tamaño máximo: 2MB. Dimensiones mínimas: 800x600px.</small>
                            </div>

                            <!-- Centro Regional -->
                            <div class="mb-3">
                                <label for="centro_regional" class="form-label">Centro Regional</label>
                                <select class="form-select" id="centro_regional" name="centro_regional" required>
                                    <option value="" selected disabled>Seleccione un centro</option>

                                </select>
                                <div class="invalid-feedback">Por favor seleccione un centro regional.</div>
                            </div>

                            <!-- Carrera Principal -->
                            <div class="mb-3">
                                <label for="carrera_principal" class="form-label">Carrera Principal</label>
                                <select class="form-select" id="carrera_principal" name="carrera_principal" required disabled>
                                    <option value="" selected disabled>Seleccione un centro regional primero</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione una carrera principal.</div>
                            </div>

                            <!-- Carrera Secundaria -->
                            <div class="mb-3">
                                <label for="carrera_secundaria" class="form-label">Carrera Secundaria</label>
                                <select class="form-select" id="carrera_secundaria" name="carrera_secundaria" required disabled>
                                    <option value="" selected>Seleccione primero carrera principal</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitButton" disabled>
                                    Enviar Solicitud
                                </button>
                            </div>
                        </form>
                    </div>
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

    <custom-footer></custom-footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="../../assets/js/admisiones/formAdmision/main.js" defer></script>
</body>

</html>