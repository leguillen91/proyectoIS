<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$userId = $ctx['userId'];

$controller = new StudentsController();
echo json_encode($controller->listSentRequests($userId));
