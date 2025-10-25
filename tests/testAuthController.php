<?php
require_once __DIR__ . '/../classes/controllers/authController.php';

$auth = new AuthController($pdo, $config);

// 🔹 Login del admin creado anteriormente
$result = $auth->login('admin@unisys.local', 'ChangeMe#2025');
print_r($result);
