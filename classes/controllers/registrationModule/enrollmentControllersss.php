<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/enrollmentService.php';

class EnrollmentController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new EnrollmentService($pdo);
    }

    public function listAvailable($ctx) {
        echo json_encode(
            $this->service->listAvailable($ctx)
        );
    }

    public function enroll($ctx, $payload) {
        $payload['periodId'] = $ctx['currentPeriodId'];
        echo json_encode(
            $this->service->enroll($ctx['studentId'], $payload)
        );
    }

    public function bulkEnroll($ctx, $payload) {
        echo json_encode(
            $this->service->bulkEnroll($ctx['studentId'], $payload['sections'])
        );
    }

    public function withdraw($payload) {
        echo json_encode(
            $this->service->withdraw($payload['enrollmentId'])
        );
    }

    public function studentEnrollments($ctx) {
        echo json_encode(
            $this->service->studentEnrollments($ctx['studentId'], $ctx['currentPeriodId'])
        );
    }
}
