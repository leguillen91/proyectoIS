<?php
// public/api/software/updateStatus.php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';
require_once __DIR__ . '/../../../classes/controllers/softwareController.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();
  authorizeSoftwareAccess($pdo, $ctx);

  // Solo ciertos roles pueden modificar estados
  $allowedRoles = ['coordinator', 'teacher', 'depthead', 'admin'];
  if (!in_array(strtolower($ctx['role']), $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado para cambiar estados']);
    exit;
  }

  $input = json_decode(file_get_contents('php://input'), true);
  if (empty($input['projectId']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos obligatorios faltantes']);
    exit;
  }

  $projectId = intval($input['projectId']);
  $status = strtolower(trim($input['status']));

  // Validar valores permitidos
  $validStatuses = ['submitted', 'under_review', 'approved', 'vetoed', 'published', 'temporarily_hidden'];
  if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado no válido']);
    exit;
  }

  $controller = new SoftwareController($pdo);
  $controller->updateStatus($projectId, $status, $ctx['userId']);

  echo json_encode(['ok' => true, 'message' => 'Estado actualizado']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
