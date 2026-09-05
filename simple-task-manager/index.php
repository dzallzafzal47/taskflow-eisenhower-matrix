<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$quadrant = $_GET['quadrant'] ?? 'all';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(title LIKE :q OR description LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

if (in_array($status, ['todo', 'doing', 'done'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

if (in_array($priority, ['low', 'medium', 'high'], true)) {
    $where[] = 'priority = :priority';
    $params[':priority'] = $priority;
}

if (in_array($quadrant, ['do', 'schedule', 'delegate', 'eliminate'], true)) {
    $where[] = 'quadrant = :quadrant';
    $params[':quadrant'] = $quadrant;
}

$sql = 'SELECT * FROM tasks';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY CASE quadrant WHEN 'do' THEN 1 WHEN 'schedule' THEN 2 WHEN 'delegate' THEN 3 ELSE 4 END, CASE status WHEN 'doing' THEN 1 WHEN 'todo' THEN 2 ELSE 3 END, CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, due_date IS NULL, due_date ASC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$counts = ['todo' => 0, 'doing' => 0, 'done' => 0, 'total' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM tasks GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['total'];
    $counts['total'] += (int) $row['total'];
}

$quadrantCounts = ['do' => 0, 'schedule' => 0, 'delegate' => 0, 'eliminate' => 0];
foreach ($pdo->query('SELECT quadrant, COUNT(*) AS total FROM tasks GROUP BY quadrant') as $row) {
    if (isset($quadrantCounts[$row['quadrant']])) {
        $quadrantCounts[$row['quadrant']] = (int) $row['total'];
    }
}

$recentHistory = $pdo->query('SELECT h.*, t.title FROM task_history h LEFT JOIN tasks t ON t.id = h.task_id ORDER BY h.id DESC LIMIT 8')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow — Eisenhower Task Manager</title>
    <meta name="description" content="A simple task management web app with SQLite, activity history, and the Eisenhower Matrix.">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">TaskFlow</a>
        <nav>
            <a class="active" href="index.php">Tasks</a>
            <a href="matrix.php">Matrix</a>
            <a href="history.php">History</a>
        </nav>
    </div>
</header>

<main class="container page-space">
    <section class="hero">
        <div>
            <p class="eyebrow">Simple productivity</p>
            <h1>Decide what deserves your attention.</h1>
            <p class="subtext">Manage tasks with SQLite, activity history, and the Eisenhower Matrix for urgency and importance.</p>
        </div>
        <a class="button primary" href="task-form.php">+ New Task</a>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><span>Total</span><strong><?= $counts['total'] ?></strong></article>
        <article class="stat-card"><span>To Do</span><strong><?= $counts['todo'] ?></strong></article>
        <article class="stat-card"><span>In Progress</span><strong><?= $counts['doing'] ?></strong></article>
        <article class="stat-card"><span>Done</span><strong><?= $counts['done'] ?></strong></article>
    </section>

    <section class="matrix-mini-grid" aria-label="Eisenhower Matrix overview">
        <?php foreach (['do', 'schedule', 'delegate', 'eliminate'] as $item): ?>
            <a class="matrix-mini-card matrix-<?= e($item) ?>" href="matrix.php#<?= e($item) ?>">
                <div>
                    <span><?= e(quadrantLabel($item)) ?></span>
                    <small><?= e(quadrantDescription($item)) ?></small>
                </div>
                <strong><?= $quadrantCounts[$item] ?></strong>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="panel filters-panel">
        <form method="get" class="filters filters-wide">
            <label class="search-field">
                <span>Search</span>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search tasks...">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="all">All</option>
                    <option value="todo" <?= $status === 'todo' ? 'selected' : '' ?>>To Do</option>
                    <option value="doing" <?= $status === 'doing' ? 'selected' : '' ?>>In Progress</option>
                    <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Done</option>
                </select>
            </label>
            <label>
                <span>Priority</span>
                <select name="priority">
                    <option value="all">All</option>
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                </select>
            </label>
            <label>
                <span>Matrix</span>
                <select name="quadrant">
                    <option value="all">All</option>
                    <option value="do" <?= $quadrant === 'do' ? 'selected' : '' ?>>Do</option>
                    <option value="schedule" <?= $quadrant === 'schedule' ? 'selected' : '' ?>>Decide</option>
                    <option value="delegate" <?= $quadrant === 'delegate' ? 'selected' : '' ?>>Delegate</option>
                    <option value="eliminate" <?= $quadrant === 'eliminate' ? 'selected' : '' ?>>Eliminate</option>
                </select>
            </label>
            <button class="button secondary" type="submit">Filter</button>
            <a class="button ghost" href="index.php">Reset</a>
        </form>
    </section>

    <div class="content-grid">
        <section>
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Workspace</p>
                    <h2>Your Tasks</h2>
                </div>
                <span class="muted"><?= count($tasks) ?> shown</span>
            </div>

            <?php if (!$tasks): ?>
                <div class="empty-state panel">
                    <div class="empty-icon">✓</div>
                    <h3>No tasks found</h3>
                    <p>Create a task or adjust your filters.</p>
                    <a class="button primary" href="task-form.php">Create Task</a>
                </div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <article class="task-card <?= $task['status'] === 'done' ? 'is-done' : '' ?>">
                            <div class="task-main">
                                <div class="task-badges">
                                    <span class="badge quadrant-badge quadrant-<?= e($task['quadrant']) ?>"><?= e(quadrantLabel($task['quadrant'])) ?></span>
                                    <span class="badge status-<?= e($task['status']) ?>"><?= e(statusLabel($task['status'])) ?></span>
                                    <span class="badge priority-<?= e($task['priority']) ?>"><?= e(priorityLabel($task['priority'])) ?></span>
                                </div>
                                <h3><?= e($task['title']) ?></h3>
                                <?php if ($task['description'] !== ''): ?>
                                    <p><?= nl2br(e($task['description'])) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <span><?= e(quadrantLongLabel($task['quadrant'])) ?></span>
                                    <span>Created <?= e(date('M j, Y', strtotime($task['created_at']))) ?></span>
                                    <?php if ($task['due_date']): ?>
                                        <span>Due <?= e(date('M j, Y', strtotime($task['due_date']))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-actions">
                                <a class="button small secondary" href="task-form.php?id=<?= (int) $task['id'] ?>">Edit</a>
                                <form action="task-action.php" method="post">
                                    <input type="hidden" name="action" value="cycle">
                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                    <button class="button small ghost" type="submit">Next Status</button>
                                </form>
                                <form action="task-action.php" method="post" onsubmit="return confirm('Delete this task permanently?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                    <button class="button small danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside>
            <div class="section-heading compact">
                <div>
                    <p class="eyebrow">Activity</p>
                    <h2>Recent History</h2>
                </div>
                <a class="text-link" href="history.php">View all</a>
            </div>
            <div class="panel history-list">
                <?php if (!$recentHistory): ?>
                    <p class="muted">No history yet.</p>
                <?php else: ?>
                    <?php foreach ($recentHistory as $item): ?>
                        <div class="history-item">
                            <span class="history-dot"></span>
                            <div>
                                <strong><?= e($item['action']) ?></strong>
                                <p><?= e($item['title'] ?: $item['details']) ?></p>
                                <small><?= e(date('M j, H:i', strtotime($item['created_at']))) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

<footer>
    <div class="container">TaskFlow · PHP + SQLite · Eisenhower Matrix</div>
</footer>
</body>
</html>
