<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/subjectsController.php';

header("Content-Type: application/json");

$ctx = requireAuth();

$subjectId = $_GET['subjectId'] ?? null;
$careerId  = $_GET['careerId']  ?? null;

$controller = new SubjectsController();
$controller->listPrereqsByCareer($subjectId, $careerId);
