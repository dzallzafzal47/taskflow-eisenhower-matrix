<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$task = [
    'id' => 0,
    'title' => '',
    'description' => '',
    'status' => 'todo',
    'priority' => 'medium',
    'quadrant' => 'schedule',
    'due_date' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        exit('Task not found.');
    }
    $task = $found;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'todo';
    $priority = $_POST['priority'] ?? 'medium';
    $quadrant = $_POST['quadrant'] ?? 'schedule';
    $dueDate = trim($_POST['due_date'] ?? '');

    $validStatuses = ['todo', 'doing', 'done'];
    $validPriorities = ['low', 'medium', 'high'];
    $validQuadrants = ['do', 'schedule', 'delegate', 'eliminate'];

    if ($title === '') {
        $error = 'Task title is required.';
    } elseif (!in_array($status, $validStatuses, true) || !in_array($priority, $validPriorities, true) || !in_array($quadrant, $validQuadrants, true)) {
        $error = 'Invalid task data.';
    } else {
        $dueDate = $dueDate !== '' ? $dueDate : null;

        if ($id > 0) {
            $oldQuadrant = $task['quadrant'];
            $oldStatus = $task['status'];

            $stmt = $pdo->prepare('UPDATE tasks SET title=:title, description=:description, status=:status, priority=:priority, quadrant=:quadrant, due_date=:due_date, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':quadrant' => $quadrant,
                ':due_date' => $dueDate,
                ':id' => $id,
            ]);

            addHistory($pdo, $id, 'Task updated', "Updated: {$title}");
            if ($oldQuadrant !== $quadrant) {
                addHistory($pdo, $id, 'Matrix changed', quadrantLabel($oldQuadrant) . ' → ' . quadrantLabel($quadrant));
            }
            if ($oldStatus !== $status) {
                addHistory($pdo, $id, 'Status changed', statusLabel($oldStatus) . ' → ' . statusLabel($status));
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO tasks (title, description, status, priority, quadrant, due_date) VALUES (:title,:description,:status,:priority,:quadrant,:due_date)');
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':quadrant' => $quadrant,
                ':due_date' => $dueDate,
            ]);
            $id = (int) $pdo->lastInsertId();
            addHistory($pdo, $id, 'Task created', "Created in " . quadrantLabel($quadrant) . ": {$title}");
        }

        redirect('index.php');
    }

    $task = compact('id', 'title', 'description', 'status', 'priority', 'quadrant');
    $task['due_date'] = $dueDate ?? '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $id > 0 ? 'Edit Task' : 'New Task' ?> — TaskFlow</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">TaskFlow</a>
        <nav><a href="index.php">Tasks</a><a href="matrix.php">Matrix</a><a href="history.php">History</a></nav>
    </div>
</header>

<main class="container narrow page-space">
    <a class="back-link" href="index.php">← Back to tasks</a>
    <section class="panel form-card">
        <p class="eyebrow">Task editor</p>
        <h1><?= $id > 0 ? 'Edit Task' : 'Create Task' ?></h1>
        <p class="subtext">Set the task details and choose where it belongs in the Eisenhower Matrix.</p>

        <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

        <form method="post" class="task-form">
            <label>
                <span>Title</span>
                <input type="text" name="title" maxlength="120" required value="<?= e($task['title']) ?>" placeholder="e.g. Finish landing page">
            </label>
            <label>
                <span>Description</span>
                <textarea name="description" rows="5" placeholder="Add notes or details..."><?= e(trim($task['description'])) ?></textarea>
            </label>

            <label>
                <span>Eisenhower Matrix</span>
                <select name="quadrant" class="matrix-select">
                    <option value="do" <?= $task['quadrant'] === 'do' ? 'selected' : '' ?>>Do — Urgent & Important</option>
                    <option value="schedule" <?= $task['quadrant'] === 'schedule' ? 'selected' : '' ?>>Decide / Schedule — Important, Not Urgent</option>
                    <option value="delegate" <?= $task['quadrant'] === 'delegate' ? 'selected' : '' ?>>Delegate — Urgent, Not Important</option>
                    <option value="eliminate" <?= $task['quadrant'] === 'eliminate' ? 'selected' : '' ?>>Eliminate — Not Urgent, Not Important</option>
                </select>
                <small class="field-help">Use the matrix to decide whether to do, schedule, delegate, or eliminate the task.</small>
            </label>

            <div class="form-grid">
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="todo" <?= $task['status'] === 'todo' ? 'selected' : '' ?>>To Do</option>
                        <option value="doing" <?= $task['status'] === 'doing' ? 'selected' : '' ?>>In Progress</option>
                        <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </label>
                <label>
                    <span>Priority</span>
                    <select name="priority">
                        <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                    </select>
                </label>
                <label>
                    <span>Due date</span>
                    <input type="date" name="due_date" value="<?= e((string) ($task['due_date'] ?? '')) ?>">
                </label>
            </div>
            <div class="form-actions">
                <button class="button primary" type="submit"><?= $id > 0 ? 'Save Changes' : 'Create Task' ?></button>
                <a class="button ghost" href="index.php">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
