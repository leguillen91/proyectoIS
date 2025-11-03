<?php
// models/studentModel.php
require_once __DIR__ . '/../../config/connection.php';

class StudentModel {
  private PDO $db;

  public function __construct(PDO $db) {
    $this->db = $db;
  }

  /**
   * Obtener todos los estudiantes (para admin o coordinator)
   */
  public function getAll(): array {
    $stmt = $this->db->query("SELECT * FROM view_students ORDER BY studentId ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Obtener información de un estudiante por su ID
   */
  public function getById(int $id): ?array {
    $stmt = $this->db->prepare("SELECT * FROM view_students WHERE studentId = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  /**
   * Obtener información de un estudiante por ID de usuario (para 'student')
   */
  public function getByUserId(int $userId): ?array {
    $stmt = $this->db->prepare("SELECT * FROM view_students WHERE studentId = (SELECT id FROM students WHERE userId = ?)");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  /**
   * Crear un nuevo registro de estudiante
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
        userId, enrollmentCode, career, academicCenter, admissionYear, currentPeriod, status, phoneNumber, address
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $enrollmentCode, $career, $academicCenter, $admissionYear, $currentPeriod, $status, $phoneNumber, $address]);
    return (int) $this->db->lastInsertId();
  }

  /**
   * Actualizar información de estudiante
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
      SET career = ?, academicCenter = ?, currentPeriod = ?, status = ?, phoneNumber = ?, address = ?
      WHERE id = ?
    ");
    return $stmt->execute([$career, $academicCenter, $currentPeriod, $status, $phoneNumber, $address, $studentId]);
  }

  /**
   * Eliminar estudiante (solo admin)
   */
  public function delete(int $studentId): bool {
    $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
    return $stmt->execute([$studentId]);
  }
}
