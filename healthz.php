<?php
// Health check for Docker, load balancers and monitoring.
// Returns 200 {"status":"ok"} when PHP runs and the database answers, otherwise 503.
declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$dsn = getenv('PERTANIAN_DSN') ?: sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
$start = microtime(true);
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
    $pdo->query('SELECT 1')->fetchColumn();
    echo json_encode(['status' => 'ok', 'db_ms' => round((microtime(true) - $start) * 1000, 1)]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'reason' => 'database unavailable']);
}
