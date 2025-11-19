<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class EnrollmentCalendarModel {

    /* ============================================================
        LISTAR EL CALENDARIO DE MATRÍCULA POR PERÍODO
        Muestra rangos del índice y fechas asignadas.
    ============================================================ */
    public static function listCalendar($pdo, $periodId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM enrollmentcalendar
            WHERE periodId = ?
            ORDER BY minIndex DESC
        ");

        $stmt->execute([$periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        CREAR UN NUEVO RANGO DEL CALENDARIO
        Incluye índice mínimo, máximo y fechas habilitadas.
    ============================================================ */
    public static function createCalendarRange($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO enrollmentcalendar (periodId, minIndex, maxIndex, startDate, endDate)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['periodId'],
            $data['minIndex'],
            $data['maxIndex'],
            $data['startDate'],
            $data['endDate']
        ]);
    }

    /* ============================================================
        ACTUALIZAR UN RANGO EXISTENTE DEL CALENDARIO
    ============================================================ */
    public static function updateCalendarRange($pdo, $data) {
        $stmt = $pdo->prepare("
            UPDATE enrollmentcalendar
            SET minIndex = ?, maxIndex = ?, startDate = ?, endDate = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['minIndex'],
            $data['maxIndex'],
            $data['startDate'],
            $data['endDate'],
            $data['id']
        ]);
    }
}
