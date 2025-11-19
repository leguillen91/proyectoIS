<?php

require_once __DIR__ . '/../../../classes/models/registrationModule/sectionsModel.php';
require_once __DIR__ . '/../../../classes/models/registrationModule/subjectsModel.php';
require_once __DIR__ . '/../../../classes/models/registrationModule/periodsModel.php';

class SectionsService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ============================================================
       GENERADOR AUTOMÁTICO DEL CÓDIGO DE SECCIÓN
       Formato: SUBJECTCODE-PERIODSHORT-LETTER
       Ej: IS140-2025I-A
    ============================================================ */
    private function generateSectionCode($subjectId, $periodId) {

        // SUBJECT (Método estático)
        $subject = SubjectsModel::getById($this->pdo, $subjectId);
        if (!$subject) {
            throw new Exception("Subject not found");
        }

        // PERIODO (Método estático)
        $period = PeriodsModel::getById($this->pdo, $periodId);
        if (!$period) {
            throw new Exception("Period not found");
        }

        $subjectCode = $subject['code'];               // EJ: IS140
        $periodShort = str_replace("-", "", $period['code']); // EJ: 2025I

        // Secciones existentes con ese subject + periodo
        $existing = SectionsModel::listSectionCodes($this->pdo, $subjectId, $periodId);

        // Determinar la letra
        $nextLetter = 'A';
        if (!empty($existing)) {
            $letters = array_map(fn($s) => substr($s, -1), $existing);
            sort($letters);
            $last = end($letters);
            $nextLetter = chr(ord($last) + 1);
        }

        return "{$subjectCode}-{$periodShort}-{$nextLetter}";
    }

    /* ============================================================
        LISTAR TODAS LAS SECCIONES
    ============================================================ */
    public function listSections() {
        return SectionsModel::listSections($this->pdo);
    }

    public function getSectionById($id) {
        return SectionsModel::getSectionById($this->pdo, $id);
    }

    /* ============================================================
        CREAR SECCIÓN
    ============================================================ */
    public function createSection($data) {

        $required = ['periodId','subjectId','teacherId','classroomId','cupo'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        if (!is_numeric($data['cupo']) || $data['cupo'] <= 0) {
            return ['ok' => false, 'error' => 'Invalid cupo'];
        }

        // GENERAR CODIGO AUTOMÁTICAMENTE
        try {
            $data['sectionCode'] = $this->generateSectionCode(
                $data['subjectId'], 
                $data['periodId']
            );
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $success = SectionsModel::createSection($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Section created successfully' : 'Error creating section'
        ];
    }

    /* ============================================================
        ACTUALIZAR SECCIÓN
    ============================================================ */
    public function updateSection($data) {

        if (empty($data['id'])) {
            return ['ok' => false, 'error' => 'Missing id'];
        }

        $required = ['periodId','subjectId','teacherId','classroomId','cupo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['ok' => false, 'error' => "Missing field: $field"];
            }
        }

        if (!is_numeric($data['cupo']) || $data['cupo'] <= 0) {
            return ['ok' => false, 'error' => 'Invalid cupo'];
        }

        // Datos anteriores
        $old = SectionsModel::getSectionById($this->pdo, $data['id']);
        if (!$old) {
            return ['ok' => false, 'error' => 'Section not found'];
        }

        // Regenerar código solo si cambió subject o period
        if ($old['subjectId'] != $data['subjectId'] || $old['periodId'] != $data['periodId']) {
            $data['sectionCode'] = $this->generateSectionCode($data['subjectId'], $data['periodId']);
        } else {
            $data['sectionCode'] = $old['sectionCode'];
        }

        $success = SectionsModel::updateSection($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Section updated successfully' : 'Error updating section'
        ];
    }

    /* ============================================================
        ELIMINAR SECCIÓN
    ============================================================ */
    public function deleteSection($id) {
        $success = SectionsModel::deleteSection($this->pdo, $id);

        return [
            'ok' => $success,
            'message' => $success ? 'Section deleted successfully' : 'Error deleting section'
        ];
    }
}
