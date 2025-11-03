<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/controllers/studentController.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

$ctx = requireAuth();

$controller = new StudentController($pdo);

$studentId = $_GET['id'] ?? null;
if (!$studentId) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing student ID']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$controller->update($ctx, (int)$studentId, $body);
