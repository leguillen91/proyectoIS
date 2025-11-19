<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

header('Content-Type: application/json');

try {
    $ctx = requireAuth();

    $userId = $ctx['userId'] ?? null;
    $role = strtolower($ctx['role'] ?? '');

    // Valores por defecto
    $career = null;
    $studentId = null;
    $careerId = null;
    $enrollmentCode = null;
    $academicIndex = null;
    if ($role === 'student') {

        // Obtener studentId, careerName y careerId directamente
        $stmt = $pdo->prepare("
            SELECT id, career, careerId,enrollmentCode, academicIndex
            FROM students
            WHERE userId = ?
        ");
        $stmt->execute([$userId]);
        $studentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentRow) {
            $studentId = $studentRow['id'];
            $career = $studentRow['career'];         // nombre textual
            $careerId = $studentRow['careerId'];
            $enrollmentCode = $studentRow['enrollmentCode']; // código de matrícula
            $academicIndex = $studentRow['academicIndex'];
        }

    } elseif (in_array($role, ['teacher', 'coordinator', 'depthead'])) {

        // Para docentes usamos solo careerName
        $stmt = $pdo->prepare("SELECT career FROM teachers WHERE userId = ?");
        $stmt->execute([$userId]);
        $career = $stmt->fetchColumn() ?: null;
    }

    echo json_encode([
        'ok' => true,
        'user' => [
            'userId' => $ctx['userId'],
            'studentId' => $studentId,
            'email' => $ctx['email'],
            'fullName' => $ctx['fullName'],
            'enrollmentCode' => $enrollmentCode,
            'role' => $ctx['role'],
            'career' => $career,
            'careerId' => $careerId,
            'academicIndex' => $academicIndex,
            'permissions' => $ctx['permissions'] ?? []
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
