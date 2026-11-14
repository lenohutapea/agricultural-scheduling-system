<?php
// Satu koneksi PDO untuk seluruh aplikasi.
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // PERTANIAN_DSN hanya dipakai untuk pengujian (misalnya SQLite). Normalnya kosong.
    $dsn = getenv('PERTANIAN_DSN') ?: sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<h1>Tidak bisa terhubung ke database</h1>';
        echo '<p>Pastikan MySQL di XAMPP sudah berjalan, database <b>' . htmlspecialchars(DB_NAME)
            . '</b> sudah dibuat dari <code>database.sql</code>, dan isi <code>config.php</code> sudah benar.</p>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        exit;
    }

    if (str_starts_with($dsn, 'sqlite')) {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}
