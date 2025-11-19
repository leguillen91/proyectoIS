<?php
function normalizeCareer($text) {
  $text = strtolower(trim($text));
  $map = [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
    'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
    'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
    'ñ'=>'n','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
    'À'=>'a','È'=>'e','Ì'=>'i','Ò'=>'o','Ù'=>'u',
    'Ä'=>'a','Ë'=>'e','Ï'=>'i','Ö'=>'o','Ü'=>'u','Ñ'=>'n'
  ];
  return strtr($text, $map);
}

function authorizeModuleAccess($ctx, $module) {
    
  $role = strtolower($ctx['role'] ?? '');
  $career = normalizeCareer($ctx['career'] ?? '');

  // Roles permitidos globalmente
  $allowedRoles = ['student', 'teacher', 'coordinator', 'depthead', 'admin'];

  if (!in_array($role, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Access denied: role not permitted']);
    exit;
  }

  // Admin siempre tiene acceso
  if ($role === 'admin') return;

  $allowed = false;

  switch (strtolower($module)) {

    case 'registration': // MATRÍCULA
      // ✔ Todas las carreras tienen acceso
      $allowed = true;
      break;

    case 'software':
      $allowed = in_array($career, ['ingenieria en sistemas', 'licenciatura en informatica']);
      break;

    case 'music':
      $allowed = ($career === 'musica');
      break;

    case 'library':
      // Biblioteca virtual es general
      $allowed = true;
      break;

    default:
      // Si no se reconoce el módulo → bloqueo
      $allowed = false;
  }

  if (!$allowed) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => "Access denied: career mismatch ({$career})"]);
    exit;
  }
}
