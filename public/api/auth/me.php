<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

$ctx = requireAuth();
echo json_encode(['ok' => true, 'user' => $ctx]);
