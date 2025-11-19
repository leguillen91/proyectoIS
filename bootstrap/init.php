<?php
// Aqui lo cree para mantener mas segura la conexion y configuracion. y que sirva capa media de estas rutas
// Este archivo inicializa la configuración y la conexión a la base de datos.

require_once __DIR__ . '/../config/connection.php';
$config = require __DIR__ . '/../config/env.php';
putenv('APP_ENV=dev');
