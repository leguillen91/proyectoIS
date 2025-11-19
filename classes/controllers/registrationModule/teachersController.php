<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../services/registrationModule/teachersService.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

class TeachersController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new TeachersService($pdo);
    }

    public function list()
        {
            $list = $this->service->listTeachers();

            return [
                'ok' => true,
                'teachers' => $list
            ];
        }

}
