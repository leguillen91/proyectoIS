<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/careerSubjectsController.php';

header('Content-Type: application/json');

$ctx = requireAuth();

if (!isset($_GET['careerId'])) {
    echo json_encode(['ok' => false, 'error' => 'Missing careerId']);
    exit;
}

$controller = new CareerSubjectsController();
$controller->listByCareer($_GET['careerId']);
