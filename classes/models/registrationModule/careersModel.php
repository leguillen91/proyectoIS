<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class CareersModel {

    /* ============================================================
        LISTAR TODAS LAS CARRERAS
        Incluye su departamento y total de períodos/semestres.
    ============================================================ */
    public static function listCareers($pdo) {
        $stmt = $pdo->query("
            SELECT 
                id, 
                name, 
                departmentId, 
                totalPeriods
            FROM careers
            ORDER BY name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        CREAR UNA NUEVA CARRERA
    ============================================================ */
    public static function createCareer($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO careers (name, departmentId, totalPeriods)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([
            $data['name'],
            $data['departmentId'],
            $data['totalPeriods']
        ]);
    }

    /* ============================================================
        ACTUALIZAR INFORMACIÓN DE UNA CARRERA
    ============================================================ */
    public static function updateCareer($pdo, $data) {
        $stmt = $pdo->prepare("
            UPDATE careers
            SET name = ?, departmentId = ?, totalPeriods = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['departmentId'],
            $data['totalPeriods'],
            $data['id']
        ]);
    }
}
