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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Project UNAH Systems - Portal Institucional</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="./assets/Logo.png" type="image/png" />
  <link rel="stylesheet" href="./assets/css/landingPage/landingPage.css" />
</head>

<body class="bg-light">

  <nav-bar></nav-bar>

   <?php include __DIR__ . '/views/landingPage/landingBody.php'; ?>

  <!-- ESTADO DE CONEXIÓN -->
  <div class="text-center mt-5 mb-4">
    <p class="small">
      <?= $conexionOk
        ? '<span class="text-success fw-bold">Conexión a la base de datos establecida</span>'
        : '<span class="text-danger fw-bold">Error en la conexión con la base de datos</span>' ?>
    </p>
  </div>

  <custom-footer></custom-footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/js/components/NavBarSession.js" defer></script>
  <script type="module" src="assets/js/admisiones/menu/main.js"></script>

</body>
</html>
