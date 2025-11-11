<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';
require_once __DIR__ . '/../../../classes/models/resourceModel.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $resourceId = $_GET['id'] ?? null;
  if (!$resourceId) throw new Exception("Resource ID required");

  // Obtener el módulo desde la BD antes de autorizar
  $model = new ResourceModel();
  $module = $model->getModuleByResource($resourceId);
  if (!$module) throw new Exception("Resource not found");

  // Autorizar acceso
  authorizeModuleAccess($ctx, $module);

  // Obtener detalle
  $controller = new ResourceController();
  $controller->getResourceDetail($resourceId);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
