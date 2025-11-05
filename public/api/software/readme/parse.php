<?php
require_once __DIR__ . '/../../../../bootstrap/init.php';
require_once __DIR__ . '/../../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../../middleware/authorizeSoftwareAccess.php';

header('Content-Type: application/json');

$ctx = requireAuth();
authorizeSoftwareAccess($pdo, $ctx);

if (!isset($_FILES['readme'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Falta el archivo README.md']);
  exit;
}

$file = $_FILES['readme'];
if (strtolower($file['name']) !== 'readme.md') {
  http_response_code(400);
  echo json_encode(['error' => 'El archivo debe llamarse README.md']);
  exit;
}

$content = file_get_contents($file['tmp_name']);
echo json_encode(['ok' => true, 'content' => $content]);
