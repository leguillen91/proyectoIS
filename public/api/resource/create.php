<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../classes/helpers/authorizeModuleAccess.php';
require_once __DIR__ . '/../../../classes/controllers/resourceController.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
  $ctx = requireAuth();

  // Detectar si viene FormData (con archivo) o JSON
  if (isset($_FILES['file'])) {
    $data = $_POST;
    $data['file'] = $_FILES['file'];
  } else {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) throw new Exception("Invalid JSON payload");
  }

  // Validar módulo
  $module = $data['module'] ?? null;
  if (!$module) throw new Exception("Module required");

  // Autorizar acceso
 authorizeModuleAccess((array)$ctx, $module);


  // Agregar ID del creador
  $data['createdByPersonId'] = $ctx['userId'] ?? null;

  // Crear recurso
  $controller = new ResourceController();
  ob_start();
  $controller->createResource($data);
  $output = ob_get_clean();

  // Intentar leer la respuesta del controlador
  $response = json_decode($output, true);
  if (!isset($response['ok']) || !$response['ok']) {
    echo $output;
    exit;
  }

  // Subir archivo (si se incluyó)
  if (!empty($data['file']) && isset($response['data']['idResource'])) {
    $resourceId = $response['data']['idResource'];
    $controller->uploadFile($resourceId, $data['file'], 'Primary');
  } else {
    echo $output; // devolver respuesta original
  }

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
