<?php
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $module = $_GET['module'] ?? 'Software';

  $controller = new ResourceController();
  $controller->getMetadata($ctx, $module);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
