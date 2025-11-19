<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class PeriodsModel {

    /* ============================================================
        LISTAR TODOS LOS PERIODOS
        Usado en administración para ver los ciclos académicos.
    ============================================================ */
    public static function listPeriods(PDO $pdo) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM periods
            ORDER BY id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        OBTENER PERIODO POR ID
        Importante para validaciones y generación de códigos de sección.
    ============================================================ */
    public static function getById(PDO $pdo, int $id) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM periods
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        CREAR UN NUEVO PERIODO ACADÉMICO
    ============================================================ */
    public static function createPeriod(PDO $pdo, array $data) {

        $stmt = $pdo->prepare("
            INSERT INTO periods (code, startDate, endDate, status)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['code'],
            $data['startDate'],
            $data['endDate'],
            $data['status']
        ]);
    }

    /* ============================================================
        ACTUALIZAR INFORMACIÓN DE UN PERIODO
        No modifica estado, solo fechas y código.
    ============================================================ */
    public static function updatePeriod(PDO $pdo, array $data) {

        $stmt = $pdo->prepare("
            UPDATE periods
            SET code = ?, startDate = ?, endDate = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['code'],
            $data['startDate'],
            $data['endDate'],
            $data['id']
        ]);
    }

    /* ============================================================
        CAMBIAR ESTADO DEL PERIODO
        Estados típicos: Creado, Activo, Finalizado
    ============================================================ */
    public static function changeStatus(PDO $pdo, int $id, string $status) {

        $stmt = $pdo->prepare("
            UPDATE periods
            SET status = ?
            WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }
}
