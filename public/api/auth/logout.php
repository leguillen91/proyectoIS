<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
// Validar token
$ctx = requireAuth();
$token = getBearerToken();

// Revocar token actual
try {
  $jwtService = new JwtService($config['jwt']);
  $payload = $jwtService->verify($token);

  if (!empty($payload['jti'])) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO revokedTokens (jti) VALUES (?)");
    $stmt->execute([$payload['jti']]);
  }
} catch (Exception $e) {
  // Si el token ya está expirado o da error, no hacemos nada
}

//  Limpiar la cookie del token
$cookieCfg = $config['cookies']['accessToken'];

setcookie(
  $cookieCfg['name'],
  '',
  [
    'expires' => time() - 3600,
    'path' => $cookieCfg['path'],
    'secure' => $cookieCfg['secure'],
    'httponly' => $cookieCfg['httpOnly'],
    'samesite' => $cookieCfg['sameSite']
  ]
);


// Respuesta
echo json_encode([
  'ok' => true,
  'message' => 'Sesión cerrada con éxito',
  'user' => $ctx['email']
]);
