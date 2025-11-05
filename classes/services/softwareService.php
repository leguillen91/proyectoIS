<?php
// classes/services/softwareService.php
require_once __DIR__ . '/../models/softwareModel.php';

class SoftwareService {
  private SoftwareModel $model;

  public function __construct(PDO $pdo) {
    $this->model = new SoftwareModel($pdo);
  }

  public function createProject(array $data): int {
    return $this->model->createProject($data);
  }

  public function listProjectsByContext(int $userId, string $role): array {
    return $this->model->listProjectsByContext($userId, $role);
  }

  public function getProjectDetails(int $projectId): ?array {
    return $this->model->getProjectDetails($projectId);
  }

  public function updateProjectStatus(int $projectId, string $status, int $changedBy): bool {
    return $this->model->updateProjectStatus($projectId, $status, $changedBy);
  }
}
