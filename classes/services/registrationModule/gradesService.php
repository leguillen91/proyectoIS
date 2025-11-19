<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/gradesModel.php';

class GradesService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Para el docente
    public function getGradesBySection($sectionId) {
        return [
            'ok' => true,
            'grades' => GradesModel::getGradesBySection($this->pdo, $sectionId)
        ];
    }

    // Para el estudiante
    public function getGradesByStudent($studentId) {
        return [
            'ok' => true,
            'grades' => GradesModel::getGradesByStudent($this->pdo, $studentId)
        ];
    }

    // Docente asigna / actualiza
    public function assignGrade($payload) {
        $required = ['studentId','sectionId','grade','status'];

        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        if ($payload['grade'] < 0 || $payload['grade'] > 100) {
            return ['ok' => false, 'error' => 'Invalid grade (0-100)'];
        }

        $success = GradesModel::assignGrade(
            $this->pdo,
            $payload['studentId'],
            $payload['sectionId'],
            $payload['grade'],
            $payload['status']
        );

        return [
            'ok' => $success,
            'message' => $success ? 'Grade updated successfully' : 'Error updating grade'
        ];
    }
}
