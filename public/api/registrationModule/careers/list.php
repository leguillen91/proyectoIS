<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/careersController.php';

header('Content-Type: application/json');

$ctx = requireAuth();

$controller = new CareersController();
$controller->list();
