<?php

$config = require __DIR__ . '/env.php';

$dsn = sprintf(
  'mysql:host=%s;port=%d;dbname=%s;charset=%s',
  $config['db']['host'],
  $config['db']['port'],
  $config['db']['name'],
  $config['db']['charset']
);

try {
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Database connection failed',
    'detail' => $e->getMessage()
  ]);
  exit;
}
