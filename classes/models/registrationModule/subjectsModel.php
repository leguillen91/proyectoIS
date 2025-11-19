<?php

class SubjectsModel {

    /* ============================================================
        LISTAR TODAS LAS MATERIAS CON SU DEPARTAMENTO
        Retorna listado completo para administración / matrícula.
    ============================================================ */
    public static function listSubjects(PDO $pdo): array {
        $stmt = $pdo->prepare("
            SELECT s.*, d.name AS departmentName
            FROM subjects s
            INNER JOIN departments d ON s.departmentId = d.id
            ORDER BY s.code ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        LISTAR TODOS LOS DEPARTAMENTOS
        Usado en formularios de creación/edición de materias.
    ============================================================ */
    public static function listDepartments(PDO $pdo): array {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM departments 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        OBTENER UNA MATERIA POR ID
        Necesario para edición y para generación de códigos de sección.
    ============================================================ */
    public static function getById(PDO $pdo, int $id): ?array {
        $stmt = $pdo->prepare("
            SELECT *
            FROM subjects
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ============================================================
        CREAR UNA NUEVA MATERIA
    ============================================================ */
    public static function createSubject(PDO $pdo, array $data): bool {
        $stmt = $pdo->prepare("
            INSERT INTO subjects (code, name, uv, departmentId)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['uv'],
            $data['departmentId']
        ]);
    }

    /* ============================================================
        ACTUALIZAR MATERIA EXISTENTE
    ============================================================ */
    public static function updateSubject(PDO $pdo, array $data): bool {
        $stmt = $pdo->prepare("
            UPDATE subjects
            SET code = ?, name = ?, uv = ?, departmentId = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['uv'],
            $data['departmentId'],
            $data['id']
        ]);
    }

    /* ============================================================
        ELIMINAR UNA MATERIA POR ID
    ============================================================ */
    public static function deleteSubject(PDO $pdo, int $id): bool {
        $stmt = $pdo->prepare("
            DELETE FROM subjects 
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}
