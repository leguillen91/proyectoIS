<?php
function authorizeSoftwareAccess($pdo, $ctx) {
  $userId = $ctx['userId'] ?? null;
  $role = strtolower($ctx['role'] ?? '');

  if (!$userId || !$role) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
  }

  // Carreras válidas
  $allowedCareers = ['ingenieria en sistemas', 'licenciatura en informatica'];
  $allowedRoles = ['student', 'teacher', 'coordinator', 'depthead', 'admin'];

  // Si el rol no está permitido, denegar
  if (!in_array($role, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Rol no autorizado para acceder a este módulo']);
    exit;
  }

  // Admins, docentes y coordinadores siempre pueden entrar
  if (in_array($role, ['admin', 'teacher', 'coordinator', 'depthead'])) {
    return true;
  }

  // Si es estudiante, verificar su carrera
  if ($role === 'student') {
    $stmt = $pdo->prepare("
      SELECT LOWER(TRIM(career)) AS career
      FROM students
      WHERE userId = ?
      LIMIT 1
    ");
    $stmt->execute([$userId]);
    $career = $stmt->fetchColumn();

    if (!$career) {
      http_response_code(403);
      echo json_encode(['error' => 'Estudiante no encontrado en el registro académico']);
      exit;
    }

    $career = str_replace(['í','Í','é','É'], ['i','i','e','e'], $career);
    if (!in_array($career, $allowedCareers)) {
      http_response_code(403);
      echo json_encode([
        'error' => 'Acceso restringido. Solo Ingeniería en Sistemas o Informática pueden acceder.'
      ]);
      exit;
    }

    return true;
  }

  // Si ninguna condición se cumple
  http_response_code(403);
  echo json_encode(['error' => 'Acceso denegado']);
  exit;
}
