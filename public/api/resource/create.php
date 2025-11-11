<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $data = json_decode(file_get_contents("php://input"), true);
  if (!$data) throw new Exception("Invalid JSON payload");

  $module = $data['module'] ?? null;
  if (!$module) throw new Exception("Module required");

  authorizeModuleAccess($ctx, $module);

  $controller = new ResourceController();
  $controller->createResource($data);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
