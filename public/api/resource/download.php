<?php
require_once __DIR__ . '/../../../config/connection.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

try {
  $ctx = requireAuth();
  $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
    exit;
  }

  $controller = new ResourceController();
  $controller->downloadFile($ctx, $id);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
