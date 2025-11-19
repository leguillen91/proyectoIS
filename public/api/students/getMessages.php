<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

$ctx = requireAuth();
$controller = new StudentsController();

$contactId = $_GET['contactId'] ?? null;
$response = $controller->getMessages($ctx['userId'], $contactId);
echo json_encode($response);
