<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../services/registrationModule/periodsService.php';

class PeriodsController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new PeriodsService($pdo);
    }

    public function list() {
        $data = $this->service->listPeriods();
        echo json_encode(['ok' => true, 'periods' => $data]);
    }

    public function create($payload) {
        $result = $this->service->createPeriod($payload);
        echo json_encode($result);
    }

    public function update($payload) {
        $result = $this->service->updatePeriod($payload);
        echo json_encode($result);
    }

    public function changeStatus($payload) {
        if (!isset($payload['id']) || !isset($payload['status'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing fields']);
            return;
        }

        $result = $this->service->changeStatus(
            $payload['id'],
            $payload['status']
        );

        echo json_encode($result);
    }
}
