<?php
/**
 * GET /api/profile — Profil utilisateur + stats + badges.
 * PATCH /api/profile (ou POST) — Mise à jour nom, mot de passe (avatar optionnel).
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$uid = $current_user_id;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, nom, email, avatar, points, created_at FROM utilisateurs WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    $user['created_at'] = $user['created_at'] ?? null;

    $nb = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id = ?");
    $nb->execute([$uid]);
    $nbBudgets = (int) $nb->fetchColumn();

    $moy = $pdo->prepare("SELECT AVG(reste_a_vivre) FROM budgets WHERE utilisateur_id = ?");
    $moy->execute([$uid]);
    $moyReste = round((float) ($moy->fetchColumn() ?? 0));

    $pos = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id = ? AND reste_a_vivre > 0");
    $pos->execute([$uid]);
    $nbPos = (int) $pos->fetchColumn();

    $nbObjectifs = 0;
    $nbAtteints = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM objectifs WHERE utilisateur_id = ?");
        $stmt->execute([$uid]);
        $nbObjectifs = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM objectifs WHERE utilisateur_id = ? AND statut = 'atteint'");
        $stmt->execute([$uid]);
        $nbAtteints = (int) $stmt->fetchColumn();
    } catch (Exception $e) {}

    $badges = [];
    try {
        $stmt = $pdo->prepare("SELECT b.* FROM utilisateur_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.utilisateur_id = ?");
        $stmt->execute([$uid]);
        $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $points = (int) ($user['points'] ?? 0);
    $memberDays = (int) ((time() - strtotime($user['created_at'])) / 86400);

    echo json_encode([
        'user' => $user,
        'stats' => [
            'nb_budgets' => $nbBudgets,
            'moy_reste' => $moyReste,
            'nb_pos' => $nbPos,
            'nb_objectifs' => $nbObjectifs ?? 0,
            'nb_atteints' => $nbAtteints,
            'badges_count' => count($badges),
        ],
        'badges' => $badges,
        'points' => $points,
        'member_days' => $memberDays,
    ]);
    exit;
}

if ($method === 'PATCH' || $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (!empty($input['nom'])) {
        $nom = htmlspecialchars(trim($input['nom']));
        $pdo->prepare("UPDATE utilisateurs SET nom = ? WHERE id = ?")->execute([$nom, $uid]);
        $current_user['nom'] = $nom;
    }

    if (!empty($input['change_pwd'])) {
        $ancien = $input['ancien_mdp'] ?? '';
        $nouveau = $input['nouveau_mdp'] ?? '';
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->execute([$uid]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($ancien, $hash)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ancien mot de passe incorrect']);
            exit;
        }
        if (strlen($nouveau) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Le mot de passe doit contenir au moins 8 caractères']);
            exit;
        }
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?")
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $uid]);
    }

    echo json_encode(['success' => true, 'user' => $current_user]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
