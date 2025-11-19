<?php

require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/subjectPrerequisitesController.php';

header('Content-Type: application/json');

$ctx = requireAuth();

$controller = new SubjectPrerequisitesController();
$controller->listAll();
