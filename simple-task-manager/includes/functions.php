<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path = 'index.php'): never
{
    header('Location: ' . $path);
    exit;
}

function addHistory(PDO $pdo, ?int $taskId, string $action, string $details = ''): void
{
    $stmt = $pdo->prepare('INSERT INTO task_history (task_id, action, details) VALUES (:task_id, :action, :details)');
    $stmt->execute([
        ':task_id' => $taskId,
        ':action' => $action,
        ':details' => $details,
    ]);
}

function statusLabel(string $status): string
{
    return match ($status) {
        'doing' => 'In Progress',
        'done' => 'Done',
        default => 'To Do',
    };
}

function priorityLabel(string $priority): string
{
    return ucfirst($priority);
}

function quadrantLabel(string $quadrant): string
{
    return match ($quadrant) {
        'do' => 'Do',
        'delegate' => 'Delegate',
        'eliminate' => 'Eliminate',
        default => 'Decide',
    };
}

function quadrantLongLabel(string $quadrant): string
{
    return match ($quadrant) {
        'do' => 'Do — Urgent & Important',
        'delegate' => 'Delegate — Urgent, Not Important',
        'eliminate' => 'Eliminate — Not Urgent, Not Important',
        default => 'Decide — Important, Not Urgent',
    };
}

function quadrantDescription(string $quadrant): string
{
    return match ($quadrant) {
        'do' => 'Do it as soon as possible.',
        'delegate' => 'Assign it to someone else when possible.',
        'eliminate' => 'Remove, reduce, or intentionally skip it.',
        default => 'Schedule a time to do it.',
    };
}
