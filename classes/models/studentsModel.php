<?php

require_once __DIR__ . '/../../bootstrap/init.php';

class StudentsModel {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // =====================================================
    //  UTILIDADES BÁSICAS
    // =====================================================
    
    public function getStudentByUserId($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM students 
            WHERE userId = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =====================================================
    //  BÚSQUEDA DE ESTUDIANTES (CON RELACIÓN)
    // =====================================================
    public function searchStudent($term, $myId) {

        $sql = "
            SELECT id, fullName, enrollmentCode, career
            FROM students
            WHERE (fullName LIKE ? OR enrollmentCode LIKE ?)
              AND id != ?
            LIMIT 20
        ";

        $stmt = $this->pdo->prepare($sql);
        $like = "%$term%";
        $stmt->execute([$like, $like, $myId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];

        foreach ($students as $s) {

            $sid = $s['id'];

            // 1) ¿Ya son contactos?
            $stmt = $this->pdo->prepare("
                SELECT 1
                FROM studentcontacts
                WHERE (studentId = ? AND contactId = ?)
                   OR (studentId = ? AND contactId = ?)
            ");
            $stmt->execute([$myId, $sid, $sid, $myId]);

            if ($stmt->fetchColumn()) {
                $results[] = array_merge($s, [
                    "relation" => "contact"
                ]);
                continue;
            }

            // 2) ¿Yo envié solicitud?
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM studentcontactrequests
                WHERE senderId = ? AND receiverId = ? AND status = 'pending'
            ");
            $stmt->execute([$myId, $sid]);

            $sent = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sent) {
                $results[] = array_merge($s, [
                    "relation" => "sent",
                    "requestId" => $sent['id']
                ]);
                continue;
            }

            // 3) ¿Él me envió solicitud?
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM studentcontactrequests
                WHERE senderId = ? AND receiverId = ? AND status = 'pending'
            ");
            $stmt->execute([$sid, $myId]);

            $received = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($received) {
                $results[] = array_merge($s, [
                    "relation" => "received",
                    "requestId" => $received['id']
                ]);
                continue;
            }

            // 4) Ninguna relación → puede agregar
            $results[] = array_merge($s, [
                "relation" => "none"
            ]);
        }

        return [
            "ok" => true,
            "results" => $results
        ];
    }

    // =====================================================
    //  CONTACTOS — CRUD COMPLETO
    // =====================================================

    public function findExistingRequest($senderId, $receiverId) {
        $stmt = $this->pdo->prepare("
            SELECT id, status
            FROM studentcontactrequests
            WHERE (senderId = ? AND receiverId = ?)
               OR (senderId = ? AND receiverId = ?)
            LIMIT 1
        ");
        $stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createContactRequest($senderId, $receiverId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO studentcontactrequests (senderId, receiverId)
            VALUES (?, ?)
        ");
        return $stmt->execute([$senderId, $receiverId]);
    }

    public function cancelContactRequest($requestId, $senderId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM studentcontactrequests
            WHERE id = ? AND senderId = ? AND status = 'pending'
        ");
        return $stmt->execute([$requestId, $senderId]);
    }

    public function respondContactRequest($receiverId, $requestId, $action) {

        $stmt = $this->pdo->prepare("
            SELECT * FROM studentcontactrequests WHERE id = ?
        ");
        $stmt->execute([$requestId]);

        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req) return ['ok'=>false, 'error'=>'Request not found'];
        if ($req['receiverId'] != $receiverId) return ['ok'=>false,'error'=>'Unauthorized'];

        // Actualizar estado
        $stmt = $this->pdo->prepare("
            UPDATE studentcontactrequests SET status = ? WHERE id = ?
        ");
        $stmt->execute([$action, $requestId]);

        if ($action === 'accepted') {

            // Insertar ambas direcciones en contactos
            $stmt = $this->pdo->prepare("
                INSERT INTO studentcontacts (studentId, contactId)
                VALUES (?, ?), (?, ?)
            ");

            $stmt->execute([
                $req['senderId'], $receiverId,
                $receiverId, $req['senderId']
            ]);
        }

        return ['ok'=>true];
    }

    public function deleteContact($studentId, $contactId) {

        // 1. Eliminar relación
        $stmt = $this->pdo->prepare("
            DELETE FROM studentcontacts
            WHERE (studentId = ? AND contactId = ?)
            OR (studentId = ? AND contactId = ?)
        ");
        $stmt->execute([$studentId, $contactId, $contactId, $studentId]);

        // 2. Marcar solicitud previa como deleted
        $stmt = $this->pdo->prepare("
            UPDATE studentcontactrequests
            SET status = 'deleted'
            WHERE (senderId = ? AND receiverId = ?)
            OR (senderId = ? AND receiverId = ?)
        ");
        $stmt->execute([$studentId, $contactId, $contactId, $studentId]);

        // 3. Eliminar mensajes entre ambos
        $this->deleteConversation($studentId, $contactId);

        return true;
    }

    public function getContacts($studentId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.contactId AS studentId,
                s.fullName,
                s.enrollmentCode
            FROM studentcontacts c
            JOIN students s ON s.id = c.contactId
            WHERE c.studentId = ?
            ORDER BY s.fullName ASC
        ");
        $stmt->execute([$studentId]);

        return [
            'ok' => true,
            'contacts' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function listSentRequests($studentId) {
        $sql = "
            SELECT 
                r.id AS requestId,
                s.id AS studentId,
                s.fullName,
                s.enrollmentCode,
                r.status,
                r.createdAt
            FROM studentcontactrequests r
            JOIN students s ON s.id = r.receiverId
            WHERE r.senderId = ?
              AND r.status = 'pending'
            ORDER BY r.createdAt DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId]);

        return [
            'ok' => true,
            'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function getContactRequests($receiverId) {
        $sql = "
            SELECT 
                r.id AS requestId,
                s.id AS studentId,
                s.fullName,
                s.enrollmentCode,
                r.createdAt
            FROM studentcontactrequests r
            JOIN students s ON s.id = r.senderId
            WHERE r.receiverId = ?
              AND r.status = 'pending'
            ORDER BY r.createdAt DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$receiverId]);

        return [
            'ok' => true,
            'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // =====================================================
    //  CHAT ENTRE CONTACTOS
    // =====================================================

    public function insertMessage($senderId, $data) {
        $receiverId = $data['receiverId'] ?? null;
        $message    = $data['message'] ?? null;

        if (!$receiverId || !$message) {
            return ['ok'=>false,'error'=>'Missing parameters'];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO studentmessages (senderId, receiverId, message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$senderId, $receiverId, $message]);

        return ['ok'=>true];
    }

    public function getMessages($studentId, $contactId) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM studentmessages
            WHERE (senderId = ? AND receiverId = ?)
               OR (senderId = ? AND receiverId = ?)
            ORDER BY sentAt ASC
        ");
        $stmt->execute([$studentId, $contactId, $contactId, $studentId]);

        return [
            'ok' => true,
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // =====================================================
    //  ONLINE STATUS
    // =====================================================

    public function setOnlineStatus($studentId, $isOnline) {
        $stmt = $this->pdo->prepare("
            INSERT INTO studentonlinestatus (studentId, isOnline)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE isOnline = VALUES(isOnline)
        ");
        $stmt->execute([$studentId, $isOnline]);

        return ['ok'=>true];
    }

    // =====================================================
    //  HISTORIAL / NOTAS / ADMIN SOLICITUDES
    // =====================================================

    public function getAcademicRecord($studentId) {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM academichistory
            WHERE studentId = ?
            ORDER BY period DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGrades($studentId) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM academichistory
            WHERE studentId = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertEvaluation($studentId, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO studentevaluations (studentId, teacherId, subjectCode, period, rating, comments)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            $data['teacherId'],
            $data['subjectCode'],
            $data['period'],
            $data['rating'],
            $data['comments'] ?? ''
        ]);

        return ['ok'=>true];
    }

    public function createRequest($studentId, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO studentrequests (studentId, type, reason, documentPath)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $studentId,
            $data['type'],
            $data['reason'],
            $data['documentPath'] ?? null
        ]);

        return ['ok'=>true];
    }

    public function getRequests($studentId) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM studentrequests
            WHERE studentId = ?
            ORDER BY createdAt DESC
        ");
        $stmt->execute([$studentId]);

        return [
            'ok' => true,
            'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function deleteConversation($studentId, $contactId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM studentmessages
            WHERE (senderId = ? AND receiverId = ?)
            OR (senderId = ? AND receiverId = ?)
        ");
        return $stmt->execute([$studentId, $contactId, $contactId, $studentId]);
    }

}
