<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/controllers/softwareController.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';



$ctx = requireAuth();
authorizeSoftwareAccess($pdo, $ctx);
$data = json_decode(file_get_contents('php://input'), true);
$projectId = $data['projectId'] ?? null;

if (!$projectId) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing projectId']);
  exit;
}

$stmt = $pdo->prepare("
  INSERT INTO softwareLicenseAcceptances (projectId, userId)
  VALUES (?, ?) ON DUPLICATE KEY UPDATE acceptedAt = CURRENT_TIMESTAMP
");
$stmt->execute([$projectId, $ctx['userId']]);

echo json_encode(['ok' => true]);
