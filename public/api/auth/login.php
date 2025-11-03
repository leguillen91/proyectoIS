<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../classes/controllers/authController.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Metodo no permitido']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

$auth = new AuthController($pdo, $config);
$result = $auth->login($email, $password);

if (!$result['ok']) {
  http_response_code(401);
  echo json_encode(['error' => $result['error']]);
  exit;
}

$cookieCfg = $config['cookies']['accessToken'];
setcookie(
  $cookieCfg['name'],
  $result['token'],
  [
    'expires' => time() + $config['jwt']['expiresIn'],
    'path' => $cookieCfg['path'],
    'secure' => $cookieCfg['secure'],
    'httponly' => $cookieCfg['httpOnly'],
    'samesite' => $cookieCfg['sameSite']
  ]
);

echo json_encode(['user' => $result['user'], 'token' => $result['token']]);
