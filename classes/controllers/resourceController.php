<?php
require_once __DIR__ . '/../services/resourceService.php';

class ResourceController {
  private $service;

  public function __construct() {
    $this->service = new ResourceService();
  }

  /* ===========================
     CREAR
  =========================== */
  public function createResource($data) {
    try {
        $res = $this->service->createResource($data);
        echo json_encode(['ok' => true, 'data' => $res]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    }


  /* ===========================
     LISTAR
  =========================== */
  public function listResources($module, $status = null) {
    try {
      $result = $this->service->list($module, $status);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }

  /* ===========================
     DETALLE
  =========================== */
  public function getResourceDetail($id) {
    try {
      $result = $this->service->detail($id);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }

  /* ===========================
     ACTUALIZAR
  =========================== */
  /* public function updateResource($ctx, $data) {
    try {
        $service = new ResourceService();
        $updated = $service->updateResource($ctx, $data);
        echo json_encode(['ok' => true, 'data' => $updated]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    } */
  public function updateResource($ctx, $data) {
        $service = new ResourceService();

        $id = $data['idResource'] ?? null;
        if (!$id) throw new Exception("Resource ID missing");

        $updated = $service->update($id, $data);
        echo json_encode(['ok' => true, 'data' => $updated]);
    
    }

  /* ===========================
     ELIMINAR
  =========================== */
  public function deleteResource($id) {
    try {
      $result = $this->service->delete($id);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }

  /* ===========================
     ARCHIVOS
  =========================== */
  public function uploadFile($resourceId, $file, $fileKind) {
    try {
      $result = $this->service->uploadFile($resourceId, $file, $fileKind);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }

  public function getFiles($resourceId) {
    try {
      $result = $this->service->getFiles($resourceId);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }

  /* ===========================
     REVISIÓN
  =========================== */
  public function addReview($data) {
    try {
      $result = $this->service->review($data);
      echo json_encode($result);
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
  }


  /* ===========================
     Actualizar estado 
  =========================== */
  public function updateStatus($resourceId, $decision, $reviewerPersonId, $comments = null) {
            try {
                $service = new ResourceService();
                $result = $service->updateStatus($resourceId, $decision, $reviewerPersonId, $comments);
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
        }

    
    /* ===========================
     METADATA  
    =========================== */

  public function getMetadata($ctx, $module) {
  try {
    $service = new ResourceService();
    $data = $service->getMetadata($ctx, $module);
    echo json_encode(['ok' => true, 'data' => $data]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  }
}

 public function downloadFile($ctx, int $id) {
    try {
        $service = new ResourceService();
        $file = $service->getFileById($id);

        if (!$file) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'File not found']);
        return;
        }

        header("Content-Type: {$file['mimeType']}");
        header('Content-Length: ' . (int)$file['sizeBytes']);
        header('Content-Disposition: attachment; filename="' . basename($file['originalFilename']) . '"');

        while (ob_get_level()) ob_end_clean();
        echo $file['fileBlob'];
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    }
   public function previewFile($ctx, int $id) {
  try {
    $service = new ResourceService();

    // 🔹 Buscar por resourceId, no por idResourceFile
    $files = $service->getFiles($id);
    if (empty($files['data']) || count($files['data']) === 0) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'File not found']);
      return;
    }

    // Tomar el primer archivo asociado
    $file = $service->getFileById($files['data'][0]['idResourceFile']);

    if (!$file) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'File not found']);
      return;
    }

    $ext = strtolower(pathinfo($file['originalFilename'], PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
      header("Content-Type: application/pdf");
      header('Content-Disposition: inline; filename="' . basename($file['originalFilename']) . '"');
      echo $file['fileBlob'];
    } else {
      header("Content-Type: {$file['mimeType']}");
      header('Content-Length: ' . (int)$file['sizeBytes']);
      header('Content-Disposition: attachment; filename="' . basename($file['originalFilename']) . '"');
      echo $file['fileBlob'];
    }

    exit;

  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  }
}




}