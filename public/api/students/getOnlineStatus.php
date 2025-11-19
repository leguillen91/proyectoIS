<?php
require_once __DIR__ . '/../../../bootstrap/init.php';
require_once __DIR__ . '/../../../middleware/requireAuth.php';

header("Content-Type: application/json");

try {
    $ctx = requireAuth();
    $userId = $ctx['userId'];

    if (!isset($_GET['id'])) {
        echo json_encode(['ok' => false, 'error' => 'Missing id']);
        exit;
    }

    $contactId = intval($_GET['id']);

    // Obtener PDO
    global $pdo;

    // Buscar estado online del estudiante
    $stmt = $pdo->prepare("SELECT isOnline, lastSeen FROM students WHERE id = ?");
    $stmt->execute([$contactId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Student not found']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'isOnline' => intval($row['isOnline']),
        'lastSeen' => $row['lastSeen']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
