<?php

include_once __DIR__ . '/../../../bootstrap/init.php';

class ClassroomModel {

    /* ============================================================
        LISTAR TODAS LAS AULAS (CLASSROOMS)
        Incluye edificio, código de aula, capacidad y fecha de creación.
    ============================================================ */
    public static function listAll($pdo) {
        $sql = "
            SELECT 
                id, 
                buildingId, 
                roomCode, 
                capacity, 
                createdAt
            FROM classrooms
            ORDER BY id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
