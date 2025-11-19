<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/enrollmentCalendarController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$periodId = $_GET['periodId'] ?? null;

$controller = new EnrollmentCalendarController();
$controller->list($periodId);
