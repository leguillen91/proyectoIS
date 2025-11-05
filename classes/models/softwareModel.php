<?php
// classes/models/softwareModel.php
class SoftwareModel {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  /**
   * Crear un nuevo proyecto (metadatos)
   */
  public function createProject(array $data): int {
    $stmt = $this->pdo->prepare("
      INSERT INTO softwareProjects (title, description, licenseId, createdBy, status)
      VALUES (:title, :description, :licenseId, :createdBy, 'submitted')
    ");
    $stmt->execute([
      'title' => $data['title'],
      'description' => $data['description'] ?? null,
      'licenseId' => $data['licenseId'],
      'createdBy' => $data['createdBy']
    ]);
    return (int)$this->pdo->lastInsertId();
  }

  /**
   * Listar proyectos visibles según el rol del usuario.
   * Estudiantes → propios + publicados.
   * Resto → todos.
   */
  public function listProjectsByContext(int $userId, string $role): array {
    $role = strtolower($role);

    if ($role === 'student') {
      $stmt = $this->pdo->prepare("
        SELECT p.id, p.title, p.description, p.status,
               COALESCE(l.name, 'Sin licencia') AS licenseName,
               p.createdAt, u.fullName AS author,
               GROUP_CONCAT(t.tagName SEPARATOR ', ') AS tags
        FROM softwareProjects p
        JOIN users u ON p.createdBy = u.id
        LEFT JOIN licenses l ON p.licenseId = l.id
        LEFT JOIN softwareProjectTags pt ON pt.projectId = p.id
        LEFT JOIN softwareTags t ON t.id = pt.tagId
        WHERE (p.createdBy = :uid OR p.status = 'published')
        GROUP BY p.id
        ORDER BY p.createdAt DESC
      ");
      $stmt->execute(['uid' => $userId]);
    } else {
      $stmt = $this->pdo->query("
        SELECT p.id, p.title, p.description, p.status,
               COALESCE(l.name, 'Sin licencia') AS licenseName,
               p.createdAt, u.fullName AS author,
               GROUP_CONCAT(t.tagName SEPARATOR ', ') AS tags
        FROM softwareProjects p
        JOIN users u ON p.createdBy = u.id
        LEFT JOIN licenses l ON p.licenseId = l.id
        LEFT JOIN softwareProjectTags pt ON pt.projectId = p.id
        LEFT JOIN softwareTags t ON t.id = pt.tagId
        GROUP BY p.id
        ORDER BY p.createdAt DESC
      ");
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Obtener detalles de un proyecto (con archivos, tags y readme)
   */
  public function getProjectDetails(int $projectId): ?array {
    $stmt = $this->pdo->prepare("
      SELECT p.id, p.title, p.description, p.status, 
             p.createdAt, u.fullName AS author,
             l.name AS licenseName, l.licenseKey, p.readmeText
      FROM softwareProjects p
      JOIN users u ON p.createdBy = u.id
      LEFT JOIN licenses l ON p.licenseId = l.id
      WHERE p.id = ?
      LIMIT 1
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) return null;

    // Archivos asociados
    $filesStmt = $this->pdo->prepare("
      SELECT id, fileName, fileSize, filePath, fileType
      FROM softwareFiles
      WHERE projectId = ?
      ORDER BY uploadedAt DESC
    ");
    $filesStmt->execute([$projectId]);
    $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Tags
    $tagsStmt = $this->pdo->prepare("
      SELECT t.tagName
      FROM softwareProjectTags pt
      JOIN softwareTags t ON t.id = pt.tagId
      WHERE pt.projectId = ?
    ");
    $tagsStmt->execute([$projectId]);
    $tags = array_column($tagsStmt->fetchAll(PDO::FETCH_ASSOC), 'tagName');

    $project['files'] = $files;
    $project['tags'] = $tags;

    return $project;
  }

  /**
   * Actualizar estado de proyecto
   */
  public function updateProjectStatus(int $projectId, string $newStatus, int $changedBy): bool {
    $stmt = $this->pdo->prepare("
      SELECT status FROM softwareProjects WHERE id = ?
    ");
    $stmt->execute([$projectId]);
    $oldStatus = $stmt->fetchColumn();

    $up = $this->pdo->prepare("
      UPDATE softwareProjects
      SET status = ?, updatedAt = NOW()
      WHERE id = ?
    ");
    $up->execute([$newStatus, $projectId]);

    // Historial
    $hist = $this->pdo->prepare("
      INSERT INTO softwareStatusHistory (projectId, oldStatus, newStatus, changedBy)
      VALUES (?, ?, ?, ?)
    ");
    $hist->execute([$projectId, $oldStatus, $newStatus, $changedBy]);

    return true;
  }
}
