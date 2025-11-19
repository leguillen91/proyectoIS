<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../services/registrationModule/careerSubjectsService.php';

class CareerSubjectsController
{
    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new CareerSubjectsService($pdo);
    }

    public function listByCareer($careerId) {
        $data = $this->service->listByCareer($careerId);
        echo json_encode(['ok' => true, 'plan' => $data]);
    }

    public function add($payload) {
        $result = $this->service->addSubject($payload);
        echo json_encode($result);
    }

    public function remove($payload) {
        $result = $this->service->removeSubject($payload);
        echo json_encode($result);
    }
}
