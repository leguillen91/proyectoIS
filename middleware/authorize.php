<?php
// middleware/authorize.php

/**
 * Verifica si el usuario autenticado tiene acceso a una acción según:
 * - Permisos específicos, o
 * - Roles permitidos.
 *
 * Si no tiene autorización, devuelve un error 403 y detiene la ejecución.
 *
 * @param array $ctx Contexto del usuario (devuelto por requireAuth()).
 * @param array $neededAnyPermission Lista de permisos válidos para acceder.
 * @param array $allowedRoles Lista de roles válidos para acceder.
 */
function authorize(array $ctx, array $neededAnyPermission = [], array $allowedRoles = []): void {
  $isAuthorized = false;

  // Validar por rol
  if (!empty($allowedRoles) && in_array($ctx['role'], $allowedRoles, true)) {
    $isAuthorized = true;
  }

  // Validar por permisos (si no fue autorizado aún)
  if (!$isAuthorized && !empty($neededAnyPermission)) {
    $userPermissions = $ctx['permissions'] ?? [];
    foreach ($neededAnyPermission as $perm) {
      if (in_array($perm, $userPermissions, true)) {
        $isAuthorized = true;
        break;
      }
    }
  }

  if (!$isAuthorized) {
    http_response_code(403);
    echo json_encode([
      'error' => 'Access denied',
      'message' => 'You do not have permission to perform this action.'
    ]);
    exit;
  }
}
