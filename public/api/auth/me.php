<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

header('Content-Type: application/json');

try {
  $ctx = requireAuth();

  // Obtenemos datos base
  $userId = $ctx['userId'] ?? null;
  $role = strtolower($ctx['role'] ?? '');

  // Datos adicionales
  $career = null;

  if ($role === 'student') {
    // Buscar carrera del estudiante
    $stmt = $pdo->prepare("SELECT career FROM students WHERE userId = ?");
    $stmt->execute([$userId]);
    $career = $stmt->fetchColumn() ?: null;
  } elseif (in_array($role, ['teacher', 'coordinator', 'depthead'])) {
    // Buscar carrera asociada si aplica
    $stmt = $pdo->prepare("SELECT career FROM teachers WHERE userId = ?");
    $stmt->execute([$userId]);
    $career = $stmt->fetchColumn() ?: null;
  }

  echo json_encode([
    'ok' => true,
    'user' => [
      'userId' => $ctx['userId'],
      'email' => $ctx['email'],
      'fullName' => $ctx['fullName'],
      'role' => $ctx['role'],
      'career' => $career,
      'permissions' => $ctx['permissions'] ?? []
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
