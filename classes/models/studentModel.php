<?php
// models/studentModel.php
require_once __DIR__ . '/../../config/connection.php';

class StudentModel {
  private PDO $db;

  public function __construct(PDO $db) {
    $this->db = $db;
  }

  // ============================================================
  //  LISTADO GENERAL
  // ============================================================

  /**
   * Obtener todos los estudiantes (vista consolidada).
   * Usado por administración, jefes y coordinadores.
   */
  public function getAll(): array {
    $stmt = $this->db->query("
      SELECT * 
      FROM view_students 
      ORDER BY studentId ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // ============================================================
  //  BÚSQUEDAS INDIVIDUALES
  // ============================================================

  /**
   * Obtener información detallada de un estudiante por ID.
   */
  public function getById(int $id): ?array {
    $stmt = $this->db->prepare("
      SELECT * 
      FROM view_students 
      WHERE studentId = ?
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  /**
   * Obtener datos de estudiante por ID de usuario.
   * Usado por rol "student" para cargar su propio perfil.
   */
  public function getByUserId(int $userId): ?array {
    $stmt = $this->db->prepare("
      SELECT * 
      FROM view_students 
      WHERE studentId = (
        SELECT id FROM students WHERE userId = ?
      )
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  // ============================================================
  //  CREAR NUEVO ESTUDIANTE
  // ============================================================

  /**
   * Crear un nuevo registro de estudiante.
   * Este método es usado cuando Admision genera el estudiante
   * y se crea automáticamente en esta tabla.
   */
  public function create(
    int $userId,
    string $enrollmentCode,
    string $career,
    string $academicCenter,
    int $admissionYear,
    string $currentPeriod,
    string $status,
    ?string $phoneNumber,
    ?string $address
  ): int {

    $stmt = $this->db->prepare("
      INSERT INTO students (
        userId, enrollmentCode, career, academicCenter, 
        admissionYear, currentPeriod, status, phoneNumber, address
      ) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
      $userId,
      $enrollmentCode,
      $career,
      $academicCenter,
      $admissionYear,
      $currentPeriod,
      $status,
      $phoneNumber,
      $address
    ]);

    return (int) $this->db->lastInsertId();
  }

  // ============================================================
  //  ACTUALIZAR ESTUDIANTE
  // ============================================================

  /**
   * Actualizar información del estudiante.
   * Usado principalmente por Administración o Coordinadores.
   */
  public function update(
    int $studentId,
    string $career,
    string $academicCenter,
    string $currentPeriod,
    string $status,
    ?string $phoneNumber,
    ?string $address
  ): bool {

    $stmt = $this->db->prepare("
      UPDATE students 
      SET career = ?, academicCenter = ?, currentPeriod = ?, status = ?, 
          phoneNumber = ?, address = ?
      WHERE id = ?
    ");

    return $stmt->execute([
      $career,
      $academicCenter,
      $currentPeriod,
      $status,
      $phoneNumber,
      $address,
      $studentId
    ]);
  }

  // ============================================================
  //  ELIMINAR ESTUDIANTE
  // ============================================================

  /**
   * Eliminar estudiante.
   * Solo debe utilizarse en administración (casos extremos).
   */
  public function delete(int $studentId): bool {
    $stmt = $this->db->prepare("
      DELETE FROM students 
      WHERE id = ?
    ");
    return $stmt->execute([$studentId]);
  }
}

