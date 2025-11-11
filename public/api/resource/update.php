<?php
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  $data = json_decode(file_get_contents('php://input'), true);
  if (!$data) throw new Exception("Invalid JSON input");

  $controller = new ResourceController();
  $controller->updateResource($ctx, $data);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
