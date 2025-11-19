<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/subjectPrerequisitesController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$subjectId = $_GET['subjectId'] ?? null;

$controller = new SubjectPrerequisitesController();
$controller->listBySubject($subjectId);
