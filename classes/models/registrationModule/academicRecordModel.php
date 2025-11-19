<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class AcademicRecordModel {

    /* ============================================================
        OBTENER HISTORIAL ACADÉMICO COMPLETO DEL ESTUDIANTE
        Incluye nombre de asignatura y código del período.
    ============================================================ */
    public static function getRecordByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                ar.*, 
                sub.code AS subjectCode, 
                sub.name AS subjectName,
                p.code AS periodCode
            FROM studentacademicrecord ar
            INNER JOIN subjects sub ON ar.subjectId = sub.id
            INNER JOIN periods p ON ar.periodId = p.id
            WHERE ar.studentId = ?
            ORDER BY p.startDate ASC
        ");

        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        CALCULAR ÍNDICE ACADÉMICO
        totalUv = UV inscritas
        approvedUv = UV aprobadas (nota >= 65)
    ============================================================ */
    public static function calculateIndex($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(uv) AS totalUv,
                SUM(
                    CASE 
                        WHEN grade >= 65 THEN uv 
                        ELSE 0 
                    END
                ) AS approvedUv
            FROM studentacademicrecord
            WHERE studentId = ?
        ");

        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
