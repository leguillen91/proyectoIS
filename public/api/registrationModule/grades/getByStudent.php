<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/gradesController.php';

header('Content-Type: application/json');

$ctx = requireAuth();

$controller = new GradesController();
$controller->getByStudent($ctx['studentId']);
