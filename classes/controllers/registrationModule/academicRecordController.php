<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/academicRecordService.php';

class AcademicRecordController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new AcademicRecordService($pdo);
    }

    public function getRecordByStudent($ctx) {
        echo json_encode(
            $this->service->getRecordByStudent($ctx['studentId'])
        );
    }

    public function calculateIndex($ctx) {
        echo json_encode(
            $this->service->calculateIndex($ctx['studentId'])
        );
    }
}
