<?php
require_once __DIR__ . '/../../../../bootstrap/init.php';
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/sectionsController.php';

header('Content-Type: application/json');

try {
    $ctx = requireAuth();

    $controller = new SectionsController();
    $controller->list();

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
