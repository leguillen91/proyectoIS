<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class SubjectsModelAdvanced
{
    /**
     * 1. Listar materias que NO tienen carrera asignada (materias huérfanas).
     */
    public static function listOrphans($pdo)
    {
        $stmt = $pdo->query("
            SELECT s.*
            FROM subjects s
            LEFT JOIN careersubjects cs ON cs.subjectId = s.id
            WHERE cs.subjectId IS NULL
            ORDER BY s.code ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Listar carreras en las que aparece una materia específica.
     */
    public static function listCareersBySubject($pdo, $subjectId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                cs.id AS relationId,
                cs.semester,
                c.id AS careerId,
                c.name AS careerName
            FROM careersubjects cs
            INNER JOIN careers c ON c.id = cs.careerId
            WHERE cs.subjectId = ?
            ORDER BY cs.semester ASC
        ");

        $stmt->execute([$subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 3. Listar materias pertenecientes a una carrera específica.
     */
    public static function listByCareer($pdo, $careerId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                cs.semester,
                d.name AS departmentName
            FROM careersubjects cs
            INNER JOIN subjects s ON s.id = cs.subjectId
            INNER JOIN departments d ON d.id = s.departmentId
            WHERE cs.careerId = ?
            ORDER BY cs.semester ASC, s.code ASC
        ");

        $stmt->execute([$careerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 4. Listar prerequisitos de una materia dentro de una carrera.
     *    Valida si los prerequisitos pertenecen al plan (semester).
     */
    public static function listPrereqsByCareer($pdo, $subjectId, $careerId)
    {
        $stmt = $pdo->prepare("
            SELECT 
                sp.prerequisiteId,
                ps.code AS prereqCode,
                ps.name AS prereqName,
                cs.semester AS prereqSemester
            FROM subjectprerequisites sp
            INNER JOIN subjects ps ON ps.id = sp.prerequisiteId
            LEFT JOIN careersubjects cs
                ON cs.subjectId = sp.prerequisiteId 
               AND cs.careerId = ?
            WHERE sp.subjectId = ?
        ");

        $stmt->execute([$careerId, $subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 5. Buscar materias por código, nombre o departamento.
     */
    public static function search($pdo, $keyword)
    {
        $keyword = "%$keyword%";

        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                d.name AS departmentName
            FROM subjects s
            INNER JOIN departments d ON d.id = s.departmentId
            WHERE s.code LIKE ?
               OR s.name LIKE ?
               OR d.name LIKE ?
            ORDER BY s.code ASC
        ");

        $stmt->execute([$keyword, $keyword, $keyword]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 6. Consulta completa:
     *    Subjects + carreras + semestres + departamento
     *    Perfecto para panel maestro de materias.
     */
    public static function listAllWithCareerInfo($pdo)
    {
        $stmt = $pdo->query("
            SELECT 
                s.id AS subjectId,
                s.code,
                s.name,
                s.uv,
                d.name AS departmentName,
                c.id AS careerId,
                c.name AS careerName,
                cs.semester
            FROM subjects s
            LEFT JOIN careersubjects cs ON cs.subjectId = s.id
            LEFT JOIN careers c ON c.id = cs.careerId
            LEFT JOIN departments d ON d.id = s.departmentId
            ORDER BY s.code ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
