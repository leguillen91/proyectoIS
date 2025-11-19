<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class EnrollmentLogsModel {

    /* ============================================================
        GUARDAR LOG DE MATRÍCULA
        Almacena acciones del estudiante (inscribir, retirar, error, etc.)
    ============================================================ */
    public static function saveLog($pdo, $studentId, $action, $details) {
        $stmt = $pdo->prepare("
            INSERT INTO enrollmentlogs (studentId, action, details)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$studentId, $action, $details]);
    }

    /* ============================================================
        OBTENER TODOS LOS LOGS DE UN ESTUDIANTE
        Ordenados del más reciente al más antiguo
    ============================================================ */
    public static function logsByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM enrollmentlogs
            WHERE studentId = ?
            ORDER BY createdAt DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
