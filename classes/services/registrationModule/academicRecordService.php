<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/academicRecordModel.php';

class AcademicRecordService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Historial
    public function getRecordByStudent($studentId) {
        return [
            'ok' => true,
            'record' => AcademicRecordModel::getRecordByStudent($this->pdo, $studentId)
        ];
    }

    // Índice (calculado como UV aprobadas / UV totales)
    public function calculateIndex($studentId) {
        $data = AcademicRecordModel::calculateIndex($this->pdo, $studentId);

        if (!$data || $data['totalUv'] == 0) {
            return ['ok' => true, 'index' => 0];
        }

        $index = ($data['approvedUv'] / $data['totalUv']) * 100;

        return [
            'ok' => true,
            'index' => round($index, 2),
            'approvedUv' => (int)$data['approvedUv'],
            'totalUv' => (int)$data['totalUv']
        ];
    }
}
