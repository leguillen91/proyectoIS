<?php
    session_start();
    include $_SERVER['DOCUMENT_ROOT'].'/config.php';
    $baseURL = APP_ENV == 'PROD' ? BACKEND_BASE_PATH : '/public/api/';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio sesión revisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./../../assets/css/revisorAdmision.css">
</head>

<body>
    <nav-bar></nav-bar>

    <div class="login-container">

        <div class="card login-card">
            <div class="card-header">
                <div class="logo-container">
                    <i class="bi bi-envelope"></i>
                </div>
                <h2 class="card-title">Inicia sesión como revisor</h2>
                <p class="card-subtitle">Inicia sesión para continuar</p>
            </div>

            <div class="card-body">
                <form id="loginForm" action="<?= $baseURL; ?>Admisiones/post/login/revisor/" method="post" >
                    <div class="form-group">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="position-relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="Ingresa tu correo"
                                required>
                            <div class="invalid-feedback" id="emailError"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="position-relative">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">
                                Recordarme
                            </label>
                        </div>
                        <a href="#" class="forgot-password" onclick="handleForgotPassword()">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-login w-100 mt-4" id="loginBtn">
                        <span id="loginText">
                            <span>Iniciar sesión</span>
                            <i class="bi bi-arrow-right ms-2"></i>
                        </span>
                        <span id="loadingText" style="display: none;">
                            <div class="loading-spinner d-inline-block"></div>
                            <span>Iniciando sesión...</span>
                        </span>
                    </button>
                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="./../../assets/js/loginReviewer.js" defer></script>
</body>

</html>