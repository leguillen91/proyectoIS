<?php
// controllers/studentController.php

require_once __DIR__ . '/../../bootstrap/init.php';
require_once __DIR__ . '/../../middleware/requireAuth.php';
require_once __DIR__ . '/../../middleware/authorize.php';
require_once __DIR__ . '/../../classes/services/studentService.php';

class StudentController {
  private StudentService $studentService;

  public function __construct(PDO $db) {
    $this->studentService = new StudentService($db);
  }

  /**
   * Obtener lista de estudiantes
   * - Admin y coordinator
   */
  public function listAll(array $ctx): void {
    authorize($ctx, ['students.view'], ['admin', 'coordinator']);

    $students = $this->studentService->listAll();
    echo json_encode(['ok' => true, 'students' => $students]);
  }

  /**
   * Obtener información de un estudiante
   * - Admin y coordinator: por ID
   * - Student: por su propio userId
   */
  public function getOne(array $ctx, ?int $studentId = null): void {
    $role = $ctx['role'];
    $userId = $ctx['userId'];

    authorize($ctx, ['students.view'], ['admin', 'coordinator', 'student']);

    $student = $this->studentService->getStudent($studentId ?? $userId, $userId, $role);

    if (!$student) {
      http_response_code(404);
      echo json_encode(['error' => 'Student not found']);
      return;
    }

    echo json_encode(['ok' => true, 'student' => $student]);
  }

  /**
   * Crear nuevo estudiante
   * - Admin o coordinator
   */
  public function create(array $ctx, array $data): void {
    authorize($ctx, ['students.create'], ['admin', 'coordinator']);

    $required = ['userId', 'enrollmentCode', 'career', 'academicCenter', 'admissionYear'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        return;
      }
    }

    $id = $this->studentService->createStudent(
      (int)$data['userId'],
      $data['enrollmentCode'],
      $data['career'],
      $data['academicCenter'],
      (int)$data['admissionYear'],
      $data['currentPeriod'] ?? '2025-I',
      $data['status'] ?? 'Activo',
      $data['phoneNumber'] ?? null,
      $data['address'] ?? null
    );

    echo json_encode(['ok' => true, 'studentId' => $id]);
  }

  /**
   * Actualizar estudiante
   * - Admin y coordinator pueden actualizar cualquiera
   * - Student solo su propio contacto
   */
  public function update(array $ctx, int $studentId, array $data): void {
    $role = $ctx['role'];

    if ($role === 'student') {
      authorize($ctx, ['students.update'], ['student']);
      $allowed = ['phoneNumber', 'address'];
      $data = array_intersect_key($data, array_flip($allowed));
    } else {
      authorize($ctx, ['students.update'], ['admin', 'coordinator']);
    }

    $success = $this->studentService->updateStudent(
      $studentId,
      $data['career'] ?? '',
      $data['academicCenter'] ?? '',
      $data['currentPeriod'] ?? '',
      $data['status'] ?? '',
      $data['phoneNumber'] ?? null,
      $data['address'] ?? null
    );

    if (!$success) {
      http_response_code(400);
      echo json_encode(['error' => 'Update failed']);
      return;
    }

    echo json_encode(['ok' => true, 'message' => 'Student updated successfully']);
  }

  /**
   * Eliminar estudiante
   * - Solo admin
   */
  public function delete(array $ctx, int $studentId): void {
    authorize($ctx, ['students.delete'], ['admin']);

    $deleted = $this->studentService->deleteStudent($studentId);

    if (!$deleted) {
      http_response_code(400);
      echo json_encode(['error' => 'Delete failed']);
      return;
    }

    echo json_encode(['ok' => true, 'message' => 'Student deleted successfully']);
  }
}
