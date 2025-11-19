<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/enrollmentLogsModel.php';

class EnrollmentLogsService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function saveLog($studentId, $action, $details = null) {
        return EnrollmentLogsModel::saveLog($this->pdo, $studentId, $action, $details);
    }

    public function logsByStudent($studentId) {
        return [
            'ok' => true,
            'logs' => EnrollmentLogsModel::logsByStudent($this->pdo, $studentId)
        ];
    }
}
