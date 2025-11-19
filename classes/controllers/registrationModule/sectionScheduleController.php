<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/services/registrationModule/sectionScheduleService.php';

class SectionScheduleController {

    private $service;

    public function __construct() {
        global $pdo;
        $this->service = new SectionScheduleService($pdo);
    }

    /* ============================================================
        LISTAR HORARIOS POR SECCIÓN
    ============================================================ */
    public function list($sectionId) {

        if (empty($sectionId) || !is_numeric($sectionId)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid sectionId']);
            return;
        }

        $data = $this->service->listBySection($sectionId);

        echo json_encode([
            'ok' => true,
            'schedules' => $data  // ← Consistente con tu frontend
        ]);
    }

    /* ============================================================
        AGREGAR HORARIO
    ============================================================ */
    public function add($payload) {

        try {
            $result = $this->service->addSchedule($payload);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /* ============================================================
        ELIMINAR HORARIO
    ============================================================ */
    public function remove($payload) {

        if (empty($payload['id'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing id']);
            return;
        }

        try {
            $result = $this->service->removeSchedule($payload['id']);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
