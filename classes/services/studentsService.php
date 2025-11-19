<?php

require_once __DIR__ . '/../models/studentsModel.php';

class StudentsService {

    private $pdo;
    private $model;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->model = new StudentsModel();
    }

    // =====================================================
    //  DASHBOARD
    // =====================================================
    public function getDashboard($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false, 'error'=>'Student not found'];

        return [
            'ok' => true,
            'student' => $student
        ];
    }

    // =====================================================
    //  HISTORIAL ACADÉMICO
    // =====================================================
    public function getAcademicRecord($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false, 'error'=>'Student not found'];

        return [
            'ok'=>true,
            'academicRecord' => $this->model->getAcademicRecord($student['id'])
        ];
    }

    // =====================================================
    //  CERTIFICADO ACADÉMICO (PDF)
    // =====================================================
    public function downloadCertificate($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Student not found'];

        return [
            'ok' => true,
            'student' => $student,
            'record' => $this->model->getAcademicRecord($student['id'])
        ];
    }

    // =====================================================
    //  BUSCAR ESTUDIANTES
    // =====================================================
    public function searchStudent($term, $userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->searchStudent($term, $student['id']);
    }

    // =====================================================
    //  CONTACTOS — CRUD COMPLETO
    // =====================================================

    public function sendContactRequest($userId, $receiverId) {
        $sender = $this->model->getStudentByUserId($userId);
        if (!$sender) return ['ok'=>false,'error'=>'Invalid student'];

        $senderId = $sender['id'];

        // Verificar si ya existe solicitud o contacto
        $existing = $this->model->findExistingRequest($senderId, $receiverId);

        if ($existing) {
            if ($existing['status'] === 'pending') {
                return ['ok'=>false, 'error'=>'Request already exists'];
            }
            if ($existing['status'] === 'accepted') {
                return ['ok'=>false, 'error'=>'Already contacts'];
            }
        }

        // Crear solicitud
        $this->model->createContactRequest($senderId, $receiverId);

        return ['ok'=>true];
    }

    public function cancelContactRequest($userId, $requestId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        $senderId = $student['id'];

        $this->model->cancelContactRequest($requestId, $senderId);

        return ['ok'=>true];
    }

    public function respondContactRequest($userId, $requestId, $action) {
        $receiver = $this->model->getStudentByUserId($userId);
        if (!$receiver) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->respondContactRequest($receiver['id'], $requestId, $action);
    }

    public function getContacts($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->getContacts($student['id']);
    }

    public function deleteContact($userId, $contactId) {
            $student = $this->model->getStudentByUserId($userId);
            if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

            $this->model->deleteContact($student['id'], $contactId);

            return ['ok'=>true];
        }


    public function listSentRequests($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->listSentRequests($student['id']);
    }

    public function getContactRequests($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->getContactRequests($student['id']);
    }

    // =====================================================
    //  CHAT - MENSAJES
    // =====================================================

    public function sendMessage($userId, $data) {
        $sender = $this->model->getStudentByUserId($userId);
        if (!$sender) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->insertMessage($sender['id'], $data);
    }

    public function getMessages($userId, $contactId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->getMessages($student['id'], $contactId);
    }

    public function setOnlineStatus($userId, $data) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        $isOnline = $data['isOnline'] ?? 0;

        return $this->model->setOnlineStatus($student['id'], $isOnline);
    }

    // =====================================================
    //  EVALUACIONES — DOCENTES
    // =====================================================

    public function getGrades($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return [
            'ok'=>true,
            'grades' => $this->model->getGrades($student['id'])
        ];
    }

    public function submitEvaluation($userId, $data) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->insertEvaluation($student['id'], $data);
    }

    // =====================================================
    //  SOLICITUDES ADMINISTRATIVAS
    // =====================================================

    public function createRequest($userId, $data) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->createRequest($student['id'], $data);
    }

    public function listRequests($userId) {
        $student = $this->model->getStudentByUserId($userId);
        if (!$student) return ['ok'=>false,'error'=>'Invalid student'];

        return $this->model->getRequests($student['id']);
    }
}
