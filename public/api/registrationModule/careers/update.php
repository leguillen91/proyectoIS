<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/careersController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$payload = json_decode(file_get_contents("php://input"), true);

$controller = new CareersController();
$controller->update($payload);
