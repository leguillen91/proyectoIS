<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/sectionsController.php';

header('Content-Type: application/json');

$ctx = requireAuth();
$id = $_GET['id'] ?? null;

$controller = new SectionsController();
$controller->get($id);
