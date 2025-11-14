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

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-white" href="#">
        <img src="./assets/Logo.png" alt="Logo UNAH" class="navbar-logo me-2" style="height:45px;">
        UNAH Systems
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active fw-semibold" href="#">Inicio</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold" href="#" data-bs-toggle="dropdown">Estudiantes</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Pregrado</a></li>
              <li><a class="dropdown-item" href="#">Postgrado</a></li>
            </ul>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold" href="#" data-bs-toggle="dropdown">Admisiones</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Inscripciones</a></li>
              <li><a class="dropdown-item" href="#">Resultados</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="./views/library/virtualLibrary.html">Biblioteca Virtual</a>
          </li>
          
          <li class="nav-item" id="linkSoftware">
            <a class="nav-link fw-semibold" href="./views/librarySoftware/librarySoftware.html">Módulo Software</a>
          </li>
          <li class="nav-item" id="navSessionArea">
          <a id="btnLogin" class="nav-link fw-semibold" href="./views/login.html">Acceder</a>
        </li>


        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="py-5 text-center bg-white">
    <div class="container">
      <h1 class="fw-bold text-primary">Portal Institucional UNAH Systems</h1>
      <p class="lead mt-3">
        Bienvenido(a) al sistema académico integral de la
        <strong>Universidad Nacional Autónoma de Honduras (UNAH)</strong>.
      </p>
      <p class="text-muted">
        Accede a los módulos de Estudiantes, Admisiones, Biblioteca Virtual y Software Educativo desde un mismo lugar.
      </p>
      <a href="./views/login.html" class="btn btn-primary btn-lg mt-3 px-4">
        Iniciar Sesión
      </a>
    </div>
  </section>

  <!-- TABLÓN DE AVISOS -->
  <section class="py-5 bg-light border-top" id="tablon-avisos">
    <div class="container">
      <h2 class="text-center fw-bold text-primary mb-4">Tablón de Avisos</h2>
      <p class="text-center text-muted mb-5">Noticias y comunicados recientes del portal académico</p>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title fw-bold text-primary">Inscripción PHUMA 2025</h5>
              <p class="card-text small text-muted">
                Las inscripciones para la Prueba de Aptitud Académica estarán abiertas del 10 al 25 de enero.
              </p>
              <a href="#" class="btn btn-outline-primary btn-sm">Ver Aviso</a>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title fw-bold text-primary">Calendario Académico III PAC</h5>
              <p class="card-text small text-muted">
                Consulta las fechas importantes del III período académico para carreras trimestrales.
              </p>
              <a href="#" class="btn btn-outline-primary btn-sm">Ver Calendario</a>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title fw-bold text-primary">Reinicio de Clases UNAH 2025</h5>
              <p class="card-text small text-muted">
                El inicio del primer período académico será el lunes 17 de febrero de 2025.
              </p>
              <a href="#" class="btn btn-outline-primary btn-sm">Leer más</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ESTADO DE CONEXIÓN -->
  <div class="text-center mt-5 mb-4">
    <p class="small">
      <?= $conexionOk
        ? '<span class="text-success fw-bold">Conexión a la base de datos establecida</span>'
        : '<span class="text-danger fw-bold">Error en la conexión con la base de datos</span>' ?>
    </p>
  </div>

  <!-- FOOTER -->
  <footer class="bg-primary text-white text-center py-3 mt-3">
    <p class="mb-0 small">
      <strong>Project UNAH Systems</strong> © <?= date('Y') ?> — Desarrollado por Jhonny Hernández
    </p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
document.addEventListener("DOMContentLoaded", async () => {
  const sessionArea = document.getElementById("navSessionArea");
  const token = localStorage.getItem("accessToken");
  
  if (!sessionArea) {
    console.error("navSessionArea no encontrado");
    return;
  }

  if (!token) {
    console.log("No hay token, mostrando Acceder");
    return;
  }

  try {
    const res = await fetch("/api/auth/me.php", {
      method: "GET",
      headers: { "Authorization": "Bearer " + token }
    });

    const data = await res.json();
    console.log("SESSION DATA:", data);

    if (!data.ok) {
      console.warn("Sesión inválida. Mostrando Acceder.");
      return;
    }
    const roleMap = {
      student: "Estudiante",
      teacher: "Docente",
      coordinator: "Coordinador",
      depthead: "Jefe de Departamento",
      admin: "Administrador"
    };
    // ---- Validación por carrera ----
    // ---- Validación por carrera + admin ----
const linkSoftware = document.getElementById("linkSoftware");

// Carreras permitidas
const allowedCareers = [
  "Ingeniería en Sistemas",
  "Licenciatura en Informática"
];

const userCareer = data.user.career || "";
const userRole = data.user.role || "";

// Si NO pertenece a las carreras permitidas y NO es admin → ocultar
const isCareerAllowed = allowedCareers.includes(userCareer);
const isAdmin = userRole === "admin";

if (!isCareerAllowed && !isAdmin) {
  if (linkSoftware) linkSoftware.style.display = "none";
}


    const roleEs = roleMap[data.user.role] || data.user.role;
    const rawName = data.user.name || data.user.fullName || data.user.username || "Usuario";
    const userName = rawName.split(" ")[0];
    sessionArea.innerHTML = `
      <div class="dropdown">
        <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i> ${userName}
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item disabled">${roleEs}</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" id="btnLogout">Cerrar sesión</a></li>
        </ul>
      </div>
    `;

    document.getElementById("btnLogout").addEventListener("click", () => {
      localStorage.removeItem("accessToken");
      window.location.href = "index.php";
    });

  } catch (error) {
    console.error("Error cargando sesión:", error);
  }
});
</script>

</body>
</html>
