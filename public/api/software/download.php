<?php
// public/api/software/download.php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';
require_once __DIR__ . '/../../../middleware/authorizeSoftwareAccess.php';

try {
  $ctx = requireAuth();
  authorizeSoftwareAccess($pdo, $ctx);

  $path = $_GET['path'] ?? null;
  if (!$path) {
    http_response_code(400);
    echo "Ruta no proporcionada.";
    exit;
  }

  // Normalizamos path (ej: "12/archivo.php")
  $path = str_replace(['\\'], '/', $path);
  $path = ltrim($path, '/'); // evitar rutas absolutas
  if (preg_match('/\.\./', $path)) {
    http_response_code(400);
    echo "Ruta inválida.";
    exit;
  }

  $root = dirname(__DIR__, 3);
  $base = realpath($root . '/storage/software');
  $file = realpath($base . DIRECTORY_SEPARATOR . $path);

  if (!$base || !$file || strpos($file, $base) !== 0 || !file_exists($file)) {
    http_response_code(404);
    echo "Archivo no encontrado.";
    exit;
  }

  $filename = basename($file);
  $mime = function_exists('mime_content_type') ? @mime_content_type($file) : null;
  if (!$mime && function_exists('finfo_open')) {
    $f = @finfo_open(FILEINFO_MIME_TYPE);
    if ($f) {
      $mime = @finfo_file($f, $file);
      @finfo_close($f);
    }
  }
  if (!$mime) $mime = 'application/octet-stream';

  header('Content-Description: File Transfer');
  header('Content-Type: ' . $mime);
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . filesize($file));
  readfile($file);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Error: " . $e->getMessage();
}
