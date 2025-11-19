<?php
include_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/classroomService.php';
class ClassroomController {

    private $service;

    public function __construct($pdo) {
        $this->service = new ClassroomService($pdo);
    }

    public function listAll() {
        return $this->service->listAll();
    }
}
