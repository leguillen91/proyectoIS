<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $data = json_decode(file_get_contents("php://input"), true);
  if (!$data) throw new Exception("Invalid JSON payload");

  $resourceId = $data['resourceId'] ?? null;
  $decision = $data['decision'] ?? null;
  $comments = $data['comments'] ?? null;
  $reviewer = $ctx['userId'] ?? null; //  usamos userId porque personId no existe en tu users table

  if (!$resourceId || !$decision) {
    throw new Exception("resourceId and decision required");
  }

  //  ahora sí podemos autorizar
  $model = new ResourceModel();
  $module = $model->getModuleByResource($resourceId);
  if (!$module) throw new Exception("Resource not found");
  authorizeModuleAccess($ctx, $module);

  //  Actualizar estado
  $controller = new ResourceController();
  $controller->updateStatus($resourceId, $decision, $reviewer, $comments);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
