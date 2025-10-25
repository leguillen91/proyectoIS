<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorize.php';
require_once __DIR__ . '/../../../classes/controllers/authController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$ctx = requireAuth();
authorize($ctx, ['users.manage'], ['admin']);

$body = json_decode(file_get_contents('php://input'), true);

$fullName = trim($body['fullName'] ?? '');
$email = trim($body['email'] ?? '');
$identityNumber = trim($body['identityNumber'] ?? '');
$accountNumber = $body['accountNumber'] ?? null;
$roleName = trim($body['role'] ?? '');
$password = (string)($body['password'] ?? '');

if ($fullName === '' || $email === '' || $roleName === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Missing required fields']);
  exit;
}

$auth = new AuthController($pdo, $config);
$result = $auth->register($fullName, $email, $identityNumber, $accountNumber, $roleName, $password);

if (!$result['ok']) {
  http_response_code(400);
  echo json_encode(['error' => $result['error']]);
  exit;
}

echo json_encode([
  'ok' => true,
  'message' => 'User registered successfully',
  'userId' => $result['userId']
]);
