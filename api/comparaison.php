<?php
/**
 * GET /api/comparaison — Dernier budget, moyennes UMI, moyennes plateforme (anonymisées).
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$uid = $current_user_id;

$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]);
$monBudget = $stmt->fetch(PDO::FETCH_ASSOC);

$moyennes = ['loyer' => 1200, 'alimentation' => 600, 'transport' => 250, 'loisirs' => 150, 'imprevus' => 100];
try {
    $stmt = $pdo->query("SELECT categorie, montant_moyen FROM moyennes_etudiants WHERE annee = 2026");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $moyennes[$row['categorie']] = (float) $row['montant_moyen'];
    }
} catch (Exception $e) {}

$avgRows = null;
try {
    $stmt = $pdo->query("
        SELECT
            AVG(b.loyer) AS loyer,
            AVG(b.transport) AS transport,
            AVG(b.alimentation) AS alimentation,
            AVG(b.loisirs) AS loisirs,
            AVG(b.imprevus) AS imprevus,
            AVG(b.bourse + b.aide_familiale + b.emploi) AS revenus,
            AVG(b.reste_a_vivre) AS reste,
            COUNT(*) AS nb
        FROM budgets b
        JOIN (
            SELECT utilisateur_id, MAX(created_at) AS last
            FROM budgets
            GROUP BY utilisateur_id
        ) latest ON b.utilisateur_id = latest.utilisateur_id AND b.created_at = latest.last
    ");
    $avgRows = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($avgRows) {
        foreach (['loyer','transport','alimentation','loisirs','imprevus','revenus','reste','nb'] as $k) {
            if (isset($avgRows[$k]) && $k !== 'nb') $avgRows[$k] = (float) $avgRows[$k];
            if ($k === 'nb') $avgRows[$k] = (int) $avgRows[$k];
        }
    }
} catch (Exception $e) {}

echo json_encode([
    'mon_budget' => $monBudget,
    'moyennes' => $moyennes,
    'plateforme' => $avgRows,
]);
