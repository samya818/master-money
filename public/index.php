<?php
// Autoriser CORS
$cors_origin = $_ENV['CORS_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $cors_origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Routeur API Master Money — Railway.
 * Toutes les requêtes sont envoyées ici (php -S avec ce fichier comme routeur).
 * CORS + routage /api/* vers api/*.php
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// Log temporaire pour déboguer
error_log('=== DEBUT REQUETE ===');
error_log('URI: ' . ($_SERVER['REQUEST_URI'] ?? ''));
error_log('METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? ''));
error_log('PATH: ' . $path);

$base = dirname(__DIR__);

// /api/login, /api/register (sans auth), /api/dashboard, /api/budget, etc.
if (preg_match('#^/api/([a-z_]+)(?:/([0-9]+))?/?$#', $path, $m)) {
    $route = $m[1];
    $id = $m[2] ?? null;
    $map = [
        'login' => 'login.php',
        'register' => 'register.php',
        'dashboard' => 'dashboard.php',
        'budget' => 'budget.php',
        'budgets' => 'budget.php',
        'expenses' => 'expenses.php',
        'objectifs' => 'objectifs.php',
        'simulation' => 'simulation.php',
        'comparaison' => 'comparaison.php',
        'profile' => 'profile.php',
        'export_data' => 'export_data.php',
    ];
    if (isset($map[$route])) {
        $_GET['id'] = $id;
        require $base . '/api/' . $map[$route];
        exit;
    }
}

// 404
header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode(['error' => 'Not Found', 'path' => $path]);
