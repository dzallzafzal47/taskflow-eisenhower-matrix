<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$history = $pdo->query('SELECT h.*, t.title FROM task_history h LEFT JOIN tasks t ON t.id = h.task_id ORDER BY h.id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>History — TaskFlow</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">TaskFlow</a>
        <nav><a href="index.php">Tasks</a><a href="matrix.php">Matrix</a><a class="active" href="history.php">History</a></nav>
    </div>
</header>

<main class="container narrow page-space">
    <section class="section-heading history-title">
        <div>
            <p class="eyebrow">Audit trail</p>
            <h1>Task History</h1>
            <p class="subtext">Task creation, edits, status updates, Matrix changes, and deletion are recorded here.</p>
        </div>
        <a class="button secondary" href="index.php">Back to Tasks</a>
    </section>

    <section class="panel history-page-list">
        <?php if (!$history): ?>
            <div class="empty-state compact-empty"><h3>No history yet</h3><p>Your activity will appear here.</p></div>
        <?php else: ?>
            <?php foreach ($history as $item): ?>
                <article class="history-row">
                    <div class="history-icon">↺</div>
                    <div class="history-copy">
                        <strong><?= e($item['action']) ?></strong>
                        <p><?= e($item['title'] ?: $item['details']) ?></p>
                        <?php if ($item['details'] && $item['title']): ?><small><?= e($item['details']) ?></small><?php endif; ?>
                    </div>
                    <time><?= e(date('M j, Y · H:i', strtotime($item['created_at']))) ?></time>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
