<?php
/**
 * Middleware JWT : lit Authorization: Bearer <token>, décode et définit $current_user_id.
 * À inclure en premier dans chaque endpoint protégé.
 * En cas d'échec : 401 JSON.
 */
require_once __DIR__ . '/../lib/jwt.php';

$current_user_id = null;
$current_user = null;

$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', trim($auth_header), $m)) {
    $payload = jwt_decode($m[1]);
    if ($payload && isset($payload['user_id'])) {
        $current_user_id = (int) $payload['user_id'];
        require_once __DIR__ . '/../config/db.php';
        $stmt = $pdo->prepare("SELECT id, nom, email, avatar, points FROM utilisateurs WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current_user) {
            $current_user_id = null;
            $current_user = null;
        }
    }
}

if ($current_user_id === null) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Token manquant ou invalide']);
    exit;
}
