<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class GradesModel {

    /* ============================================================
        OBTENER NOTAS POR SECCIÓN (VISTA DEL DOCENTE)
        Incluye nombres de estudiantes, asignatura y código de sección.
    ============================================================ */
    public static function getGradesBySection($pdo, $sectionId) {
        $stmt = $pdo->prepare("
            SELECT 
                sg.*, 
                st.fullName AS studentName,
                st.enrollmentCode,
                s.subjectId,
                sub.code AS subjectCode,
                sub.name AS subjectName
            FROM studentgrades sg
            INNER JOIN students st ON sg.studentId = st.id
            INNER JOIN sections s ON sg.sectionId = s.id
            INNER JOIN subjects sub ON s.subjectId = sub.id
            WHERE sg.sectionId = ?
            ORDER BY st.fullName ASC
        ");

        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        OBTENER NOTAS POR ESTUDIANTE
        Incluye sección, código de asignatura y nombre del docente.
    ============================================================ */
    public static function getGradesByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                sg.*, 
                sec.sectionCode,
                sub.code AS subjectCode,
                sub.name AS subjectName,
                t.fullName AS teacherName
            FROM studentgrades sg
            INNER JOIN sections sec ON sg.sectionId = sec.id
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            INNER JOIN teachers t ON sec.teacherId = t.id
            WHERE sg.studentId = ?
            ORDER BY sg.updatedAt DESC
        ");

        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        ASIGNAR O ACTUALIZAR UNA NOTA
        Actualiza estado y calificación del estudiante.
    ============================================================ */
    public static function assignGrade($pdo, $studentId, $sectionId, $grade, $status) {
        $stmt = $pdo->prepare("
            UPDATE studentgrades
            SET grade = ?, status = ?
            WHERE studentId = ? AND sectionId = ?
        ");

        return $stmt->execute([$grade, $status, $studentId, $sectionId]);
    }
}
