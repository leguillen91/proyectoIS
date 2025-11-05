<?php
// public/api/software/list.php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';
require_once __DIR__ . '/../../../classes/controllers/softwareController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  authorizeSoftwareAccess($pdo, $ctx);

  $controller = new SoftwareController($pdo);
  $result = $controller->list($ctx);

  echo json_encode($result);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
