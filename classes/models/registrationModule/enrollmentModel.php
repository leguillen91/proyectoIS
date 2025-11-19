<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class EnrollmentModel
{
    /**
     * Obtener el período activo (status = 'abierto')
     */
    public static function getActivePeriod($pdo)
    {
        $stmt = $pdo->query("SELECT id FROM periods WHERE status='abierto' LIMIT 1");
        return $stmt->fetchColumn();
    }

    /**
     * Listar asignaturas inscritas del estudiante en el período activo
     */
    public static function listEnrolled($pdo, $studentId, $periodId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                e.id AS enrollmentId,
                sec.id AS sectionId,
                sec.sectionCode,
                sub.code AS subjectCode,
                sub.name AS subjectName,
                sub.uv,
                t.fullName AS teacherName,
                sec.cupo,
                p.code AS periodCode
            FROM enrollment e
            INNER JOIN sections sec ON e.sectionId = sec.id
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            INNER JOIN periods p ON e.periodId = p.id
            LEFT JOIN teachers t ON sec.teacherId = t.id
            WHERE e.studentId = ? AND e.periodId = ?
            ORDER BY sub.code ASC
        ");

        $stmt->execute([$studentId, $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listar horario de todas las clases inscritas (vista semanal)
     */
    public static function listSchedule($pdo, $studentId, $periodId)
    {
        $stmt = $pdo->prepare("
            SELECT
                sec.subjectId,
                sub.code AS subjectCode,
                sec.sectionCode,
                sch.day,
                sch.startTime,
                sch.endTime,
                b.name AS buildingName,
                c.roomCode AS classroomName
            FROM enrollment e
            INNER JOIN sections sec ON e.sectionId = sec.id
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            INNER JOIN sectionschedule sch ON sch.sectionId = sec.id
            INNER JOIN classrooms c ON sec.classroomId = c.id
            INNER JOIN buildings b ON c.buildingId = b.id
            WHERE e.studentId = ?
              AND sec.periodId = ?
            ORDER BY sch.day, sch.startTime
        ");

        $stmt->execute([$studentId, $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insertar matrícula
     */
    public static function addEnrollment($pdo, $studentId, $sectionId, $periodId)
    {
        $stmt = $pdo->prepare("
            INSERT INTO enrollment (studentId, sectionId, periodId)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$studentId, $sectionId, $periodId]);
    }

    /**
     * Retirar matrícula
     */
    public static function removeEnrollment($pdo, $studentId, $sectionId)
    {
        $stmt = $pdo->prepare("
            DELETE FROM enrollment
            WHERE studentId = ? AND sectionId = ?
        ");

        return $stmt->execute([$studentId, $sectionId]);
    }

    /**
     * Verificar si ya está inscrito en la sección
     */
    public static function isAlreadyEnrolled($pdo, $studentId, $sectionId)
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM enrollment
            WHERE studentId = ? AND sectionId = ?
        ");

        $stmt->execute([$studentId, $sectionId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * UV inscritas actualmente
     */
    public static function enrolledUV($pdo, $studentId, $periodId)
    {
        $stmt = $pdo->prepare("
            SELECT SUM(sub.uv) AS totalUV
            FROM enrollment e
            INNER JOIN sections sec ON e.sectionId = sec.id
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            WHERE e.studentId = ? AND e.periodId = ?
        ");

        $stmt->execute([$studentId, $periodId]);
        return $stmt->fetchColumn() ?: 0;
    }

    /**
     * Obtener horario de una sección para validar colisiones
     */
    public static function getSectionSchedule($pdo, $sectionId)
    {
        $stmt = $pdo->prepare("
            SELECT day, startTime, endTime
            FROM sectionschedule
            WHERE sectionId = ?
        ");

        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener requisitos de una materia
     */
    public static function getSubjectPrereqs($pdo, $subjectId)
    {
        $stmt = $pdo->prepare("
            SELECT prereqId
            FROM subjectprerequisites
            WHERE subjectId = ?
        ");

        $stmt->execute([$subjectId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Oferta académica filtrada por carrera (pensum) y período activo
     */
    public static function listAvailable($pdo, $careerId, $periodId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                sec.id AS sectionId,
                sec.sectionCode,
                sub.id AS subjectId,
                sub.code AS subjectCode,
                sub.name AS subjectName,
                sub.uv,
                t.fullName AS teacherName,
                sec.cupo,
                p.code AS periodCode,
                d.name AS departmentName
            FROM sections sec
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            INNER JOIN departments d ON sub.departmentId = d.id
            INNER JOIN periods p ON sec.periodId = p.id
            LEFT JOIN teachers t ON sec.teacherId = t.id
            INNER JOIN careersubjects cs ON cs.subjectId = sub.id
            WHERE p.id = ?
              AND cs.careerId = ?
            ORDER BY sub.code ASC
        ");

        $stmt->execute([$periodId, $careerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
