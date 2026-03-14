<?php
/**
 * GET /api/dashboard — Données pour le tableau de bord (après auth).
 * Retourne : dernier budget, historique 6 mois, points, badges, moyennes, recommandations.
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$uid = $current_user_id;

// Dernier budget
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]);
$dernier = $stmt->fetch(PDO::FETCH_ASSOC);

// Historique 6 mois (PostgreSQL : to_char pour période)
$stmt = $pdo->prepare("
    SELECT to_char(created_at, 'YYYY-MM') AS periode,
           (bourse + aide_familiale + emploi) AS revenus,
           (loyer + transport + alimentation + loisirs + imprevus) AS depenses,
           reste_a_vivre
    FROM budgets
    WHERE utilisateur_id = ?
    ORDER BY created_at DESC
    LIMIT 6
");
$stmt->execute([$uid]);
$historique = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Points
$points = (int) ($current_user['points'] ?? 0);

// Badges obtenus
$badgesObt = [];
$badgesObtenusNoms = [];
try {
    $stmt = $pdo->prepare("SELECT b.nom, b.code FROM utilisateur_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.utilisateur_id = ?");
    $stmt->execute([$uid]);
    $badgesObt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $badgesObtenusNoms = array_column($badgesObt, 'nom');
} catch (Exception $e) {}

$allBadges = [];
try {
    $allBadges = $pdo->query("SELECT * FROM badges ORDER BY points_requis ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Moyennes étudiants
$moyennes = ['loyer' => 1200, 'alimentation' => 600, 'transport' => 250, 'loisirs' => 150, 'imprevus' => 100];
try {
    $stmt = $pdo->query("SELECT categorie, montant_moyen FROM moyennes_etudiants WHERE annee = 2026");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $moyennes[$row['categorie']] = (float) $row['montant_moyen'];
    }
} catch (Exception $e) {}

// Attribution badges (logique conservée)
function attribuerBadge($pdo, $uid, $code) {
    try {
        $b = $pdo->prepare("SELECT id FROM badges WHERE code = ?");
        $b->execute([$code]);
        $badge = $b->fetch(PDO::FETCH_ASSOC);
        if (!$badge) return;
        $c = $pdo->prepare("SELECT id FROM utilisateur_badges WHERE utilisateur_id = ? AND badge_id = ?");
        $c->execute([$uid, $badge['id']]);
        if (!$c->fetch()) {
            $pdo->prepare("INSERT INTO utilisateur_badges (utilisateur_id, badge_id) VALUES (?, ?)")->execute([$uid, $badge['id']]);
            $pdo->prepare("UPDATE utilisateurs SET points = COALESCE(points, 0) + 10 WHERE id = ?")->execute([$uid]);
        }
    } catch (Exception $e) {}
}

try {
    $nb = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id = ?");
    $nb->execute([$uid]);
    if ((int) $nb->fetchColumn() >= 1) attribuerBadge($pdo, $uid, 'premier_budget');
    if ($dernier && (float) $dernier['reste_a_vivre'] > 0) attribuerBadge($pdo, $uid, 'economiseur');
    $pos = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id = ? AND reste_a_vivre > 0");
    $pos->execute([$uid]);
    $nbPos = (int) $pos->fetchColumn();
    if ($nbPos >= 3) attribuerBadge($pdo, $uid, 'bon_gestionnaire');
    if ($nbPos >= 6) attribuerBadge($pdo, $uid, 'budget_master');
} catch (Exception $e) {}

// Recharger points après attribution
$stmt = $pdo->prepare("SELECT points FROM utilisateurs WHERE id = ?");
$stmt->execute([$uid]);
$points = (int) $stmt->fetchColumn();

// Recommandations
$recommandations = [];
if ($dernier) {
    $dernier = array_map(function ($v) { return is_numeric($v) ? (float) $v : $v; }, $dernier);
    if (($dernier['transport'] ?? 0) > ($moyennes['transport'] ?? 0)) {
        $recommandations[] = ['type' => 'warning', 'msg' => 'Transport (' . number_format($dernier['transport'], 0) . ' MAD) dépasse la moyenne (' . $moyennes['transport'] . ' MAD). Pensez aux abonnements étudiants.'];
    }
    if (($dernier['alimentation'] ?? 0) > ($moyennes['alimentation'] ?? 0)) {
        $recommandations[] = ['type' => 'warning', 'msg' => "Alimentation élevée ! Cuisiner maison peut vous faire économiser jusqu'à 200 MAD/mois."];
    }
    if (($dernier['loisirs'] ?? 0) > ($moyennes['loisirs'] ?? 0)) {
        $recommandations[] = ['type' => 'warning', 'msg' => 'Loisirs au-dessus de la moyenne. Cherchez des activités gratuites sur le campus.'];
    }
    $reste = (float) ($dernier['reste_a_vivre'] ?? 0);
    if ($reste < 0) {
        $recommandations[] = ['type' => 'danger', 'msg' => 'Budget déficitaire ce mois ! Réduisez les dépenses non essentielles en priorité.'];
    } elseif ($reste > 500) {
        $recommandations[] = ['type' => 'success', 'msg' => 'Excellent ! Vous pouvez mettre ' . number_format($reste * 0.5, 0) . ' MAD de côté pour votre épargne.'];
    }
}
if (empty($recommandations)) {
    $recommandations[] = ['type' => 'success', 'msg' => 'Votre budget semble équilibré. Continuez comme ça !'];
}

// Budgets pour tableau historique (10 derniers)
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$uid]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'user' => $current_user,
    'points' => $points,
    'dernier_budget' => $dernier,
    'historique' => $historique,
    'badges_obtenus' => $badgesObt,
    'badges_obtenus_noms' => $badgesObtenusNoms,
    'all_badges' => $allBadges,
    'moyennes' => $moyennes,
    'recommandations' => $recommandations,
    'budgets' => $budgets,
]);
