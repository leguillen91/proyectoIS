<?php
require_once __DIR__ . '/../../../classes/models/registrationModule/classroomModel.php';
class ClassroomService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listAll() {

        $classrooms = ClassroomModel::listAll($this->pdo);

        return [
            'ok' => true,
            'classrooms' => $classrooms
        ];
    }
}
