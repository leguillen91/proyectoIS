<?php

require_once __DIR__ . '/../services/studentsService.php';

class StudentsController {

    private $service;

    public function __construct() {
        $this->service = new StudentsService();
    }

    // ============================================================
    // DASHBOARD
    // ============================================================
    public function getDashboard($userId) {
        return $this->service->getDashboard($userId);
    }

    // ============================================================
    // HISTORIAL ACADÉMICO
    // ============================================================
    public function getAcademicRecord($userId) {
        return $this->service->getAcademicRecord($userId);
    }

    // ============================================================
    // CERTIFICADO ACADÉMICO
    // ============================================================
    public function downloadCertificate($userId) {
        return $this->service->downloadCertificate($userId);
    }

    // ============================================================
    // CONTACTOS — CRUD COMPLETO
    // ============================================================
    public function searchStudent($term, $userId) {
        return $this->service->searchStudent($term, $userId);
    }

    public function sendContactRequest($userId, $receiverId) {
        return $this->service->sendContactRequest($userId, $receiverId);
    }

    public function cancelContactRequest($userId, $requestId) {
        return $this->service->cancelContactRequest($userId, $requestId);
    }

    public function respondContactRequest($userId, $requestId, $action) {
        return $this->service->respondContactRequest($userId, $requestId, $action);
    }

    public function getContacts($userId) {
        return $this->service->getContacts($userId);
    }

    public function deleteContact($userId, $contactId) {
        return $this->service->deleteContact($userId, $contactId);
    }

    public function listSentRequests($userId) {
        return $this->service->listSentRequests($userId);
    }

    public function getContactRequests($userId) {
        return $this->service->getContactRequests($userId);
    }

    // ============================================================
    // CHAT
    // ============================================================
    public function sendMessage($userId, $data) {
        return $this->service->sendMessage($userId, $data);
    }

    public function getMessages($userId, $contactId) {
        return $this->service->getMessages($userId, $contactId);
    }

    public function setOnlineStatus($userId, $data) {
        return $this->service->setOnlineStatus($userId, $data);
    }

    // ============================================================
    // EVALUACIONES
    // ============================================================
    public function getGrades($userId) {
        return $this->service->getGrades($userId);
    }

    public function submitEvaluation($userId, $data) {
        return $this->service->submitEvaluation($userId, $data);
    }

    // ============================================================
    // SOLICITUDES ADMINISTRATIVAS
    // ============================================================
    public function createRequest($userId, $data) {
        return $this->service->createRequest($userId, $data);
    }

    public function listRequests($userId) {
        return $this->service->listRequests($userId);
    }
}
