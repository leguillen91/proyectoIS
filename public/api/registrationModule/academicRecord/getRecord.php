<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/academicRecordController.php';

header('Content-Type: application/json');

$ctx = requireAuth();

$controller = new AcademicRecordController();
$controller->getRecordByStudent($ctx);
