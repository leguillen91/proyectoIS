<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/sectionsService.php';

class SectionsController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new SectionsService($pdo);
    }

    public function list() {
        echo json_encode([
            'ok' => true,
            'sections' => $this->service->listSections()
        ]);
    }

    public function get($id) {
        echo json_encode([
            'ok' => true,
            'section' => $this->service->getSectionById($id)
        ]);
    }

    public function create($payload) {
        echo json_encode($this->service->createSection($payload));
    }

    public function update($payload) {
        echo json_encode($this->service->updateSection($payload));
    }

    public function delete($payload) {
        if (empty($payload['id'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing id']);
            return;
        }

        echo json_encode($this->service->deleteSection($payload['id']));
    }
}
