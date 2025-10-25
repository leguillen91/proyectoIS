<?php
header('Content-Type: application/json');

// Aquí agrego el Token JWT completo (sin saltos de línea)
$cliToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsImVtYWlsIjoiYWRtaW5AdW5pc3lzLmxvY2FsIiwicm9sZSI6ImFkbWluIiwiaXNzIjoicHJvamVjdFVuYWhTaXN0ZW1zLmxvY2FsIiwiYXVkIjoicHJvamVjdFVuYWhTaXN0ZW1zLmNsaWVudCIsImlhdCI6MTc2MTM0NTU4NSwiZXhwIjoxNzYxMzUyNzg1LCJqdGkiOiIzZjkwM2NhZTY5MWMwZGIzIn0.BjaC9SMLlfSO3FnpqvUCdGXoOfpWg-b4YzNBYPelJxQ';

// añado la ruta al middleware
require_once __DIR__ . '/../middleware/requireAuth.php';

$ctx = requireAuth();
echo json_encode(['ok' => true, 'context' => $ctx], JSON_PRETTY_PRINT);
