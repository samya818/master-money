<?php
/**
 * Connexion PDO PostgreSQL pour Master Money (Supabase / Railway).
 * Utilise DATABASE_URL (format Supabase) ou variables séparées.
 */
$pdo = null;

$database_url = getenv('DATABASE_URL');
if ($database_url !== false && $database_url !== '') {
    // Format Supabase: postgresql://user:password@host:port/dbname?sslmode=require
    $url = parse_url($database_url);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 5432;
    $dbname = ltrim($url['path'] ?? '/postgres', '/');
    $user = $url['user'] ?? '';
    $pass = $url['pass'] ?? '';
    $sslmode = isset($url['query']) ? (strpos($url['query'], 'sslmode=') !== false ? '' : '?sslmode=require') : '?sslmode=require';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname" . ($sslmode ?: '');
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            throw $e;
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
} else {
    $host = getenv('PG_HOST') ?: '127.0.0.1';
    $port = getenv('PG_PORT') ?: 5432;
    $dbname = getenv('PG_DATABASE') ?: 'master_money';
    $user = getenv('PG_USER') ?: 'postgres';
    $pass = getenv('PG_PASSWORD') ?: '';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            throw $e;
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}
