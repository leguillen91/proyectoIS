<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/enrollmentCalendarModel.php';

class EnrollmentCalendarService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listCalendar($periodId) {
        return [
            'ok' => true,
            'calendar' => EnrollmentCalendarModel::listCalendar($this->pdo, $periodId)
        ];
    }

    public function createCalendarRange($data) {
        $required = ['periodId','minIndex','maxIndex','startDate','endDate'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        if ($data['minIndex'] < $data['maxIndex']) {
            return ['ok' => false, 'error' => 'minIndex must be >= maxIndex'];
        }

        $success = EnrollmentCalendarModel::createCalendarRange($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Calendar range created' : 'Error creating calendar range'
        ];
    }

    public function updateCalendarRange($data) {

        $required = ['id','minIndex','maxIndex','startDate','endDate'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        if ($data['minIndex'] < $data['maxIndex']) {
            return ['ok' => false, 'error' => 'minIndex must be >= maxIndex'];
        }

        $success = EnrollmentCalendarModel::updateCalendarRange($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Calendar range updated' : 'Error updating calendar range'
        ];
    }
}
