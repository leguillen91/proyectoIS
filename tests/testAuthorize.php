<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/requireAuth.php';
require_once __DIR__ . '/../middleware/authorize.php';

// Reutilizamos el token del admin (ya tiene todos los permisos)
$cliToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsImVtYWlsIjoiYWRtaW5AdW5pc3lzLmxvY2FsIiwicm9sZSI6ImFkbWluIiwiaXNzIjoicHJvamVjdFVuYWhTaXN0ZW1zLmxvY2FsIiwiYXVkIjoicHJvamVjdFVuYWhTaXN0ZW1zLmNsaWVudCIsImlhdCI6MTc2MTM0NTU4NSwiZXhwIjoxNzYxMzUyNzg1LCJqdGkiOiIzZjkwM2NhZTY5MWMwZGIzIn0.BjaC9SMLlfSO3FnpqvUCdGXoOfpWg-b4YzNBYPelJxQ';

$ctx = requireAuth();

// Prueba 1: acceso permitido por rol admin
authorize($ctx, [], ['admin']);
echo json_encode(['test1' => '✅ Allowed by role', 'user' => $ctx['email']]);

// Prueba 2: acceso permitido por permiso
authorize($ctx, ['users.manage'], []);
echo json_encode(['test2' => '✅ Allowed by permission']);
