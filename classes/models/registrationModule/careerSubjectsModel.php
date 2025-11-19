<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class CareerSubjectsModel
{
    /* ============================================================
        LISTAR MATERIAS DE UNA CARRERA
        Devuelve todas las asignaturas asignadas al pensum.
    ============================================================ */
    public static function listByCareer($pdo, $careerId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                cs.id,
                cs.careerId,
                cs.subjectId,
                cs.periodNumber,
                s.code,
                s.name,
                s.uv,
                d.name AS departmentName
            FROM careersubjects cs
            INNER JOIN subjects s ON cs.subjectId = s.id
            INNER JOIN departments d ON s.departmentId = d.id
            WHERE cs.careerId = ?
            ORDER BY cs.periodNumber ASC, s.code ASC
        ");

        $stmt->execute([$careerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        AGREGAR UNA MATERIA AL PLAN DE LA CARRERA
        Especifica el período (semestre / trimestre).
    ============================================================ */
    public static function addSubjectToCareer($pdo, $careerId, $subjectId, $periodNumber)
    {
        $stmt = $pdo->prepare("
            INSERT INTO careersubjects (careerId, subjectId, periodNumber)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$careerId, $subjectId, $periodNumber]);
    }

    /* ============================================================
        ELIMINAR UNA MATERIA DEL PLAN
        Eliminación por ID de la relación en careersubjects.
    ============================================================ */
    public static function removeById($pdo, $id)
    {
        $stmt = $pdo->prepare("
            DELETE FROM careersubjects
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    /* ============================================================
        ACTUALIZAR PERÍODO DE UNA MATERIA DENTRO DEL PLAN
    ============================================================ */
    public static function updatePeriod($pdo, $id, $periodNumber)
    {
        $stmt = $pdo->prepare("
            UPDATE careersubjects
            SET periodNumber = ?
            WHERE id = ?
        ");

        return $stmt->execute([$periodNumber, $id]);
    }
}
