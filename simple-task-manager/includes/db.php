<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $dbPath = $dataDir . '/tasks.sqlite';
    $isNew = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($isNew) {
        $schema = file_get_contents($dataDir . '/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Unable to load database schema.');
        }
        $pdo->exec($schema);
    } else {
        migrateDatabase($pdo);
    }

    return $pdo;
}

function migrateDatabase(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(tasks)')->fetchAll();
    $columnNames = array_column($columns, 'name');

    if (!in_array('quadrant', $columnNames, true)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN quadrant TEXT NOT NULL DEFAULT 'schedule'");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_quadrant ON tasks(quadrant)');
    }
}
