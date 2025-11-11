<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
header('Content-Type: application/json');

try {
  $ctx = requireAuth();
    $model = new ResourceModel();
    $module = $model->getModuleByResource($resourceId);
    authorizeModuleAccess($ctx, $module);

  $data = json_decode(file_get_contents("php://input"), true);
  if (!$data) throw new Exception("Invalid JSON payload");

  $data['reviewerPersonId'] = $ctx['personId'] ?? null;

  $controller = new ResourceController();
  $controller->addReview($data);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
