<?php
require_once __DIR__ . '/../../../../bootstrap/init.php';
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/sectionScheduleController.php';

header('Content-Type: application/json');

try {
    $ctx = requireAuth();

    $sectionId = $_GET['sectionId'] ?? null;

    $controller = new SectionScheduleController();
    $controller->list($sectionId);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
