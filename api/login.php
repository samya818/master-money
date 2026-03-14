<?php
/**
 * POST /api/login — email, mot_de_passe
 * Réponse : { "token", "user": { id, nom, email, avatar, points } }
 */
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/jwt.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = trim($input['email'] ?? '');
$mdp = $input['mot_de_passe'] ?? '';

if ($email === '' || $mdp === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email et mot de passe requis']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nom, email, mot_de_passe, avatar, points FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Email ou mot de passe incorrect']);
    exit;
}

unset($user['mot_de_passe']);
$user['avatar'] = $user['avatar'] ?? null;
$user['points'] = (int) ($user['points'] ?? 0);

$token = jwt_encode(['user_id' => (int) $user['id']]);
echo json_encode(['token' => $token, 'user' => $user]);
