<?php

require_once __DIR__ . '/../../models/registrationModule/enrollmentModel.php';
require_once __DIR__ . '/../../models/registrationModule/subjectPrerequisitesModel.php';
require_once __DIR__ . '/../../models/studentsModel.php';

class EnrollmentService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // ============================================================
    // 1. LISTAR ASIGNATURAS INSCRITAS
    // ============================================================
    public function listEnrolled($studentId)
    {
        $periodId = EnrollmentModel::getActivePeriod($this->pdo);

        if (!$periodId) {
            return ['ok' => false, 'error' => 'No hay período activo.'];
        }

        $data = EnrollmentModel::listEnrolled($this->pdo, $studentId, $periodId);
        return ['ok' => true, 'enrolled' => $data];
    }

    // ============================================================
    // 2. LISTAR OFERTA DISPONIBLE PARA EL ESTUDIANTE
    // ============================================================
    public function listAvailable($studentId, $careerId)
    {
        $periodId = EnrollmentModel::getActivePeriod($this->pdo);

        if (!$periodId) {
            return ['ok' => false, 'error' => 'No hay período activo.'];
        }

        $data = EnrollmentModel::listAvailable($this->pdo, $careerId, $periodId);
        return ['ok' => true, 'available' => $data];
    }

    // ============================================================
    // 3. INSCRIBIR UNA SECCIÓN (VALIDACIÓN COMPLETA)
    // ============================================================
    public function enroll($studentId, $sectionId)
    {
        $periodId = EnrollmentModel::getActivePeriod($this->pdo);
        if (!$periodId) return ['ok' => false, 'error' => 'No hay período activo.'];

        // 3.1 validar turno
        if (!$this->isEnrollmentOpenForStudent($studentId, $periodId)) {
            return ['ok' => false, 'error' => 'Aún no es su turno de matrícula.'];
        }

        // 3.2 validar duplicado
        if (EnrollmentModel::isAlreadyEnrolled($this->pdo, $studentId, $sectionId)) {
            return ['ok' => false, 'error' => 'Ya está inscrito en esta sección.'];
        }

        // 3.3 cupo
        if (!$this->hasCapacity($sectionId)) {
            return ['ok' => false, 'error' => 'La sección está llena.'];
        }

        // 3.4 UV
        if (!$this->validateUV($studentId, $sectionId, $periodId)) {
            return ['ok' => false, 'error' => 'Excede el máximo de UV permitidas.'];
        }

        // 3.5 prerrequisitos
        if (!$this->validatePrerequisites($studentId, $sectionId)) {
            return ['ok' => false, 'error' => 'No cumple prerrequisitos para esta asignatura.'];
        }

        // 3.6 colisión horario
        if ($this->hasScheduleConflict($studentId, $sectionId, $periodId)) {
            return ['ok' => false, 'error' => 'Conflicto de horario con otra clase inscrita.'];
        }

        // 3.7 insertar
        $ok = EnrollmentModel::addEnrollment($this->pdo, $studentId, $sectionId, $periodId);

        if ($ok) {
            $this->log($studentId, "INSCRIPCIÓN", "Sección ID $sectionId");
            return ['ok' => true, 'message' => 'Inscripción exitosa.'];
        }

        return ['ok' => false, 'error' => 'Error al inscribir.'];
    }

    // ============================================================
    // 4. RETIRAR MATRÍCULA
    // ============================================================
    public function removeEnrollment($studentId, $sectionId)
    {
        $ok = EnrollmentModel::removeEnrollment($this->pdo, $studentId, $sectionId);

        if ($ok) {
            $this->log($studentId, "RETIRO", "Sección ID $sectionId");
            return ['ok' => true, 'message' => 'Asignatura retirada.'];
        }

        return ['ok' => false, 'error' => 'No se pudo retirar la asignatura.'];
    }

    // ============================================================
    // VALIDACIONES INTERNAS
    // ============================================================

    private function isEnrollmentOpenForStudent($studentId, $periodId)
    {
        $stmt = $this->pdo->prepare("
            SELECT academicIndex FROM students WHERE id = ?
        ");
        $stmt->execute([$studentId]);
        $index = $stmt->fetchColumn();

        $stmt2 = $this->pdo->prepare("
            SELECT 1 
            FROM enrollmentcalendar
            WHERE periodId = ?
              AND ? BETWEEN minIndex AND maxIndex
              AND CURRENT_DATE BETWEEN startDate AND endDate
        ");

        $stmt2->execute([$periodId, $index]);

        return $stmt2->fetchColumn() ? true : false;
    }

    private function hasCapacity($sectionId)
    {
        $stmt = $this->pdo->prepare("
            SELECT sec.cupo - COUNT(e.id) AS available
            FROM sections sec
            LEFT JOIN enrollment e ON e.sectionId = sec.id
            WHERE sec.id = ?
        ");
        $stmt->execute([$sectionId]);
        return $stmt->fetchColumn() > 0;
    }

    private function validateUV($studentId, $sectionId, $periodId)
    {
        $currentUV = EnrollmentModel::enrolledUV($this->pdo, $studentId, $periodId);

        $stmt = $this->pdo->prepare("
            SELECT sub.uv 
            FROM sections sec
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            WHERE sec.id = ?
        ");
        $stmt->execute([$sectionId]);
        $uv = $stmt->fetchColumn();

        $maxUV = 12;
        $idx = $this->getIndex($studentId);
        if ($idx >= 80) $maxUV = 16;

        return ($currentUV + $uv) <= $maxUV;
    }

    private function getIndex($studentId)
    {
        $stmt = $this->pdo->prepare("SELECT academicIndex FROM students WHERE id=?");
        $stmt->execute([$studentId]);
        return $stmt->fetchColumn() ?: 0;
    }

    private function validatePrerequisites($studentId, $sectionId)
    {
        $stmt = $this->pdo->prepare("SELECT subjectId FROM sections WHERE id = ?");
        $stmt->execute([$sectionId]);
        $subjectId = $stmt->fetchColumn();

        $pr = EnrollmentModel::getSubjectPrereqs($this->pdo, $subjectId);

        if (empty($pr)) return true;

        foreach ($pr as $req) {
            $stmt2 = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM studentgrades
                WHERE studentId = ? 
                  AND subjectId = ? 
                  AND status = 'Aprobado'
            ");
            $stmt2->execute([$studentId, $req]);

            if ($stmt2->fetchColumn() == 0) return false;
        }

        return true;
    }

    private function hasScheduleConflict($studentId, $newSectionId, $periodId)
    {
        $newSchedule = EnrollmentModel::getSectionSchedule($this->pdo, $newSectionId);
        $currentSchedule = EnrollmentModel::listSchedule($this->pdo, $studentId, $periodId);

        foreach ($newSchedule as $n) {
            foreach ($currentSchedule as $c) {

                if ($n['day'] !== $c['day']) continue;

                if (
                    ($n['startTime'] < $c['endTime']) &&
                    ($n['endTime'] > $c['startTime'])
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    // ============================================================
    // LOG DE MATRÍCULA
    // ============================================================
    private function log($studentId, $action, $details)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO enrollmentlogs (studentId, action, details)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$studentId, $action, $details]);
    }
}
