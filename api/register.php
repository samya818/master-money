<?php
/**
 * POST /api/register — nom, email, mot_de_passe, confirmation
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
$nom = htmlspecialchars(trim($input['nom'] ?? ''));
$email = trim($input['email'] ?? '');
$mdp = $input['mot_de_passe'] ?? '';
$conf = $input['confirmation'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Veuillez entrer une adresse email valide.']);
    exit;
}
if (strlen($mdp) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Le mot de passe doit contenir au moins 8 caractères.']);
    exit;
}
if ($mdp !== $conf) {
    http_response_code(400);
    echo json_encode(['error' => 'Les mots de passe ne correspondent pas.']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Cet email est déjà utilisé.']);
    exit;
}

$hash = password_hash($mdp, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?) RETURNING id");
$stmt->execute([$nom, $email, $hash]);
$id = (int) $stmt->fetchColumn();
$user = [
    'id' => $id,
    'nom' => $nom,
    'email' => $email,
    'avatar' => null,
    'points' => 0,
];
$token = jwt_encode(['user_id' => $id]);
echo json_encode(['token' => $token, 'user' => $user]);
