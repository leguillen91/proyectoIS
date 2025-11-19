<?php
// Verificar que el usuario esté autenticado y devolver su contexto (ID, email, rol, permisos, carrera)

require_once __DIR__ . '/../config/connection.php';
$config = require __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/services/jwtService.php';
require_once __DIR__ . '/../classes/models/userModel.php';

/**
 * Obtiene el token JWT desde el encabezado Authorization o cookie.
 */
function getBearerToken(): ?string {
  // CLI (para pruebas sin servidor HTTP)
  if (isset($GLOBALS['cliToken']) && !empty($GLOBALS['cliToken'])) {
    return $GLOBALS['cliToken'];
  }

  // Token desde encabezado Authorization
  $headers = function_exists('getallheaders') ? getallheaders() : [];
  $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

  if (stripos($authHeader, 'Bearer ') === 0) {
    return trim(substr($authHeader, 7));
  }

  // Token desde cookie
  $cookieName = $GLOBALS['config']['cookies']['accessToken']['name'] ?? 'accessToken';
  return $_COOKIE[$cookieName] ?? null;
}

/**
 * Verifica el token JWT y retorna el contexto del usuario.
 */
function requireAuth(): array {
  global $pdo, $config;

  $jwtService = new JwtService($config['jwt']);
  $userModel  = new UserModel($pdo);

  $token = getBearerToken();
  if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Token faltante']);
    exit;
  }

  // ============================
  // Validar JWT
  // ============================
  try {
    $payload = $jwtService->verify($token);

    // Revisar si fue revocado
    if (!empty($payload['jti'])) {

      //  TABLA CORREGIDA: revokedtokens (todo minúsculas)
      $stmt = $pdo->prepare("SELECT id FROM revokedtokens WHERE jti = ? LIMIT 1");
      $stmt->execute([$payload['jti']]);

      if ($stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['error' => 'Token revocado']);
        exit;
      }
    }
  } catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
  }

  // ============================
  // Buscar usuario
  // ============================
  $user = $userModel->findById((int)$payload['sub']);
  if (!$user || (int)$user['status'] !== 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no encontrado o inactivo']);
    exit;
  }

  $userId = (int)$user['id'];
  $role   = strtolower($user['roleName']);

  // ============================
  // Obtener permisos del rol
  // ============================
  $permissions = $userModel->getPermissionsByRoleId((int)$user['roleId']);

  // ============================
  // Obtener carrera según rol
  // ============================
  $career = null;
  $studentId = null;
  $careerId = null;
  $enrollmentCode= null;
  $academicIndex = null;

  try {
    if ($role === 'student') {

      // Estas tablas ya están en minúsculas en el servidor y están correctas
      $stmt = $pdo->prepare("SELECT career FROM students WHERE userId = ?");
      $stmt->execute([$userId]);
      $career = $stmt->fetchColumn() ?: null;

      $stmt = $pdo->prepare("SELECT id FROM students WHERE userId = ?");
      $stmt->execute([$userId]);
      $studentId = $stmt->fetchColumn();

      $stmt = $pdo->prepare("SELECT careerId FROM students WHERE userId = ?");
      $stmt->execute([$userId]);
      $careerId = $stmt->fetchColumn();

      $stmt = $pdo->prepare("SELECT enrollmentCode FROM students WHERE userId = ?");
      $stmt->execute([$userId]);
      $enrollmentCode = $stmt->fetchColumn();

      $stmt = $pdo->prepare("SELECT academicIndex FROM students WHERE userId = ?");
      $stmt->execute([$userId]);
      $academicIndex = $stmt->fetchColumn();

    } elseif (in_array($role, ['teacher','coordinator','depthead'])) {

      $stmt = $pdo->prepare("SELECT career FROM teachers WHERE userId = ?");
      $stmt->execute([$userId]);
      $career = $stmt->fetchColumn() ?: null;
    }

  } catch (Exception $e) {
    $career = null;
  }

  // ============================
  // Retornar contexto completo
  // ============================
  return [
    'userId'     => $userId,
    'studentId'  => $studentId,
    'email'      => $user['email'],
    'fullName'   => $user['fullName'],
    'enrollmentCode' => $enrollmentCode,
    'role'       => $user['roleName'],
    'career'     => $career,
    'careerId'   => $careerId,
    'academicIndex' => $academicIndex,
    'permissions'=> $permissions
  ];
}
