<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/enrollmentCalendarService.php';

class EnrollmentCalendarController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new EnrollmentCalendarService($pdo);
    }

    public function list($periodId) {
        echo json_encode(
            $this->service->listCalendar($periodId)
        );
    }

    public function create($payload) {
        echo json_encode(
            $this->service->createCalendarRange($payload)
        );
    }

    public function update($payload) {
        echo json_encode(
            $this->service->updateCalendarRange($payload)
        );
    }
}
