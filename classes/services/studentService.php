<?php
// classes/services/studentService.php
require_once __DIR__ . '/../../classes/models/studentModel.php';

class StudentService {
  private StudentModel $studentModel;

  public function __construct(PDO $db) {
    $this->studentModel = new StudentModel($db);
  }

  /**
   * Listar todos los estudiantes
   * - Solo para admin y coordinator
   */
  public function listAll(): array {
    return $this->studentModel->getAll();
  }

  /**
   * Obtener información de un estudiante
   * - Admin y coordinator: por ID
   * - Student: por su propio userId
   */
  public function getStudent(int $studentId, ?int $userId = null, string $role = 'student'): ?array {
    if ($role === 'student' && $userId !== null) {
      return $this->studentModel->getByUserId($userId);
    }
    return $this->studentModel->getById($studentId);
  }

  /**
   * Crear un nuevo estudiante (solo admin o coordinator)
   */
  public function createStudent(
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
    return $this->studentModel->create(
      $userId,
      $enrollmentCode,
      $career,
      $academicCenter,
      $admissionYear,
      $currentPeriod,
      $status,
      $phoneNumber,
      $address
    );
  }

  /**
   * Actualizar información de estudiante
   * - Admin y coordinator pueden actualizar cualquiera.
   * - Student solo puede actualizar contacto (según permisos futuros).
   */
  public function updateStudent(
    int $studentId,
    string $career,
    string $academicCenter,
    string $currentPeriod,
    string $status,
    ?string $phoneNumber,
    ?string $address
  ): bool {
    return $this->studentModel->update(
      $studentId,
      $career,
      $academicCenter,
      $currentPeriod,
      $status,
      $phoneNumber,
      $address
    );
  }

  /**
   * Eliminar un estudiante (solo admin)
   */
  public function deleteStudent(int $studentId): bool {
    return $this->studentModel->delete($studentId);
  }
}
