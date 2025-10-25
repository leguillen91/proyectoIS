<?php
// scripts/createAdmin.php
require_once __DIR__ . '/../config/connection.php';
$config = require __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/services/authService.php';
require_once __DIR__ . '/../classes/models/userModel.php';

$authService = new AuthService($config['security']['pepper']);
$userModel = new UserModel($pdo);

// Obtener el ID del rol admin
$stmt = $pdo->prepare("SELECT id FROM roles WHERE roleName = 'admin' LIMIT 1");
$stmt->execute();
$role = $stmt->fetch();

if (!$role) {
  die("❌ No se encontró el rol 'admin' en la base de datos.\n");
}

$fullName = 'System Administrator';
$email = 'admin@unisys.local';
$password = 'ChangeMe#2025';

$salt = $authService->generateSalt();
$hash = $authService->hashPassword($password, $salt);

try {
  $userId = $userModel->create($fullName, $email, '', null, (int)$role['id'], $hash, $salt);
  echo "✅ Admin creado con éxito:\n";
  echo "  ID: {$userId}\n";
  echo "  Email: {$email}\n";
  echo "  Password: {$password}\n";
} catch (Exception $e) {
  echo "❌ Error al crear el admin: " . $e->getMessage() . "\n";
}
