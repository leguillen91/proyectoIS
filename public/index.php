<?php
// public/index.php
// Página inicial del sistema Project UNAH Systems

require_once __DIR__ . '/../bootstrap/init.php';

$conexionOk = false;

try {
  // Verificar conexión PDO
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
  <title>Project UNAH Systems</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    body { background: #f8f9fa; }
    .card { border-radius: 1rem; }
  </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-lg p-5 text-center" style="width: 500px;">
    <h2 class="mb-3"> Project UNAH Systems</h2>
    <p class="text-muted mb-4">Sistema de gestión universitaria desarrollado en PHP puro</p>

    <?php if ($conexionOk): ?>
      <div class="alert alert-success">
         Conexión a la base de datos establecida correctamente.
      </div>
      <div class="d-grid gap-3 mt-3">
        <a href="./views/login.html" class="btn btn-primary">Ir al Login</a>
        <!--<a href="./views/dashboard.html" class="btn btn-outline-secondary">Dashboard</a>-->
        <!--<a href="./views/adminPanel.html" class="btn btn-outline-dark">Panel Admin</a>-->
      </div>
    <?php else: ?>
      <div class="alert alert-danger">
         Error al conectar con la base de datos o entorno.
      </div>
      <p class="text-muted">Verifica tu archivo <code>bootstrap/init.php</code> y la configuración en <code>config/connection.php</code>.</p>
    <?php endif; ?>

    <hr class="mt-4">
    <small class="text-muted">
        Servidor local activo <br>
      <?= date('d/m/Y H:i:s') ?>
    </small>
  </div>
</body>
</html>
