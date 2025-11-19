<?php

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/models/registrationModule/subjectPrerequisitesModel.php';

class SubjectPrerequisitesService {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Obtener prerrequisitos de una materia
    public function listBySubject($subjectId) {
        return SubjectPrerequisitesModel::listBySubject($this->pdo, $subjectId);
    }

    // Agregar múltiples prerrequisitos en un solo request
   public function addPrerequisites($data) {

    if (!isset($data['subjectId']) || !isset($data['selectedPrereqs'])) {
        return ['ok' => false, 'error' => 'Missing fields'];
    }

    $subjectId = $data['subjectId'];
    $selected  = $data['selectedPrereqs'];

    if (!is_array($selected)) {
        return ['ok' => false, 'error' => 'selectedPrereqs must be an array'];
    }

    $added = [];
    $duplicates = [];

    foreach ($selected as $prereqId) {

        if ($prereqId == $subjectId) {
            $duplicates[] = $prereqId;
            continue;
        }

        $result = SubjectPrerequisitesModel::addPrerequisite(
            $this->pdo,
            $subjectId,
            $prereqId
        );

        if ($result === 1) {
            $added[] = $prereqId;
        } else {
            $duplicates[] = $prereqId;
        }
    }

    // Si ninguno se agregó
    if (count($added) === 0) {
        return [
            'ok' => false,
            'error' => 'Los prerrequisitos seleccionados ya existen',
            'added' => [],
            'duplicates' => $duplicates
        ];
    }

    // Si algunos sí se agregaron
    return [
        'ok' => true,
        'message' => 'Prerrequisitos guardados correctamente',
        'added' => $added,
        'duplicates' => $duplicates
    ];
    }
    public function listAll() {
        return SubjectPrerequisitesModel::listAll($this->pdo);
    }

    // Eliminar un prerrequisito específico
    public function removePrerequisite($subjectId, $prereqId) {
        $success = SubjectPrerequisitesModel::removePrerequisite($this->pdo, $subjectId, $prereqId);

        return [
            'ok' => $success,
            'message' => $success ? 'Prerequisite removed' : 'Error removing prerequisite'
        ];
    }
}
