<?php

require_once __DIR__ . '/../../models/registrationModule/subjectsModel.php';
require_once __DIR__ . '/../../models/registrationModule/subjectsModelAdvanced.php';

class SubjectsService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /* ============================================================
       CRUD ORIGINAL (SIN CAMBIOS)
       ============================================================ */

    public function listSubjects()
    {
        return SubjectsModel::listSubjects($this->pdo);
    }

    public function listDepartments()
    {
        return SubjectsModel::listDepartments($this->pdo);
    }

    public function createSubject($data)
    {
        if (!isset($data['code']) || !isset($data['name']) ||
            !isset($data['uv']) || !isset($data['departmentId'])) {
            return ['ok' => false, 'error' => 'Campos incompletos'];
        }

        if (!is_numeric($data['uv']) || $data['uv'] <= 0) {
            return ['ok' => false, 'error' => 'UV inválido'];
        }

        $success = SubjectsModel::createSubject($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Materia creada correctamente' : 'Error al crear la materia'
        ];
    }

    public function updateSubject($data)
    {
        if (!isset($data['id']) || !isset($data['code']) || !isset($data['name']) ||
            !isset($data['uv']) || !isset($data['departmentId'])) {
            return ['ok' => false, 'error' => 'Campos incompletos'];
        }

        $success = SubjectsModel::updateSubject($this->pdo, $data);

        return [
            'ok' => $success,
            'message' => $success ? 'Materia actualizada correctamente' : 'Error al actualizar la materia'
        ];
    }

    public function deleteSubject($id)
    {
        $success = SubjectsModel::deleteSubject($this->pdo, $id);

        return [
            'ok' => $success,
            'message' => $success ? 'Materia eliminada correctamente' : 'Error al eliminar la materia'
        ];
    }

    /* ============================================================
       FUNCIONES AVANZADAS (NUEVAS)
       ============================================================ */

    /** 1. Materias sin carrera asignada */
    public function listOrphans()
    {
        $data = SubjectsModelAdvanced::listOrphans($this->pdo);
        return ['ok' => true, 'subjects' => $data];
    }

    /** 2. Carreras donde aparece una materia */
    public function listCareersBySubject($subjectId)
    {
        if (!$subjectId) {
            return ['ok' => false, 'error' => 'subjectId requerido'];
        }

        $data = SubjectsModelAdvanced::listCareersBySubject($this->pdo, $subjectId);
        return ['ok' => true, 'careers' => $data];
    }

    /** 3. Materias pertenecientes a una carrera */
    public function listSubjectsByCareer($careerId)
    {
        if (!$careerId) {
            return ['ok' => false, 'error' => 'careerId requerido'];
        }

        $data = SubjectsModelAdvanced::listByCareer($this->pdo, $careerId);
        return ['ok' => true, 'subjects' => $data];
    }

    /** 4. Prerequisitos por carrera */
    public function listPrereqsByCareer($subjectId, $careerId)
    {
        if (!$subjectId || !$careerId) {
            return ['ok' => false, 'error' => 'subjectId y careerId requeridos'];
        }

        $data = SubjectsModelAdvanced::listPrereqsByCareer(
            $this->pdo,
            $subjectId,
            $careerId
        );

        return ['ok' => true, 'prerequisites' => $data];
    }

    /** 5. Búsqueda avanzada */
    public function searchSubjects($keyword)
    {
        if (!$keyword) {
            return ['ok' => false, 'error' => 'keyword requerido'];
        }

        $data = SubjectsModelAdvanced::search($this->pdo, $keyword);
        return ['ok' => true, 'subjects' => $data];
    }

    /** 6. Mega consulta para Subjects Master Panel */
    public function listAllWithCareerInfo()
    {
        $data = SubjectsModelAdvanced::listAllWithCareerInfo($this->pdo);

        return [
            'ok' => true,
            'subjects' => $data
        ];
    }
}
