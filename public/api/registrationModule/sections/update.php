<?php
require_once __DIR__ . '/../../../../bootstrap/init.php';
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/sectionsController.php';

header('Content-Type: application/json');

try {
    $ctx = requireAuth();

    $payload = json_decode(file_get_contents("php://input"), true);

    if (!$payload) {
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    $controller = new SectionsController();
    $controller->update($payload);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
