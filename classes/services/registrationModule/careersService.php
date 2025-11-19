<?php

require_once __DIR__ . '/../../models/registrationModule/careersModel.php';

class CareersService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listCareers() {
        return CareersModel::listCareers($this->pdo);
    }

    public function createCareer($data) {

        if (!isset($data['name'], $data['departmentId'], $data['totalPeriods'])) {
            return ['ok' => false, 'error' => 'Missing fields'];
        }

        $success = CareersModel::createCareer($this->pdo, $data);
        return [
            'ok' => $success,
            'message' => $success ? 'Carrera creada correctamente' : 'Error creando carrera'
        ];
    }

    public function updateCareer($data) {

        if (!isset($data['id'], $data['name'], $data['departmentId'], $data['totalPeriods'])) {
            return ['ok' => false, 'error' => 'Missing fields'];
        }

        $success = CareersModel::updateCareer($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Carrera actualizada' : 'Error actualizando carrera'
        ];
    }
}
