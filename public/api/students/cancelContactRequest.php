<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$userId = $ctx['userId'];

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['requestId'] ?? null;

$controller = new StudentsController();
echo json_encode($controller->cancelContactRequest($userId, $requestId));
