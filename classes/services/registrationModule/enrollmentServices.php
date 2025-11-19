<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/enrollmentModel.php';
require_once __DIR__ . '/../../../classes/models/registrationModule/sectionsModel.php';
require_once __DIR__ . '/../../../classes/models/registrationModule/subjectsModel.php';

class EnrollmentService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // LISTA DE CLASES DISPONIBLES SEGÚN CARRERA
    public function listAvailable($studentCtx) {
        $career = $studentCtx['career'];
        $periodId = $studentCtx['currentPeriodId'];

        $data = EnrollmentModel::listAvailableSubjects(
            $this->pdo,
            $career,
            $periodId
        );

        return ['ok' => true, 'availableSubjects' => $data];
    }

    // VALIDAR PREREQUISITOS
    private function validatePrerequisites($studentId, $subjectId) {
        $sql = "
            SELECT prereqId
            FROM subjectPrerequisites
            WHERE subjectId = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$subjectId]);
        $required = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($required)) return true;

        // Historial del estudiante
        $sql2 = "
            SELECT subjectId
            FROM studentAcademicRecord
            WHERE studentId = ?
        ";
        $stmt = $this->pdo->prepare($sql2);
        $stmt->execute([$studentId]);
        $approved = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($required as $req) {
            if (!in_array($req, $approved)) {
                return false;
            }
        }

        return true;
    }

    // VALIDAR CHOQUES DE HORARIO
    private function validateScheduleConflicts($studentId, $section) {
        $periodId = $section['periodId'];
        $sectionSchedule = EnrollmentModel::getSectionSchedule($this->pdo, $section['sectionId']);
        $studentSchedule = EnrollmentModel::getStudentSchedule($this->pdo, $studentId, $periodId);

        foreach ($sectionSchedule as $new) {
            foreach ($studentSchedule as $old) {
                if ($new['day'] === $old['day']) {
                    if (
                        ($new['startTime'] < $old['endTime']) &&
                        ($new['endTime'] > $old['startTime'])
                    ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // MATRICULAR UNA CLASE
    public function enroll($studentId, $section) {

        // Validar prerequisitos
        if (!$this->validatePrerequisites($studentId, $section['subjectId'])) {
            return ['ok' => false, 'error' => 'Prerequisites not met'];
        }

        // Validar choques
        if (!$this->validateScheduleConflicts($studentId, $section)) {
            return ['ok' => false, 'error' => 'Schedule conflict'];
        }

        // Registrar matrícula
        $success = EnrollmentModel::enroll($this->pdo, $studentId, $section);

        return [
            'ok' => $success,
            'message' => $success ? 'Enrollment successful' : 'Error during enrollment'
        ];
    }

    // BULK ENROLL
    public function bulkEnroll($studentId, $sections) {
        $results = [];

        foreach ($sections as $sec) {
            $results[] = $this->enroll($studentId, $sec);
        }

        return ['ok' => true, 'results' => $results];
    }

    // RETIRAR ASIGNATURA
    public function withdraw($enrollmentId) {
        $success = EnrollmentModel::withdraw($this->pdo, $enrollmentId);

        return [
            'ok' => $success,
            'message' => $success ? 'Class withdrawn' : 'Error withdrawing class'
        ];
    }

    // LISTADO DEL ESTUDIANTE
    public function studentEnrollments($studentId, $periodId) {
        $data = EnrollmentModel::studentEnrollments($this->pdo, $studentId, $periodId);
        return ['ok' => true, 'enrollments' => $data];
    }
}
