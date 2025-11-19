<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/periodsController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$controller = new PeriodsController();
$controller->list();
