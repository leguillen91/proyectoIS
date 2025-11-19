<?php
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../classes/controllers/registrationModule/classroomController.php';
header('Content-Type: application/json');

try {
    $ctx = requireAuth();  // Valida token

    $controller = new ClassroomController($pdo);
    $response = $controller->listAll();

    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
