<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class SectionsModel {

    /* ============================================================
        LISTAR TODAS LAS SECCIONES
        Incluye datos completos: materia, docente, aula, periodo
    ============================================================ */
    public static function listSections($pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                s.*, 
                sub.code AS subjectCode, 
                sub.name AS subjectName,
                sub.departmentId AS departmentId,
                t.fullName AS teacherName,
                c.roomCode AS roomCode,
                p.code AS periodCode
            FROM sections s
            INNER JOIN subjects sub ON s.subjectId = sub.id
            INNER JOIN teachers t ON s.teacherId = t.id
            INNER JOIN classrooms c ON s.classroomId = c.id
            INNER JOIN periods p ON s.periodId = p.id
            ORDER BY s.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        OBTENER UNA SECCIÓN POR ID
        Retorna todos los datos asociados a la sección
    ============================================================ */
    public static function getSectionById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT 
                s.*, 
                sub.code AS subjectCode,
                sub.name AS subjectName,
                sub.departmentId AS departmentId,
                t.fullName AS teacherName,
                c.roomCode AS roomCode,
                p.code AS periodCode
            FROM sections s
            INNER JOIN subjects sub ON s.subjectId = sub.id
            INNER JOIN teachers t ON s.teacherId = t.id
            INNER JOIN classrooms c ON s.classroomId = c.id
            INNER JOIN periods p ON s.periodId = p.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        CREAR UNA NUEVA SECCIÓN
    ============================================================ */
    public static function createSection($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO sections 
                (periodId, subjectId, sectionCode, teacherId, classroomId, cupo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['periodId'],
            $data['subjectId'],
            $data['sectionCode'],
            $data['teacherId'],
            $data['classroomId'],
            $data['cupo']
        ]);
    }

    /* ============================================================
        ACTUALIZAR DATOS DE UNA SECCIÓN
    ============================================================ */
    public static function updateSection($pdo, $data) {
        $stmt = $pdo->prepare("
            UPDATE sections
            SET 
                periodId = ?, 
                subjectId = ?, 
                sectionCode = ?, 
                teacherId = ?, 
                classroomId = ?, 
                cupo = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['periodId'],
            $data['subjectId'],
            $data['sectionCode'],
            $data['teacherId'],
            $data['classroomId'],
            $data['cupo'],
            $data['id']
        ]);
    }

    /* ============================================================
        ELIMINAR UNA SECCIÓN POR ID
    ============================================================ */
    public static function deleteSection($pdo, $id) {
        $stmt = $pdo->prepare("
            DELETE FROM sections 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /* ============================================================
        LISTAR TODOS LOS CÓDIGOS DE SECCIÓN 
        (para evitar duplicados al generar sectionCode)
    ============================================================ */
    public static function listSectionCodes($pdo, $subjectId, $periodId) {
        $stmt = $pdo->prepare("
            SELECT sectionCode
            FROM sections
            WHERE subjectId = ? 
              AND periodId = ?
            ORDER BY id ASC
        ");

        $stmt->execute([$subjectId, $periodId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

}
