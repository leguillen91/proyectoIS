<?php
// models/userModel.php
require_once __DIR__ . '/../../config/connection.php';

class UserModel {
  private PDO $db;

  public function __construct(PDO $db) {
    $this->db = $db;
  }

  /**
   * Busca un usuario por correo electrónico.
   */
  public function findByEmail(string $email): ?array {
    $stmt = $this->db->prepare("
      SELECT u.*, r.roleName 
      FROM users u
      JOIN roles r ON r.id = u.roleId
      WHERE u.email = ?
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  /**
   * Busca un usuario por ID.
   */
  public function findById(int $id): ?array {
    $stmt = $this->db->prepare("
      SELECT u.*, r.roleName 
      FROM users u
      JOIN roles r ON r.id = u.roleId
      WHERE u.id = ?
      LIMIT 1
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  /**
   * Obtiene todos los permisos asociados a un rol.
   */
  public function getPermissionsByRoleId(int $roleId): array {
    $stmt = $this->db->prepare("
      SELECT p.permissionCode 
      FROM rolepermissions rp
      JOIN permissions p ON p.id = rp.permissionId
      WHERE rp.roleId = ?
    ");
    $stmt->execute([$roleId]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $permissions ?: [];
  }

  /**
   * Crea un nuevo usuario.
   */
  public function create(
    string $fullName,
    string $email,
    string $identityNumber,
    ?string $accountNumber,
    int $roleId,
    string $passwordHash,
    string $passwordSalt
  ): int {
    $stmt = $this->db->prepare("
      INSERT INTO users (fullName, email, identityNumber, accountNumber, roleId, passwordHash, passwordSalt)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fullName, $email, $identityNumber, $accountNumber, $roleId, $passwordHash, $passwordSalt]);
    return (int) $this->db->lastInsertId();
  }

  public function createTeacher($userId, $fullName, $employeeNumber, $career, $center)
  {
      $stmt = $this->db->prepare("
          INSERT INTO teachers (userId, fullName, employeeNumber, career, academicCenter)
          VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$userId, $fullName, $employeeNumber, $career, $center]);
      return $this->db->lastInsertId();
  }
}
