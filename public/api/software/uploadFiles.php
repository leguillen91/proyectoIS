<?php
// public/api/software/uploadFiles.php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';

header('Content-Type: application/json');

function detectMime($path) {
  if (function_exists('mime_content_type')) {
    $m = @mime_content_type($path);
    if ($m) return $m;
  }
  if (function_exists('finfo_open')) {
    $f = @finfo_open(FILEINFO_MIME_TYPE);
    if ($f) {
      $m = @finfo_file($f, $path);
      @finfo_close($f);
      if ($m) return $m;
    }
  }
  return 'application/octet-stream';
}

// Normaliza nombre de archivo (sin caracteres raros)
function safeName($name) {
  $name = str_replace(['\\','/'], '_', $name);
  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
  return $name;
}

try {
  $ctx = requireAuth();
  authorizeSoftwareAccess($pdo, $ctx);

  // --- Validar projectId
  $projectId = isset($_POST['projectId']) ? (int) $_POST['projectId'] : 0;
  if ($projectId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'projectId requerido']);
    exit;
  }

  // --- Carpeta destino: <root>/storage/software/{projectId}
  $root = dirname(__DIR__, 3); // sube desde /public/api/software -> raíz del proyecto
  $uploadBase = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'software';
  $folder = $uploadBase . DIRECTORY_SEPARATOR . $projectId;

  if (!is_dir($uploadBase)) {
    if (!mkdir($uploadBase, 0777, true)) {
      throw new Exception("No se pudo crear el directorio base de almacenamiento.");
    }
  }
  if (!is_dir($folder)) {
    if (!mkdir($folder, 0777, true)) {
      throw new Exception("No se pudo crear la carpeta del proyecto.");
    }
  }

  // --- Lista blanca de extensiones
  $allowed = ['php','py','jar','java','c','cpp','js','css','html','jsp','class','7z','md'];

  $uploaded = 0;
  $readmeSaved = false;

  // Helper para insertar fila en softwareFiles
  $insertFileRow = function($filePathOnDisk, $originalName) use ($pdo, $ctx, $projectId) {
    $fileType = detectMime($filePathOnDisk);
    $fileSize = filesize($filePathOnDisk);
    $filePathDb = $projectId . '/' . basename($filePathOnDisk); // SIEMPRE con barra normal y sólo {id}/archivo

    $stmt = $pdo->prepare("
      INSERT INTO softwareFiles (projectId, fileName, filePath, fileSize, fileType, uploadedBy)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $projectId,
      $originalName,
      $filePathDb,
      $fileSize,
      $fileType,
      $ctx['userId']
    ]);
  };

  // --- 1) Procesar README (campo 'readme' individual)
  if (!empty($_FILES['readme']) && is_uploaded_file($_FILES['readme']['tmp_name'])) {
    $original = $_FILES['readme']['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
      throw new Exception("Extensión no permitida para README: .$ext");
    }

    // Aceptamos README.md aunque el front valide el nombre exacto
    $destName = safeName($original);
    $dest = $folder . DIRECTORY_SEPARATOR . $destName;

    if (!move_uploaded_file($_FILES['readme']['tmp_name'], $dest)) {
      throw new Exception("No se pudo mover el archivo README al destino.");
    }

    // Guardar contenido en softwareProjects.readmeText
    $content = @file_get_contents($dest);
    if ($content !== false) {
      $up = $pdo->prepare("UPDATE softwareProjects SET readmeText = ? WHERE id = ?");
      $up->execute([$content, $projectId]);
      $readmeSaved = true;
    }

    // Insertar fila en softwareFiles
    $insertFileRow($dest, $original);
    $uploaded++;
  }

  // --- 2) Procesar múltiples archivos (campo 'files[]')
  if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
    $count = count($_FILES['files']['name']);
    for ($i = 0; $i < $count; $i++) {
      if (empty($_FILES['files']['tmp_name'][$i]) || !is_uploaded_file($_FILES['files']['tmp_name'][$i])) {
        continue;
      }
      $original = $_FILES['files']['name'][$i];
      $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed)) {
        throw new Exception("Archivo no permitido: $original");
      }

      $destName = safeName($original);
      $dest = $folder . DIRECTORY_SEPARATOR . $destName;

      if (!move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
        throw new Exception("No se pudo mover el archivo: $original");
      }

      // Si alguno es README.md y aún no se guardó el texto, lo guardamos
      if (!$readmeSaved && strtolower($original) === 'readme.md') {
        $content = @file_get_contents($dest);
        if ($content !== false) {
          $up = $pdo->prepare("UPDATE softwareProjects SET readmeText = ? WHERE id = ?");
          $up->execute([$content, $projectId]);
          $readmeSaved = true;
        }
      }

      // Insertar fila
      $insertFileRow($dest, $original);
      $uploaded++;
    }
  }

  echo json_encode([
    'ok' => true,
    'uploaded' => $uploaded,
    'readmeSaved' => $readmeSaved,
    'folder' => "storage/software/$projectId"
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
