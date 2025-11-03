<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignaturas - Biblioteca Virtual</title>
    <link rel="stylesheet" href="/styles/biblioteca.css">
</head>
<body>
    <header>
        <div class="brand">BIBLIOTECA VIRTUAL</div>
        <div class="page-title">- ASIGNATURAS</div>
    </header>
    <nav>
        <ul>
            <li><a href="biblioteca.php">Inicio</a></li>
            <li><a href="bibliotecaRecursos.php">Recursos</a></li>
            <li><a href="bibliotecaAsignaturas.php" class="active">Asignaturas</a></li>
            <li><a href="bibliotecaContacto.php">Contacto</a></li>
        </ul>
    </nav>
    <div class="hero"></div>
    <main>
        <!-- Overlay con buscador de asignaturas -->
        <div class="modal-overlay">
            <div class="modal">
                <ul class="steps">
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                </ul>
                <div class="modal-content">
                    <h2>Buscar asignatura...</h2>
                    <div class="search-bar">
                        <input type="text" placeholder="Buscar asignatura...">
                    </div>
                    <ul class="list">
                        <li>Introducción a la Ingeniería en Sistemas</li>
                        <li>Cálculo II</li>
                        <li>Ecuaciones Diferenciales</li>
                        <li>Programación orientada a objetos</li>
                        <li>Sistemas operativos II</li>
                        <li>Ingeniería del software</li>
                        <li>Seguridad Informática</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
