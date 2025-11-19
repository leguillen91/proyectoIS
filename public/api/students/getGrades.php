<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

$ctx = requireAuth();
$controller = new StudentsController();

$response = $controller->getGrades($ctx['userId']);
echo json_encode($response);
