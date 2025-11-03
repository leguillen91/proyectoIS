<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/controllers/studentController.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

$ctx = requireAuth();

$controller = new StudentController($pdo);

$body = json_decode(file_get_contents('php://input'), true);
$controller->create($ctx, $body);
