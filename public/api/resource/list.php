<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $module = $_GET['module'] ?? null;
  if (!$module) throw new Exception("Module required");

  authorizeModuleAccess($ctx, $module);

  $controller = new ResourceController();
  $status = $_GET['status'] ?? null;
  $controller->listResources($module, $status);


} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
