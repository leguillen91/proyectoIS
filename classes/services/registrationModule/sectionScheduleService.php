<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/sectionScheduleModel.php';

class SectionScheduleService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ============================================================
        LISTAR HORARIOS POR SECCIÓN
    ============================================================ */
    public function listBySection($sectionId) {
        return SectionScheduleModel::listBySection($this->pdo, $sectionId);
    }

    /* ============================================================
        AGREGAR HORARIO (DÍAS COMPACTOS: LuMaMi, LuMaMiJu, Sa)
    ============================================================ */
    public function addSchedule($data) {

        $required = ['sectionId','day','startTime','endTime'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        // VALIDACIÓN: hora inicio < hora fin
        if ($data['startTime'] >= $data['endTime']) {
            return ['ok' => false, 'error' => 'startTime must be earlier than endTime'];
        }

        // VALIDACIÓN: evitar duplicados exactos
        $existing = SectionScheduleModel::listBySection($this->pdo, $data['sectionId']);
        foreach ($existing as $sch) {
            if (
                $sch['day'] == $data['day'] &&
                $sch['startTime'] == $data['startTime'] &&
                $sch['endTime'] == $data['endTime']
            ) {
                return ['ok' => false, 'error' => 'Schedule already exists'];
            }
        }

        // CREAR REGISTRO
        $newId = SectionScheduleModel::addSchedule($this->pdo, $data);

        return [
            'ok' => true,
            'id' => $newId,
            'message' => 'Schedule added successfully'
        ];
    }

    /* ============================================================
        ELIMINAR HORARIO
    ============================================================ */
    public function removeSchedule($id) {
        $success = SectionScheduleModel::removeSchedule($this->pdo, $id);

        return [
            'ok' => $success,
            'message' => $success ? 'Schedule removed successfully' : 'Error removing schedule'
        ];
    }
}
