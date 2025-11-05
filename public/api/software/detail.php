<?php
// public/api/software/details.php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';
require_once __DIR__ . '/../../../classes/controllers/softwareController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  authorizeSoftwareAccess($pdo, $ctx);

  if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta parámetro id']);
    exit;
  }

  $projectId = intval($_GET['id']);
  $controller = new SoftwareController($pdo);
  $project = $controller->details($projectId);

  if (!$project) {
    http_response_code(404);
    echo json_encode(['error' => 'Proyecto no encontrado']);
    exit;
  }

  echo json_encode($project, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
