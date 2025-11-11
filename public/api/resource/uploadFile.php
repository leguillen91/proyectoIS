<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';
require_once __DIR__ . '/../../../classes/models/resourceModel.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception("Method not allowed");
  }

  $resourceId = $_POST['resourceId'] ?? null;
  $fileKind = $_POST['fileKind'] ?? 'Primary';

  if (!$resourceId || empty($_FILES['file'])) {
    throw new Exception("resourceId and file are required");
  }

  // Obtener módulo del recurso antes de autorizar
  $model = new ResourceModel();
  $module = $model->getModuleByResource($resourceId);
  if (!$module) {
    throw new Exception("Resource not found");
  }

  // Autorizar acceso según módulo y carrera del usuario
  authorizeModuleAccess($ctx, $module);

  // Subida de archivo
  $controller = new ResourceController();
  $controller->uploadFile($resourceId, $_FILES['file'], $fileKind);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
