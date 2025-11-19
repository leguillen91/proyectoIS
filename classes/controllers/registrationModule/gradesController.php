<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/gradesService.php';

class GradesController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new GradesService($pdo);
    }

    public function getBySection($sectionId) {
        echo json_encode(
            $this->service->getGradesBySection($sectionId)
        );
    }

    public function getByStudent($studentId) {
        echo json_encode(
            $this->service->getGradesByStudent($studentId)
        );
    }

    public function assign($payload) {
        echo json_encode(
            $this->service->assignGrade($payload)
        );
    }
}
