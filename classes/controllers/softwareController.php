<?php
// classes/controllers/softwareController.php
require_once __DIR__ . '/../services/softwareService.php';

class SoftwareController {
  private SoftwareService $service;

  public function __construct(PDO $pdo) {
    $this->service = new SoftwareService($pdo);
  }

  // Crear proyecto
  public function create(array $data): array {
    $id = $this->service->createProject($data);
    return ['ok' => true, 'projectId' => $id];
  }

  // Listar según rol
  public function list(array $ctx): array {
    return $this->service->listProjectsByContext($ctx['userId'], $ctx['role']);
  }

  // Detalles
  public function details(int $projectId): ?array {
    return $this->service->getProjectDetails($projectId);
  }

  // Actualizar estado
  public function updateStatus(int $projectId, string $status, int $userId): array {
    $ok = $this->service->updateProjectStatus($projectId, $status, $userId);
    return ['ok' => $ok];
  }
}
