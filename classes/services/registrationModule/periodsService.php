<?php

require_once __DIR__ . '/../../models/registrationModule/periodsModel.php';

class PeriodsService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ============================================
    // LIST
    // ============================================
    public function listPeriods() {
        return PeriodsModel::listPeriods($this->pdo);
    }

    // ============================================
    // CREATE (corregido con manejo de duplicados)
    // ============================================
    public function createPeriod($data) {

        if (!isset($data['code']) || !isset($data['startDate']) ||
            !isset($data['endDate']) || !isset($data['status'])) {

            return ['ok' => false, 'message' => 'Missing fields'];
        }

        try {

            $success = PeriodsModel::createPeriod($this->pdo, $data);

            return [
                'ok' => true,
                'message' => 'Período creado correctamente'
            ];
        }

        catch (PDOException $e) {

            //  DUPLICADO (error 1062 / SQLSTATE 23000)
            if ($e->getCode() == 23000) {
                return [
                    'ok' => false,
                    'message' => 'El código de período ya existe. No se permiten duplicados.'
                ];
            }

            return [
                'ok' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // UPDATE (corregido con duplicados)
    // ============================================
    public function updatePeriod($data) {

        if (!isset($data['id']) || !isset($data['code']) ||
            !isset($data['startDate']) || !isset($data['endDate'])) {

            return ['ok' => false, 'message' => 'Missing fields'];
        }

        try {

            $success = PeriodsModel::updatePeriod($this->pdo, $data);

            return [
                'ok' => true,
                'message' => 'Período actualizado correctamente'
            ];
        }

        catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                return [
                    'ok' => false,
                    'message' => 'El código de período ya existe. No se permiten duplicados.'
                ];
            }

            return [
                'ok' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // CHANGE STATUS (correcto)
    // ============================================
    public function changeStatus($id, $status) {

        $validStatuses = ['creado','abierto','cerrado','finalizado'];

        if (!in_array($status, $validStatuses)) {
            return ['ok' => false, 'message' => 'Estado inválido'];
        }

        $success = PeriodsModel::changeStatus($this->pdo, $id, $status);

        return [
            'ok' => $success,
            'message' => $success ? 'Estado actualizado correctamente' : 'Error updating status'
        ];
    }
}
