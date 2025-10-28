<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Biblioteca Virtual</title>
    <link rel="stylesheet" href="/styles/biblioteca.css">
</head>
<body>
    <header>
        <div class="brand">BIBLIOTECA VIRTUAL</div>
        <div class="page-title">- INICIO</div>
    </header>
    <nav>
        <ul>
            <li><a href="biblioteca.php" class="active">Inicio</a></li>
            <li><a href="bibliotecaRecursos.php">Recursos</a></li>
            <li><a href="bibliotecaAsignaturas.php">Asignaturas</a></li>
            <li><a href="bibliotecaContacto.php">Contacto</a></li>
        </ul>
    </nav>
    <div class="hero"></div>
    <main class="recent-section">
        <h2>Recursos electrónicos vistos recientemente</h2>
        <div class="cards">
            <!-- Tarjeta de ejemplo 1 -->
            <div class="card">
                <div class="title-bar">IS-802 Somerville E.9</div>
                <!-- Sustituir src por la ruta al archivo de portada correspondiente -->
                <img src="imagenes/ingenieria_de_software.jpg" alt="Ingeniería de Software">
                <div class="card-content">
                    Ingeniería de Software
                </div>
            </div>
            <!-- Tarjeta de ejemplo 2 -->
            <div class="card">
                <div class="title-bar">IS-811 CCNP CISCO</div>
                <img src="imagenes/ccnp_ccie_security_core.jpg" alt="CCNP and CCIE Security Core">
                <div class="card-content">
                    CCNP and CCIE Security Core
                </div>
            </div>
            <!-- Tarjeta de ejemplo 3 -->
            <div class="card">
                <div class="title-bar">IS-811 CISCO CYBEROPS</div>
                <img src="imagenes/cisco_cyberops_associate.jpg" alt="Cisco CyberOps Associate">
                <div class="card-content">
                    Cisco CyberOps Associate
                </div>
            </div>
        </div>
    </main>
</body>
</html>
