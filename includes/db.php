<?php
// Load .env from project root
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

// Redirect to setup wizard if not installed
$installed = dirname(__DIR__) . '/.installed';
if (!file_exists($installed) && !defined('PLANNINGCO_SETUP')) {
    header('Location: ../setup/');
    exit;
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? 'timetable_system'
        ),
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('PlanningCo DB: ' . $e->getMessage());
    http_response_code(503);
    die('<p style="font-family:sans-serif;padding:2rem;color:#dc2626">Database unavailable. Check your <code>.env</code> or run <a href="../setup/">setup</a>.</p>');
}
