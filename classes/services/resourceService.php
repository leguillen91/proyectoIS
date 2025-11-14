<?php
require_once __DIR__ . '/../models/resourceModel.php';
require_once __DIR__ . '/mailerService.php';
class ResourceService {
  private $model;

  public function __construct() {
    $this->model = new ResourceModel();
  }

  /* =======================================
     CREAR RECURSO
  ======================================= */
 public function createResource($data) {
  $db = $this->model->getConnection();
  $db->beginTransaction();

  try {
    // 🔧 Normalizar authors y tags si vienen como JSON string
    if (!empty($data['authors']) && is_string($data['authors'])) {
      $decoded = json_decode($data['authors'], true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $data['authors'] = $decoded;
      } else {
        $data['authors'] = [['authorName' => trim($data['authors'])]];
      }
    }

    if (!empty($data['tags']) && is_string($data['tags'])) {
      $decoded = json_decode($data['tags'], true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $data['tags'] = $decoded;
      } else {
        $data['tags'] = array_map('trim', explode(',', $data['tags']));
      }
    }

    // --------------------------------------
    // 1. Validaciones básicas
    // --------------------------------------
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $module = trim($data['module'] ?? '');
    $resourceTypeId = (int)($data['resourceTypeId'] ?? 0);
    $licenseId = !empty($data['licenseId']) ? (int)$data['licenseId'] : null;
    $createdByPersonId = $data['createdByPersonId'] ?? null;

    if ($title === '' || $module === '' || !$resourceTypeId) {
      throw new Exception("Campos obligatorios faltantes.");
    }

    // --------------------------------------
    // 2. Insertar recurso principal
    // --------------------------------------
    $stmt = $db->prepare("
      INSERT INTO resource (
        title, description, module, resourceTypeId, createdByPersonId,
        licenseId, visibility, downloadPolicy, status, createdAt
      ) VALUES (?, ?, ?, ?, ?, ?, 'UniversityOnly', 'DownloadAllowed', 'Draft', NOW())
    ");
    $stmt->execute([
      $title,
      $description,
      $module,
      $resourceTypeId,
      $createdByPersonId,
      $licenseId
    ]);

    $resourceId = $db->lastInsertId();

    // --------------------------------------
    // 3. Autores (si existen)
    // --------------------------------------
    if (!empty($data['authors'])) {
      foreach ($data['authors'] as $a) {
        $authorName = trim($a['authorName'] ?? '');
        $role = $a['role'] ?? 'Author';
        if ($authorName !== '') {
          $stmt = $db->prepare("
            INSERT INTO resourceAuthor (resourceId, authorName, role)
            VALUES (?, ?, ?)
          ");
          $stmt->execute([$resourceId, $authorName, $role]);
        }
      }
    }

    // --------------------------------------
    // 4. Tags (crear o vincular)
    // --------------------------------------
    if (!empty($data['tags'])) {
      foreach ($data['tags'] as $tagName) {
        $tagName = trim($tagName);
        if ($tagName === '') continue;

        // Buscar tag existente
        $stmt = $db->prepare("SELECT idTag FROM tag WHERE name = ?");
        $stmt->execute([$tagName]);
        $tagId = $stmt->fetchColumn();

        // Crear si no existe
        if (!$tagId) {
          $stmt = $db->prepare("INSERT INTO tag (name) VALUES (?)");
          $stmt->execute([$tagName]);
          $tagId = $db->lastInsertId();
        }

        // Enlazar tag con recurso (sin duplicar)
        $stmt = $db->prepare("
          INSERT IGNORE INTO resourceTag (resourceId, tagId)
          VALUES (?, ?)
        ");
        $stmt->execute([$resourceId, $tagId]);
      }
    }

    // --------------------------------------
    // 5. Confirmar transacción
    // --------------------------------------
    $db->commit();

    return [
      'idResource' => (int)$resourceId,
      'title' => $title,
      'module' => $module,
      'createdAt' => date('Y-m-d H:i:s')
    ];

  } catch (Exception $e) {
    $db->rollBack();
    throw $e;
  }
}



  /* =======================================
     LISTAR / OBTENER
  ======================================= */
  public function list($module, $status = null) {
    $resources = $this->model->listResources($module, $status);
    return ['ok' => true, 'data' => $resources];
  }

  public function detail($id) {
    $resource = $this->model->getResourceById($id);
    if (!$resource) throw new Exception("Resource not found");

    $resource['files'] = $this->model->getFilesByResource($id);
    $resource['authors'] = $this->model->getAuthorsByResource($id);
    $resource['tags'] = $this->model->getTagsByResource($id);
    $resource['reviews'] = $this->model->getReviewsByResource($id);

    return ['ok' => true, 'data' => $resource];
  }

  /* =======================================
     ACTUALIZAR / ELIMINAR
  ======================================= */
  public function update($id, $payload) {
    $existing = $this->model->getResourceById($id);
    if (!$existing) throw new Exception("Resource not found");

    $this->model->updateResource($id, $payload);
    return ['ok' => true, 'message' => 'Resource updated'];
  }

  public function delete($id) {
    $this->model->deleteResource($id);
    return ['ok' => true, 'message' => 'Resource deleted'];
  }

  /* =======================================
     ARCHIVOS
  ======================================= */
  public function uploadFile($resourceId, $file, $fileKind) {
    if (!isset($file['tmp_name'])) {
      throw new Exception("Invalid file upload");
    }

    $fileBlob = file_get_contents($file['tmp_name']);
    $data = [
      'resourceId' => $resourceId,
      'fileKind' => $fileKind,
      'fileBlob' => $fileBlob,
      'originalFilename' => $file['name'],
      'mimeType' => $file['type'],
      'sizeBytes' => $file['size'],
      'checksum' => sha1($fileBlob)
    ];

    $this->model->addFile($data);
    return ['ok' => true, 'message' => 'File uploaded'];
  }

  public function getFiles($resourceId) {
    $files = $this->model->getFilesByResource($resourceId);
    return ['ok' => true, 'data' => $files];
  }

  /* =======================================
     REVISIÓN
  ======================================= */
  public function review($payload) {
    if (empty($payload['resourceId']) || empty($payload['reviewerPersonId']) || empty($payload['decision'])) {
      throw new Exception("Missing review data");
    }

    $this->model->addReview($payload);
    return ['ok' => true, 'message' => 'Review registered'];
  }
  /* =======================================
   ACTUALIZAR ESTADO
======================================= */
 public function updateStatus($resourceId, $decision, $reviewerPersonId, $comments = null) {
        $valid = ['Approved','NeedsCorrection','Rejected','UnderReview'];
        if (!in_array($decision, $valid)) throw new Exception("Invalid status decision");

        $statusMap = [
            'Approved' => 'Approved',
            'NeedsCorrection' => 'NeedsCorrection',
            'Rejected' => 'Rejected',
            'UnderReview' => 'UnderReview'
        ];
        $newStatus = $statusMap[$decision];

        // Registrar revisión
        $this->model->addReview([
            'resourceId' => $resourceId,
            'reviewerPersonId' => $reviewerPersonId,
            'decision' => $decision,
            'comments' => $comments
        ]);

        // Actualizar estado
        $this->model->updateStatus($resourceId, $newStatus);

        // Notificación por correo
        $mailer = new MailerService();
        $subject = "Cambio de estado del recurso #{$resourceId}";
        $message = "El recurso #{$resourceId} cambió su estado a {$newStatus}.\nComentarios: {$comments}";
        $result = $mailer->send("placeholder@unah.edu.hn", $subject, $message);

        return [
            'ok' => true,
            'message' => "Status updated to {$newStatus}",
            'mail' => $result
        ];
    }
    public function getMetadata($ctx, $module) {
  return $this->model->getMetadataData($ctx, $module);
}
    public function getFileById(int $id): ?array {
        return $this->model->findFileById($id);
    }


    public function updateResource($ctx, $data) {
        return $this->update($data['idResource'], $data);
    }

            
}
