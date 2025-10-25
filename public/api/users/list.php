<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorize.php';
require_once __DIR__ . '/../../../classes/models/userModel.php';

// Verificar autenticación
$ctx = requireAuth();

// Solo admin o roles con permiso "users.manage"
authorize($ctx, ['users.manage'], ['admin']);

$userModel = new UserModel($pdo);

// Obtener todos los usuarios
$stmt = $pdo->query("
  SELECT u.id, u.fullName, u.email, r.roleName AS role
  FROM users u
  JOIN roles r ON r.id = u.roleId
  ORDER BY u.id ASC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'users' => $users]);
