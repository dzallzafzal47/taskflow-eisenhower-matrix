<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$rows = $pdo->query("SELECT * FROM tasks ORDER BY CASE status WHEN 'doing' THEN 1 WHEN 'todo' THEN 2 ELSE 3 END, due_date IS NULL, due_date ASC, id DESC")->fetchAll();

$matrix = [
    'do' => [],
    'schedule' => [],
    'delegate' => [],
    'eliminate' => [],
];

foreach ($rows as $task) {
    $key = $task['quadrant'] ?? 'schedule';
    if (!isset($matrix[$key])) {
        $key = 'schedule';
    }
    $matrix[$key][] = $task;
}

$quadrants = [
    'do' => ['title' => 'Do', 'axis' => 'Urgent + Important', 'note' => 'Do it immediately.'],
    'schedule' => ['title' => 'Decide', 'axis' => 'Not Urgent + Important', 'note' => 'Schedule a time to do it.'],
    'delegate' => ['title' => 'Delegate', 'axis' => 'Urgent + Not Important', 'note' => 'Assign it to someone else.'],
    'eliminate' => ['title' => 'Eliminate', 'axis' => 'Not Urgent + Not Important', 'note' => 'Remove or reduce the task.'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eisenhower Matrix — TaskFlow</title>
    <meta name="description" content="Eisenhower Matrix view for TaskFlow.">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">TaskFlow</a>
        <nav><a href="index.php">Tasks</a><a class="active" href="matrix.php">Matrix</a><a href="history.php">History</a></nav>
    </div>
</header>

<main class="container page-space matrix-page">
    <section class="matrix-hero">
        <div>
            <p class="eyebrow">Priority framework</p>
            <h1>Eisenhower Matrix</h1>
            <p class="subtext">Organize work by urgency and importance. The fourth quadrant means eliminate from your attention — it does not permanently delete the database record.</p>
        </div>
        <a class="button primary" href="task-form.php">+ New Task</a>
    </section>

    <div class="matrix-axis-top" aria-hidden="true">
        <span>Urgent</span>
        <span>Not Urgent</span>
    </div>

    <section class="eisenhower-shell">
        <div class="matrix-axis-side" aria-hidden="true">
            <span>Important</span>
            <span>Not Important</span>
        </div>

        <div class="eisenhower-grid">
            <?php foreach ($quadrants as $key => $config): ?>
                <section class="matrix-quadrant matrix-<?= e($key) ?>" id="<?= e($key) ?>">
                    <header class="quadrant-header">
                        <div>
                            <p><?= e($config['axis']) ?></p>
                            <h2><?= e($config['title']) ?></h2>
                            <small><?= e($config['note']) ?></small>
                        </div>
                        <strong><?= count($matrix[$key]) ?></strong>
                    </header>

                    <div class="quadrant-task-list">
                        <?php if (!$matrix[$key]): ?>
                            <div class="quadrant-empty">No tasks in this quadrant.</div>
                        <?php else: ?>
                            <?php foreach ($matrix[$key] as $task): ?>
                                <article class="matrix-task <?= $task['status'] === 'done' ? 'is-done' : '' ?>">
                                    <div class="matrix-task-top">
                                        <span class="badge status-<?= e($task['status']) ?>"><?= e(statusLabel($task['status'])) ?></span>
                                        <span class="badge priority-<?= e($task['priority']) ?>"><?= e(priorityLabel($task['priority'])) ?></span>
                                    </div>
                                    <h3><?= e($task['title']) ?></h3>
                                    <?php if ($task['due_date']): ?>
                                        <p>Due <?= e(date('M j, Y', strtotime($task['due_date']))) ?></p>
                                    <?php elseif ($task['description'] !== ''): ?>
                                        <p><?= e(strlen($task['description']) > 90 ? substr($task['description'], 0, 87) . '...' : $task['description']) ?></p>
                                    <?php endif; ?>
                                    <div class="matrix-task-actions">
                                        <a href="task-form.php?id=<?= (int) $task['id'] ?>">Edit</a>
                                        <form action="task-action.php" method="post">
                                            <input type="hidden" name="action" value="cycle">
                                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                            <input type="hidden" name="return" value="matrix.php">
                                            <button type="submit">Next status</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer>
    <div class="container">TaskFlow · Eisenhower Matrix · PHP + SQLite</div>
</footer>
</body>
</html>
