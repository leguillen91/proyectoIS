<?php
require_once __DIR__ . '/../../../../bootstrap/init.php';

try {
  $stmt = $pdo->query("SELECT id, name, licenseKey FROM licenses ORDER BY name ASC");
  $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
  header('Content-Type: application/json');
  echo json_encode($data);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
