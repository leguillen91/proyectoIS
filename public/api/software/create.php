<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';
require_once __DIR__ . '/../../../classes/controllers/softwareController.php';

header('Content-Type: application/json');
$ctx = requireAuth();
authorizeSoftwareAccess($pdo, $ctx);

$controller = new SoftwareController($pdo);
echo json_encode($controller->create($ctx));
