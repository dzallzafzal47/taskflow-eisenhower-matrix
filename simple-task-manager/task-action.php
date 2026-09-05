<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect();
}

$pdo = db();
$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$returnPath = ($_POST['return'] ?? '') === 'matrix.php' ? 'matrix.php' : 'index.php';

$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = :id');
$stmt->execute([':id' => $id]);
$task = $stmt->fetch();

if (!$task) {
    redirect($returnPath);
}

if ($action === 'delete') {
    addHistory($pdo, $id, 'Task deleted', "Deleted: {$task['title']}");
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
} elseif ($action === 'cycle') {
    $next = match ($task['status']) {
        'todo' => 'doing',
        'doing' => 'done',
        default => 'todo',
    };
    $stmt = $pdo->prepare('UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([':status' => $next, ':id' => $id]);
    addHistory($pdo, $id, 'Status changed', statusLabel($task['status']) . ' → ' . statusLabel($next));
}

redirect($returnPath);
