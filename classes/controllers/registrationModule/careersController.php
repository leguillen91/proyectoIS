<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../services/registrationModule/careersService.php';

class CareersController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new CareersService($pdo);
    }

    public function list() {
        $data = $this->service->listCareers();
        echo json_encode(['ok' => true, 'careers' => $data]);
    }

    public function create($payload) {
        $result = $this->service->createCareer($payload);
        echo json_encode($result);
    }

    public function update($payload) {
        $result = $this->service->updateCareer($payload);
        echo json_encode($result);
    }
}
