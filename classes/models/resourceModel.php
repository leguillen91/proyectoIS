<?php
require_once __DIR__ . '/../../config/connection.php';

class ResourceModel {

  private $db;

  public function __construct() {
    global $pdo;
    $this->db = $pdo;
  }

  /**
   * Obtener instancia de conexión PDO
   */
  public function getConnection() {
    return $this->db;
  }

  /* ============================================================
     RESOURCE — CRUD PRINCIPAL
  ============================================================ */

  /**
   * Crear un recurso nuevo en la tabla `resource`
   */
  public function createResource($data) {
    $sql = "INSERT INTO resource 
              (title, description, module, resourceTypeId, createdByPersonId, licenseId, visibility, downloadPolicy, status)
            VALUES (:title, :description, :module, :resourceTypeId, :createdByPersonId, :licenseId, :visibility, :downloadPolicy, :status)";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      ':title' => $data['title'],
      ':description' => $data['description'] ?? null,
      ':module' => $data['module'],
      ':resourceTypeId' => $data['resourceTypeId'],
      ':createdByPersonId' => $data['createdByPersonId'] ?? null,
      ':licenseId' => $data['licenseId'] ?? null,
      ':visibility' => $data['visibility'] ?? 'UniversityOnly',
      ':downloadPolicy' => $data['downloadPolicy'] ?? 'ViewOnly',
      ':status' => $data['status'] ?? 'Draft'
    ]);

    return $this->db->lastInsertId();
  }

  /**
   * Obtener un recurso por ID junto a:
   * - Tipo
   * - Licencia
   * - Archivos
   * - Autores
   * - Tags
   * - Revisiones
   */
  public function getResourceById($id) {

    // Información del recurso + joins
    $sql = "
      SELECT 
        r.*,
        rt.description AS typeName,
        l.name AS licenseName
      FROM resource r
      LEFT JOIN resourcetype rt ON r.resourceTypeId = rt.idResourceType
      LEFT JOIN license l ON r.licenseId = l.idLicense
      WHERE r.idResource = ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resource) return null;

    // Archivos del recurso
    $sqlFiles = "
      SELECT 
        idResourceFile,
        fileKind,
        originalFilename,
        mimeType,
        sizeBytes,
        createdAt
      FROM resourcefile
      WHERE resourceId = ?
    ";
    $stmt = $this->db->prepare($sqlFiles);
    $stmt->execute([$id]);
    $resource['files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Autores del recurso
    $sqlAuthors = "
      SELECT 
        idResourceAuthor,
        resourceId,
        personId,
        authorName,
        role
      FROM resourceauthor
      WHERE resourceId = ?
    ";
    $stmt = $this->db->prepare($sqlAuthors);
    $stmt->execute([$id]);
    $resource['authors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tags del recurso
    $sqlTags = "
      SELECT t.idTag, t.name
      FROM tag t
      INNER JOIN resourcetag rt ON rt.tagId = t.idTag
      WHERE rt.resourceId = ?
    ";
    $stmt = $this->db->prepare($sqlTags);
    $stmt->execute([$id]);
    $resource['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revisiones
    $sqlReviews = "
      SELECT 
        idReview,
        reviewerPersonId,
        decision,
        comments,
        reviewedAt
      FROM review
      WHERE resourceId = ?
      ORDER BY reviewedAt DESC
    ";
    $stmt = $this->db->prepare($sqlReviews);
    $stmt->execute([$id]);
    $resource['reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $resource;
  }

  /**
   * Listar recursos por módulo y opcionalmente por estado
   */
  public function listResources($module, $status = null) {
    $sql = "SELECT * FROM resource WHERE module = ?";
    $params = [$module];

    if ($status) {
      $sql .= " AND status = ?";
      $params[] = $status;
    }

    $sql .= " ORDER BY createdAt DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Actualizar recurso existente + reasignar autores + tags
   */
  public function updateResource($ctx, $data) {
    $db = $this->getConnection();

    if (empty($data['idResource'])) {
      throw new Exception("Missing resource ID");
    }

    // Actualizar datos principales
    $stmt = $db->prepare("
      UPDATE resource
      SET title = ?, description = ?, resourceTypeId = ?, licenseId = ?, updatedAt = NOW()
      WHERE idResource = ?
    ");

    $stmt->execute([
      $data['title'],
      $data['description'],
      $data['resourceTypeId'],
      $data['licenseId'],
      $data['idResource']
    ]);

    // Reemplazar tags
    $db->prepare("DELETE FROM resourcetag WHERE resourceId = ?")->execute([$data['idResource']]);

    if (!empty($data['tags'])) {
      $tagStmt = $db->prepare("
        INSERT INTO resourcetag (resourceId, tagId)
        VALUES (?, (SELECT idTag FROM tag WHERE name = ? LIMIT 1))
      ");

      foreach ($data['tags'] as $tagName) {
        $tagStmt->execute([$data['idResource'], $tagName]);
      }
    }

    // Reemplazar autores
    $db->prepare("DELETE FROM resourceauthor WHERE resourceId = ?")->execute([$data['idResource']]);

    if (!empty($data['authors'])) {
      $authorStmt = $db->prepare("
        INSERT INTO resourceauthor (resourceId, authorName, role)
        VALUES (?, ?, ?)
      ");
      foreach ($data['authors'] as $a) {
        $authorStmt->execute([
          $data['idResource'],
          $a['authorName'],
          $a['role'] ?? 'Author'
        ]);
      }
    }

    return [
      'idResource' => $data['idResource'],
      'title' => $data['title'],
      'description' => $data['description']
    ];
  }

  /**
   * Eliminar un recurso
   */
  public function deleteResource($id) {
    $stmt = $this->db->prepare("DELETE FROM resource WHERE idResource = ?");
    return $stmt->execute([$id]);
  }

  /* ============================================================
     FILES — ARCHIVOS DEL RECURSO
  ============================================================ */

  /**
   * Insertar archivo asociado a un recurso
   */
  public function addFile($data) {
    $sql = "INSERT INTO resourcefile 
            (resourceId, fileKind, fileBlob, originalFilename, mimeType, sizeBytes, checksum)
            VALUES (:resourceId, :fileKind, :fileBlob, :originalFilename, :mimeType, :sizeBytes, :checksum)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      ':resourceId' => $data['resourceId'],
      ':fileKind' => $data['fileKind'],
      ':fileBlob' => $data['fileBlob'],
      ':originalFilename' => $data['originalFilename'],
      ':mimeType' => $data['mimeType'],
      ':sizeBytes' => $data['sizeBytes'],
      ':checksum' => $data['checksum'] ?? null
    ]);
  }

  /**
   * Obtener archivos asociados a un recurso
   */
  public function getFilesByResource($resourceId) {
    $stmt = $this->db->prepare("
      SELECT idResourceFile, fileKind, originalFilename, mimeType, sizeBytes, createdAt 
      FROM resourcefile 
      WHERE resourceId = ?
    ");
    $stmt->execute([$resourceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ============================================================
     AUTHORS — AUTORES DEL RECURSO
  ============================================================ */

  /**
   * Agregar un autor al recurso
   */
  public function addAuthor($data) {
    $sql = "INSERT INTO resourceauthor 
            (resourceId, personId, authorName, role)
            VALUES (:resourceId, :personId, :authorName, :role)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      ':resourceId' => $data['resourceId'],
      ':personId' => $data['personId'] ?? null,
      ':authorName' => $data['authorName'] ?? null,
      ':role' => $data['role'] ?? 'Author'
    ]);
  }

  /**
   * Obtener autores de un recurso
   */
  public function getAuthorsByResource($resourceId) {
    $stmt = $this->db->prepare("
      SELECT * 
      FROM resourceauthor 
      WHERE resourceId = ?
    ");
    $stmt->execute([$resourceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ============================================================
     TAGS — ETIQUETAS DEL RECURSO
  ============================================================ */

  /**
   * Asociar un tag a un recurso
   */
  public function linkTag($resourceId, $tagId) {
    $sql = "INSERT IGNORE INTO resourcetag (resourceId, tagId) VALUES (?, ?)";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$resourceId, $tagId]);
  }

  /**
   * Obtener tags asociados al recurso
   */
  public function getTagsByResource($resourceId) {
    $sql = "
      SELECT t.idTag, t.name 
      FROM tag t
      JOIN resourcetag rt ON t.idTag = rt.tagId 
      WHERE rt.resourceId = ?
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$resourceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ============================================================
     REVIEWS — REVISIÓN/DICTAMEN
  ============================================================ */

  /**
   * Agregar revisión de un coordinador/jefe
   */
  public function addReview($data) {
    $sql = "INSERT INTO review 
              (resourceId, reviewerPersonId, decision, comments)
            VALUES (:resourceId, :reviewerPersonId, :decision, :comments)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
      ':resourceId' => $data['resourceId'],
      ':reviewerPersonId' => $data['reviewerPersonId'],
      ':decision' => $data['decision'],
      ':comments' => $data['comments'] ?? null
    ]);
  }

  /**
   * Obtener revisiones asociadas a un recurso
   */
  public function getReviewsByResource($resourceId) {
    $stmt = $this->db->prepare("
      SELECT * 
      FROM review 
      WHERE resourceId = ?
    ");
    $stmt->execute([$resourceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Obtener archivo por ID
   */
  public function getFileById($id) {
    $stmt = $this->db->prepare("
      SELECT idResourceFile, resourceId, originalFilename, mimeType, fileBlob 
      FROM resourcefile 
      WHERE idResourceFile = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Actualizar estado del recurso (Draft, Approved, Archived...)
   */
  public function updateStatus($id, $status) {
    $sql = "UPDATE resource SET status = :status, updatedAt = NOW() WHERE idResource = :id";
    $stmt = $this->db->prepare($sql);

    $stmt->execute([
      ':status' => $status,
      ':id' => $id
    ]);

    return $stmt->rowCount() > 0;
  }

  /**
   * Obtener módulo al que pertenece un recurso
   */
  public function getModuleByResource($id) {
    $stmt = $this->db->prepare("
      SELECT module 
      FROM resource 
      WHERE idResource = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
  }

  /**
   * Obtener lista completa de tags
   */
  public function getAllTags() {
    $stmt = $this->db->query("
      SELECT idTag, name 
      FROM tag 
      ORDER BY name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Obtener todas las licencias
   */
  public function getAllLicenses() {
    $stmt = $this->db->query("
      SELECT idLicense, name, code 
      FROM license 
      ORDER BY name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Obtener autores vinculados a un módulo específico
   */
  public function getAuthorsByModule($module) {

    $roles = [
      'software' => "'Author','CoAuthor'",
      'music' => "'Composer','Editor'",
      'library' => "'Writer','Editor'"
    ];

    $allowed = $roles[$module] ?? "'Author'";

    $stmt = $this->db->query("
      SELECT DISTINCT authorName 
      FROM resourceauthor 
      WHERE role IN ($allowed) 
      ORDER BY authorName ASC
    ");

    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'authorName');
  }

  /**
   * Asociar tag a recurso
   */
  public function linkTagToResource($resourceId, $tagId) {
    $stmt = $this->db->prepare("
      INSERT IGNORE INTO resourcetag (resourceId, tagId)
      VALUES (?, ?)
    ");
    $stmt->execute([$resourceId, $tagId]);
  }

  /**
   * Obtener metadatos para filtros (licencias, tags, autores, tipos)
   */
  public function getMetadataData($ctx, $module) {
    $db = $this->getConnection();

    // Normalizar módulo
    $module = ucfirst(strtolower(trim($module)));

    // Licencias
    $stmt = $db->query("SELECT idLicense, name, code, url FROM license ORDER BY name ASC");
    $allLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($module === 'Software') {
      $licenses = array_filter($allLicenses, fn($l) =>
        in_array(strtoupper($l['code']), ['MIT','GPL3','APACHE2','BSD','ISC'])
      );
    } elseif ($module === 'Music') {
      $licenses = array_filter($allLicenses, fn($l) =>
        !in_array(strtoupper($l['code']), ['GPL3','APACHE2'])
      );
    } else {
      $licenses = $allLicenses;
    }

    // Tags asociados al módulo
    $stmt = $db->prepare("
      SELECT DISTINCT t.name
      FROM tag t
      JOIN resourcetag rt ON rt.tagId = t.idTag
      JOIN resource r ON r.idResource = rt.resourceId
      WHERE r.module = ?
      ORDER BY t.name ASC
    ");
    $stmt->execute([$module]);
    $tags = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // Tipos de recurso
    $stmt = $db->query("
      SELECT idResourceType, code, description 
      FROM resourcetype 
      ORDER BY description ASC
    ");
    $resourceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Autores vinculados al módulo
    $stmt = $db->prepare("
      SELECT DISTINCT ra.authorName
      FROM resourceauthor ra
      JOIN resource r ON ra.resourceId = r.idResource
      WHERE r.module = ? AND ra.authorName IS NOT NULL
      ORDER BY ra.authorName ASC
    ");
    $stmt->execute([$module]);
    $authors = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'authorName');

    return [
      'licenses' => array_values($licenses),
      'tags' => $tags,
      'authors' => $authors,
      'resourceTypes' => $resourceTypes
    ];
  }

  /**
   * Buscar archivo por ID
   */
  public function findFileById(int $id): ?array {
    $db = $this->getConnection();

    $stmt = $db->prepare("
      SELECT originalFilename, mimeType, fileBlob, sizeBytes
      FROM resourcefile
      WHERE idResourceFile = ?
      LIMIT 1
    ");

    $stmt->execute([$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    return $file ?: null;
  }

}
