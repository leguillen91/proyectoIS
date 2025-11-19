<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/enrollmentLogsService.php';

class EnrollmentLogsController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new EnrollmentLogsService($pdo);
    }

    public function save($payload) {
        $this->service->saveLog(
            $payload['studentId'],
            $payload['action'],
            $payload['details'] ?? null
        );

        echo json_encode(['ok' => true, 'message' => 'Log saved']);
    }

    public function logsByStudent($studentId) {
        echo json_encode(
            $this->service->logsByStudent($studentId)
        );
    }
}
