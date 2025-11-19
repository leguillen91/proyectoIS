<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/enrollmentController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$controller = new EnrollmentController();
$controller->listAvailable($ctx);
