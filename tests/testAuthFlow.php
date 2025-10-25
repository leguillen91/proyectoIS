<?php
/**
 * Script de prueba automatizada del módulo Auth
 * Flujo: LOGIN → ME → REGISTER → LOGOUT
 */

header('Content-Type: application/json');

// Inicialización global
require_once __DIR__ . '/../bootstrap/init.php';
require_once __DIR__ . '/../classes/controllers/authController.php';
require_once __DIR__ . '/../middleware/requireAuth.php';
require_once __DIR__ . '/../middleware/authorize.php';

$auth = new AuthController($pdo, $config);

echo "🚀 Iniciando pruebas del módulo Auth...\n\n";


// 1️LOGIN ADMIN

echo "1️  Probando LOGIN...\n";
$login = $auth->login('admin@unisys.local', 'ChangeMe#2025');

if (!$login['ok']) {
  echo "❌ Error en login: {$login['error']}\n";
  exit;
}

$token = $login['token'];
echo "✅ Login exitoso. Token generado.\n\n";


// 2️ ME (Validar contexto de usuario autenticado)

echo "2️  Validando CONTEXTO (me)...\n";

$cliToken = $token;
$ctx = requireAuth();
if ($ctx['role'] === 'admin') {
  echo "✅ Contexto correcto. Usuario autenticado como: {$ctx['role']}\n\n";
} else {
  echo "❌ Error: Rol incorrecto.\n";
  exit;
}


// 3️ REGISTER (Crear usuario nuevo)

echo "3️  Probando REGISTRO de nuevo usuario...\n";

authorize($ctx, ['users.manage'], ['admin']);

$newUserEmail = 'testuser' . rand(100, 999) . '@unah.edu';
$newUser = $auth->register(
  'Usuario de prueba',
  $newUserEmail,
  '0801200100000',
  null,
  'student',
  'Password123!'
);

if ($newUser['ok']) {
  echo "✅ Usuario registrado correctamente: {$newUserEmail}\n\n";
} else {
  echo "❌ Error al registrar: {$newUser['error']}\n";
  exit;
}


// 4️ LOGOUT

echo "4️  Probando LOGOUT...\n";
require_once __DIR__ . '/../public/api/auth/logout.php';
echo "\n✅ Logout ejecutado correctamente.\n";

// ======================================================
// 🎯 RESULTADO FINAL
// ======================================================
echo "\n🎯 Todas las pruebas del módulo AUTH se completaron exitosamente.\n";
