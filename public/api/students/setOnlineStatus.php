<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

$ctx = requireAuth();
$controller = new StudentsController();

$data = json_decode(file_get_contents('php://input'), true);
$response = $controller->setOnlineStatus($ctx['userId'], $data);
echo json_encode($response);
