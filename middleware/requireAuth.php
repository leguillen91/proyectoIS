<?php
//“Verificar que el usuario esté autenticado y devolver su contexto (ID, email, rol, permisos).”

require_once __DIR__ . '/../config/connection.php';
$config = require __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/services/jwtService.php';
require_once __DIR__ . '/../classes/models/userModel.php';

/**
 * Obtiene el token JWT desde el encabezado Authorization o desde la cookie.
 */
function getBearerToken(): ?string {
  // CLI fallback (para pruebas en consola sin servidor HTTP)
  if (isset($GLOBALS['cliToken']) && !empty($GLOBALS['cliToken'])) {
    return $GLOBALS['cliToken'];
  }

  // Si se ejecuta en servidor HTTP (Apache, php -S, etc.)
  $headers = function_exists('getallheaders') ? getallheaders() : [];
  $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

  if (stripos($authHeader, 'Bearer ') === 0) {
    return trim(substr($authHeader, 7));
  }

  $cookieName = $GLOBALS['config']['cookies']['accessToken']['name'] ?? 'accessToken';
  return $_COOKIE[$cookieName] ?? null;
}

/**
 * Verifica el token JWT y retorna el contexto del usuario autenticado.
 */
function requireAuth(): array {
  global $pdo, $config;

  $jwtService = new JwtService($config['jwt']);
  $userModel = new UserModel($pdo);
  $token = getBearerToken();

  if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Token Faltante']);
    exit;
  }

  try {
    $payload = $jwtService->verify($token);
    // Validar si el token está revocado
    if (!empty($payload['jti'])) {
      $stmt = $pdo->prepare("SELECT id FROM revokedTokens WHERE jti = ? LIMIT 1");
      $stmt->execute([$payload['jti']]);
      if ($stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['error' => 'Token revocado']);
        exit;
      }
    }
  } catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalido o expirado', 'detail' => $e->getMessage()]);
    exit;
  }
  //El token se verifica y revoca en cada logout para impedir que un token antiguo o robado siga siendo válido

  // Buscar usuario por ID del token
  $user = $userModel->findById((int)$payload['sub']);
  if (!$user || (int)$user['status'] !== 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no encontrado o inactivo']);
    exit;
  }

  // Obtener permisos del rol
  $permissions = $userModel->getPermissionsByRoleId((int)$user['roleId']);

  return [
    'userId' => (int)$user['id'],
    'email' => $user['email'],
    'fullName' => $user['fullName'],
    'role' => $user['roleName'],
    'permissions' => $permissions
  ];
}
