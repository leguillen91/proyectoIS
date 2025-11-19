<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../services/registrationModule/enrollmentService.php';

class EnrollmentController
{
    private $service;

    public function __construct()
    {
        global $pdo;
        $this->service = new EnrollmentService($pdo);
    }

    // ============================================================
    // LISTAR ASIGNATURAS INSCRITAS DEL ESTUDIANTE
    // ============================================================
    public function list()
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];

        $res = $this->service->listEnrolled($studentId);
        echo json_encode($res);
    }

    // ============================================================
    // LISTAR OFERTA DISPONIBLE
    // ============================================================
    public function available()
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];
        $careerId = $ctx['careerId']; // tomado desde /auth/me.php

        if (!$careerId) {
            echo json_encode(['ok' => false, 'error' => 'Carrera no encontrada en sesión.']);
            return;
        }

        $res = $this->service->listAvailable($studentId, $careerId);
        echo json_encode($res);
    }

    // ============================================================
    // INSCRIBIR ASIGNATURA
    // ============================================================
    public function enroll($payload)
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];

        if (!isset($payload['sectionId'])) {
            echo json_encode(['ok' => false, 'error' => 'Falta sectionId']);
            return;
        }

        $res = $this->service->enroll($studentId, $payload['sectionId']);
        echo json_encode($res);
    }

    // ============================================================
    // RETIRAR ASIGNATURA
    // ============================================================
    public function remove($payload)
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];

        if (!isset($payload['sectionId'])) {
            echo json_encode(['ok' => false, 'error' => 'Falta sectionId']);
            return;
        }

        $res = $this->service->removeEnrollment($studentId, $payload['sectionId']);
        echo json_encode($res);
    }

    // ============================================================
    // HORARIO SEMANAL DEL ESTUDIANTE
    // ============================================================
    public function schedule()
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];

        $periodId = EnrollmentModel::getActivePeriod($GLOBALS['pdo']);

        if (!$periodId) {
            echo json_encode(['ok' => false, 'error' => 'No hay período activo.']);
            return;
        }

        $data = EnrollmentModel::listSchedule($GLOBALS['pdo'], $studentId, $periodId);
        echo json_encode(['ok' => true, 'schedule' => $data]);
    }

    // ============================================================
    // FORMA 03 (SOLO JSON — PDF SE GENERA APARTE)
    // ============================================================
    public function forma03()
    {
        $ctx = requireAuth();
        $studentId = $ctx['studentId'];

        $periodId = EnrollmentModel::getActivePeriod($GLOBALS['pdo']);

        if (!$periodId) {
            echo json_encode(['ok' => false, 'error' => 'No hay período activo.']);
            return;
        }

        $stmt = $GLOBALS['pdo']->prepare("
            SELECT * FROM view_forma03
            WHERE studentId = ? AND periodCode = (
                SELECT code FROM periods WHERE id = ?
            )
        ");

        $stmt->execute([$studentId, $periodId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'forma03' => $rows]);
    }
}
