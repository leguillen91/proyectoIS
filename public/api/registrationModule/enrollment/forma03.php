<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/enrollmentController.php';

header("Content-Type: application/json");

$controller = new EnrollmentController();
$controller->forma03();
