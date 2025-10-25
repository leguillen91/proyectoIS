<?php
require_once __DIR__ . '/../classes/models/userModel.php';

$model = new UserModel($pdo);
$user = $model->findByEmail('admin@unisys.local');

if ($user) {
  echo "✅ Usuario encontrado: " . $user['fullName'] . " (" . $user['roleName'] . ")\n";
} else {
  echo "❌ No se encontró el usuario.\n";
}
