<?php
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/studentsController.php';

header("Content-Type: application/json");

try {
    $ctx = requireAuth();
    $controller = new StudentsController();

    $result = $controller->downloadCertificate($ctx['userId']);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
