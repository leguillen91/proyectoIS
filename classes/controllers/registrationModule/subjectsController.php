<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../services/registrationModule/subjectsService.php';

class SubjectsController
{
    private $service;

    public function __construct()
    {
        global $pdo;
        $this->service = new SubjectsService($pdo);
    }

    /* ============================================================
       FUNCIONES ORIGINALES (NO SE TOCAN)
       ============================================================ */

    public function listDepartments()
    {
        $data = $this->service->listDepartments();
        echo json_encode(['ok' => true, 'departments' => $data]);
    }

    public function list()
    {
        $data = $this->service->listSubjects();
        echo json_encode(['ok' => true, 'subjects' => $data]);
    }

    public function create($payload)
    {
        $result = $this->service->createSubject($payload);
        echo json_encode($result);
    }

    public function update($payload)
    {
        $result = $this->service->updateSubject($payload);
        echo json_encode($result);
    }

    public function delete($payload)
    {
        if (!isset($payload['id'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing id']);
            return;
        }

        $result = $this->service->deleteSubject($payload['id']);
        echo json_encode($result);
    }

    /* ============================================================
       NUEVAS FUNCIONES AVANZADAS
       ============================================================ */

    /** 1. Materias sin carrera (huérfanas) */
    public function listOrphans()
    {
        $result = $this->service->listOrphans();
        echo json_encode($result);
    }

    /** 2. Carreras donde aparece la materia */
    public function listCareersBySubject($subjectId)
    {
        $result = $this->service->listCareersBySubject($subjectId);
        echo json_encode($result);
    }

    /** 3. Lista materias por carrera */
    public function listByCareer($careerId)
    {
        $result = $this->service->listSubjectsByCareer($careerId);
        echo json_encode($result);
    }

    /** 4. Lista prerequisitos por carrera */
    public function listPrereqsByCareer($subjectId, $careerId)
    {
        $result = $this->service->listPrereqsByCareer($subjectId, $careerId);
        echo json_encode($result);
    }

    /** 5. Buscador avanzado */
    public function search($keyword)
    {
        $result = $this->service->searchSubjects($keyword);
        echo json_encode($result);
    }

    /** 6. Mega consulta: Subjects + carreras + semestres + deptos */
    public function listAllWithCareerInfo()
    {
        $result = $this->service->listAllWithCareerInfo();
        echo json_encode($result);
    }
}
