<?php

require_once __DIR__ . '/../../config/connection.php';
$config = require __DIR__ . '/../../config/env.php';

require_once __DIR__ . '/../models/userModel.php';
require_once __DIR__ . '/../services/jwtService.php';
require_once __DIR__ . '/../services/authService.php';

class AuthController {
  private PDO $db;
  private UserModel $userModel;
  private JwtService $jwtService;
  private AuthService $authService;

  public function __construct(PDO $db, array $config) {
    $this->db = $db;
    $this->userModel = new UserModel($db);
    $this->jwtService = new JwtService($config['jwt']);
    $this->authService = new AuthService($config['security']['pepper']);
  }

  /**
   * Inicia sesión y genera un token JWT.
   */
  public function login(string $email, string $password): array {
    $user = $this->userModel->findByEmail($email);

    if (!$user || (int)$user['status'] !== 1) {
      return ['ok' => false, 'error' => 'Invalid credentials'];
    }

    $valid = $this->authService->verifyPassword($password, $user['passwordSalt'], $user['passwordHash']);
    if (!$valid) {
      return ['ok' => false, 'error' => 'Invalid credentials'];
    }

    $permissions = $this->userModel->getPermissionsByRoleId((int)$user['roleId']);
    $claims = [
      'sub' => (int)$user['id'],
      'email' => $user['email'],
      'role' => $user['roleName'],
      'perms' => $permissions
    ];

    $token = $this->jwtService->issue($claims, bin2hex(random_bytes(16)));

    return [
      'ok' => true,
      'token' => $token,
      'user' => [
        'id' => (int)$user['id'],
        'fullName' => $user['fullName'],
        'email' => $user['email'],
        'role' => $user['roleName'],
        'permissions' => $permissions
      ]
    ];
  }

  /**
   * Registra un nuevo usuario (solo admin o roles con permiso users.manage)
   */
  public function register(
    string $fullName,
    string $email,
    ?string $identityNumber,
    ?string $accountNumber,
    string $roleName,
    string $password
  ): array {
    $stmt = $this->db->prepare("SELECT id FROM roles WHERE roleName = ?");
    $stmt->execute([$roleName]);
    $role = $stmt->fetch();

    if (!$role) {
      return ['ok' => false, 'error' => 'Invalid role'];
    }

    $salt = $this->authService->generateSalt();
    $hash = $this->authService->hashPassword($password, $salt);

    try {
      $userId = $this->userModel->create($fullName, $email, $identityNumber ?? '', $accountNumber, (int)$role['id'], $hash, $salt);
      return ['ok' => true, 'userId' => $userId];
    } catch (Exception $e) {
      return ['ok' => false, 'error' => 'Email already registered'];
    }
  }
}
