<?php

require_once __DIR__ . '/../../models/registrationModule/careerSubjectsModel.php';

class CareerSubjectsService
{
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listByCareer($careerId) {
        return CareerSubjectsModel::listByCareer($this->pdo, $careerId);
    }

    public function addSubject($data) {

        if (!isset($data['careerId'], $data['subjectId'], $data['periodNumber'])) {
            return ['ok' => false, 'error' => 'Missing fields'];
        }

        $success = CareerSubjectsModel::addSubjectToCareer(
            $this->pdo,
            $data['careerId'],
            $data['subjectId'],
            $data['periodNumber']
        );

        return [
            'ok' => $success,
            'message' => $success ? 'Materia agregada al período' : 'Error agregando materia'
        ];
    }

    public function removeSubject($data) {

        if (!isset($data['id'])) {
            return ['ok' => false, 'error' => 'Missing id'];
        }

        $success = CareerSubjectsModel::removeById($this->pdo, $data['id']);

        return [
            'ok' => $success,
            'message' => $success ? 'Materia eliminada' : 'Error eliminando'
        ];
    }
}
