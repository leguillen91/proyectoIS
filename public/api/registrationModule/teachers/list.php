<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/teachersController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$controller = new TeachersController();

$response = $controller->list();
echo json_encode($response);
