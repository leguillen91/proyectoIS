<?php

require_once __DIR__ . '/../../models/registrationModule/teachersModel.php';

class TeachersService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listTeachers()
    {
        return TeachersModel::listTeachers($this->pdo);
    }

}
