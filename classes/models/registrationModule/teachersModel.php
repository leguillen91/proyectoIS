<?php

class TeachersModel
{
    /**
     * Obtener listado completo de docentes.
     * Incluye información básica del teacher y 
     * el nombre de la carrera asociada.
     */
    public static function listTeachers(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT 
                t.id,
                t.userId,
                t.fullName,
                t.employeeNumber,
                t.careerId,
                c.name AS career,
                t.academicCenter,
                t.createdAt
            FROM teachers t
            JOIN careers c ON c.id = t.careerId
            ORDER BY t.fullName ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
