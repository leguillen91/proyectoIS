<?php

require_once __DIR__ . '/../../../bootstrap/init.php';

class SubjectPrerequisitesModel {

    /* ============================================================
        LISTAR PRERREQUISITOS DE UNA MATERIA
        Retorna códigos y nombres de cada prerequisito.
    ============================================================ */
    public static function listBySubject($pdo, $subjectId) {
        $stmt = $pdo->prepare("
            SELECT 
                sp.*, 
                s.code AS prerequisiteCode, 
                s.name AS prerequisiteName
            FROM subjectprerequisites sp
            INNER JOIN subjects s ON sp.prereqId = s.id
            WHERE sp.subjectId = ?
        ");
        $stmt->execute([$subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
        AGREGAR PRERREQUISITO
        INSERT IGNORE → evita error por duplicados.
        RETURN: 1 si insertó / 0 si ya existía.
    ============================================================ */
    public static function addPrerequisite($pdo, $subjectId, $prereqId) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO subjectprerequisites (subjectId, prereqId)
            VALUES (?, ?)
        ");

        $stmt->execute([$subjectId, $prereqId]);
        return $stmt->rowCount();  
    }

    /* ============================================================
        ELIMINAR UN PRERREQUISITO 
        (relación subjectId + prereqId)
    ============================================================ */
    public static function removePrerequisite($pdo, $subjectId, $prereqId) {
        $stmt = $pdo->prepare("
            DELETE FROM subjectprerequisites
            WHERE subjectId = ? AND prereqId = ?
        ");
        return $stmt->execute([$subjectId, $prereqId]);
    }

    /* ============================================================
        LISTADO COMPLETO DE TODAS LAS RELACIONES
        Útil para panel maestro o debugging.
    ============================================================ */
    public static function listAll($pdo) {
        $stmt = $pdo->query("
            SELECT 
                sp.id,
                sp.subjectId,
                sp.prereqId,
                s.code AS subjectCode,
                p.code AS prereqCode
            FROM subjectprerequisites sp
            INNER JOIN subjects s ON sp.subjectId = s.id
            INNER JOIN subjects p ON sp.prereqId = p.id
            ORDER BY sp.subjectId ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
