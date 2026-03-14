<?php
/**
 * GET /api/export-data — Données pour génération PDF/CSV côté client.
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$uid = $current_user_id;

$stmt = $pdo->prepare("SELECT id, nom, email, points, created_at FROM utilisateurs WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 12");
$stmt->execute([$uid]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$dernier = $budgets[0] ?? null;

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS nb_budgets,
        AVG(reste_a_vivre) AS moy_reste,
        SUM(bourse + aide_familiale + emploi) AS total_revenus,
        SUM(loyer + transport + alimentation + loisirs + imprevus) AS total_depenses,
        SUM(CASE WHEN reste_a_vivre > 0 THEN reste_a_vivre ELSE 0 END) AS total_epargne
    FROM budgets
    WHERE utilisateur_id = ?
");
$stmt->execute([$uid]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT categorie, SUM(montant) AS total
    FROM depenses
    WHERE utilisateur_id = ?
      AND EXTRACT(MONTH FROM date_depense) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM date_depense) = EXTRACT(YEAR FROM CURRENT_DATE)
    GROUP BY categorie
");
$stmt->execute([$uid]);
$depMois = $stmt->fetchAll(PDO::FETCH_ASSOC);

$badges = [];
try {
    $stmt = $pdo->prepare("SELECT b.icone, b.nom FROM utilisateur_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.utilisateur_id = ?");
    $stmt->execute([$uid]);
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$rev = $dep = $reste = 0;
if ($dernier) {
    $rev = (float)$dernier['bourse'] + (float)$dernier['aide_familiale'] + (float)$dernier['emploi'];
    $dep = (float)$dernier['loyer'] + (float)$dernier['transport'] + (float)$dernier['alimentation'] + (float)$dernier['loisirs'] + (float)$dernier['imprevus'];
    $reste = (float)$dernier['reste_a_vivre'];
}
$periode = $dernier ? ($dernier['mois'] ?? date('Y-m', strtotime($dernier['created_at']))) : date('Y-m');

$moyennes = ['loyer' => 1200, 'alimentation' => 600, 'transport' => 250, 'loisirs' => 150];

echo json_encode([
    'user' => $user,
    'budgets' => $budgets,
    'dernier' => $dernier,
    'stats' => $stats,
    'depenses_mois' => $depMois,
    'badges' => $badges,
    'revenus_dernier' => $rev,
    'depenses_dernier' => $dep,
    'reste_dernier' => $reste,
    'periode' => $periode,
    'moyennes' => $moyennes,
    'generated_at' => date('d/m/Y à H:i'),
]);
