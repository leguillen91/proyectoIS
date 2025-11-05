<?php
// public/index.php
require_once __DIR__ . '/../bootstrap/init.php';

$conexionOk = false;
try {
  $stmt = $pdo->query('SELECT 1');
  $conexionOk = $stmt ? true : false;
} catch (Exception $e) {
  $conexionOk = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Project UNAH Systems - Inicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/index.css">
</head>
<body>
  <div class="overlay d-flex flex-column justify-content-center align-items-center text-center text-white">
    <div class="container">
      <img src="./assets/unah_logo.png" alt="UNAH" width="90" class="mb-3" />
      <h5 class="fw-light mb-2">Universidad Nacional Autónoma de Honduras</h5>
      <h1 class="fw-bold mb-4">Bienvenido(a) al Portal Universitario</h1>

      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="./views/login.html" class="btn btn-warning fw-bold px-4 py-2 shadow">
          <i class="bi bi-person-fill"></i> Acceso al portal
        </a>
      </div>

      <p class="mt-5 text-light small">
        <?= $conexionOk
          ? '<span class="text-success fw-bold"> Conexión a la base de datos establecida</span>'
          : '<span class="text-danger fw-bold"> Error en la conexión con la base de datos</span>' ?>
      </p>
      <p class="text-light small mb-0">
        <strong>Project UNAH Systems</strong> © <?= date('Y') ?> - Desarrollado por Jhonny Hernández
      </p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
