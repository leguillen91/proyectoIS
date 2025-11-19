<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class EnrollmentModel {

    /* ============================================================
        LISTAR CLASES DISPONIBLES POR CARRERA Y PERÍODO
        Filtra por departamento relacionado a la carrera del estudiante.
    ============================================================ */
    public static function listAvailableSubjects($pdo, $career, $periodId) {
        $stmt = $pdo->prepare("
            SELECT 
                sec.*, 
                sub.code AS subjectCode, 
                sub.name AS subjectName, 
                sub.uv,
                t.fullName AS teacherName, 
                c.roomCode, 
                p.code AS periodCode
            FROM sections sec
            INNER JOIN subjects sub ON sec.subjectId = sub.id
            INNER JOIN teachers t ON sec.teacherId = t.id
            INNER JOIN classrooms c ON sec.classroomId = c.id
            INNER JOIN periods p ON sec.periodId = p.id
            WHERE p.id = ?
              AND (
                    sub.departmentId IN (
                        SELECT id 
                        FROM departments 
                        WHERE name LIKE CONCAT('%', ?, '%')
                    )
                  )
        ");

        $stmt->execute([$periodId, $career]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        OBTENER HORARIO DE UNA SECCIÓN (PARA EVITAR CHOQUES)
    ============================================================ */
    public static function getSectionSchedule($pdo, $sectionId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM sectionschedule
            WHERE sectionId = ?
        ");
        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        HORARIO DEL ESTUDIANTE (CLASES YA MATRICULADAS)
        Usado para validar choques antes de matricular.
    ============================================================ */
    public static function getStudentSchedule($pdo, $studentId, $periodId) {
        $stmt = $pdo->prepare("
            SELECT ss.*
            FROM studentenrollments se
            INNER JOIN sectionschedule ss ON ss.sectionId = se.sectionId
            WHERE se.studentId = ? 
              AND se.periodId = ? 
              AND se.status = 'Inscrito'
        ");

        $stmt->execute([$studentId, $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        INSERTAR MATRÍCULA
    ============================================================ */
    public static function enroll($pdo, $studentId, $section) {
        $stmt = $pdo->prepare("
            INSERT INTO studentenrollments (studentId, sectionId, periodId, uv)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $studentId,
            $section['sectionId'],
            $section['periodId'],
            $section['uv']
        ]);
    }

    /* ============================================================
        RETIRAR UNA CLASE
    ============================================================ */
    public static function withdraw($pdo, $enrollmentId) {
        $stmt = $pdo->prepare("
            UPDATE studentenrollments
            SET status = 'Retirado'
            WHERE id = ?
        ");

        return $stmt->execute([$enrollmentId]);
    }

    /* ============================================================
        LISTA DE CLASES MATRICULADAS POR EL ESTUDIANTE
    ============================================================ */
    public static function studentEnrollments($pdo, $studentId, $periodId) {
        $stmt = $pdo->prepare("
            SELECT 
                se.*, 
                sub.code AS subjectCode, 
                sub.name AS subjectName,
                t.fullName AS teacherName,
                ss.startTime, 
                ss.endTime, 
                ss.day
            FROM studentenrollments se
            INNER JOIN sections s ON se.sectionId = s.id
            INNER JOIN subjects sub ON s.subjectId = sub.id
            INNER JOIN teachers t ON s.teacherId = t.id
            INNER JOIN sectionschedule ss ON ss.sectionId = s.id
            WHERE se.studentId = ? 
              AND se.periodId = ? 
              AND se.status = 'Inscrito'
            ORDER BY ss.day, ss.startTime
        ");

        $stmt->execute([$studentId, $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
