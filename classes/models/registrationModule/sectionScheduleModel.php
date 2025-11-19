<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class SectionScheduleModel {

    /* ============================================================
        LISTAR HORARIOS DE UNA SECCIÓN
        Retorna todos los bloques horarios asignados a la sección.
    ============================================================ */
    public static function listBySection(PDO $pdo, int $sectionId): array {

        $stmt = $pdo->prepare("
            SELECT 
                id, 
                sectionId, 
                day, 
                startTime, 
                endTime
            FROM sectionschedule
            WHERE sectionId = ?
            ORDER BY startTime ASC
        ");

        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        AGREGAR HORARIO A UNA SECCIÓN
        'day' puede ser formato compacto: (LuMaMi, LuMaMiJuVi...)
    ============================================================ */
    public static function addSchedule(PDO $pdo, array $data): int {

        $stmt = $pdo->prepare("
            INSERT INTO sectionschedule (sectionId, day, startTime, endTime)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['sectionId'],
            $data['day'],        // ejemplo: "LuMaMi"
            $data['startTime'],  // "08:00"
            $data['endTime']     // "10:00"
        ]);

        return (int)$pdo->lastInsertId();
    }

    /* ============================================================
        ELIMINAR UN HORARIO POR ID
        Utilizado al editar o remover horarios de una sección.
    ============================================================ */
    public static function removeSchedule(PDO $pdo, int $id): bool {

        $stmt = $pdo->prepare("
            DELETE FROM sectionschedule 
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}
