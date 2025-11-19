<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/subjectPrerequisitesService.php';

class SubjectPrerequisitesController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new SubjectPrerequisitesService($pdo);
    }
    public function listAll() {
        $data = $this->service->listAll();
        echo json_encode(['ok' => true, 'list' => $data]);
    }
    public function listBySubject($subjectId) {
        $data = $this->service->listBySubject($subjectId);
        echo json_encode(['ok' => true, 'prerequisites' => $data]);
    }

    public function add($payload) {
        $result = $this->service->addPrerequisites($payload);
        echo json_encode($result);
    }

    public function remove($payload) {

        if (!isset($payload['subjectId']) || !isset($payload['prereqId'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
            return;
        }

        $result = $this->service->removePrerequisite(
            $payload['subjectId'],
            $payload['prereqId']
        );

        echo json_encode($result);
    }
}
