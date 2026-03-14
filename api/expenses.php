<?php
/**
 * GET /api/expenses — Liste dépenses (query: periode=jour|semaine|mois).
 * POST /api/expenses — Ajouter une dépense.
 * DELETE /api/expenses?id= — Supprimer par id.
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$uid = $current_user_id;
$method = $_SERVER['REQUEST_METHOD'];

// Filtre période (PostgreSQL)
$filtre = $_GET['periode'] ?? 'mois';
switch ($filtre) {
    case 'semaine':
        $where = "AND date_depense >= (CURRENT_DATE - INTERVAL '7 days')";
        break;
    case 'jour':
        $where = "AND date_depense = CURRENT_DATE";
        break;
    default:
        $where = "AND EXTRACT(MONTH FROM date_depense) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM date_depense) = EXTRACT(YEAR FROM CURRENT_DATE)";
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id requis']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM depenses WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$id, $uid]);
    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount() > 0]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $categorie = $input['categorie'] ?? '';
    $montant = (float) ($input['montant'] ?? 0);
    $description = htmlspecialchars(trim($input['description'] ?? ''));
    $date_depense = $input['date_depense'] ?? date('Y-m-d');

    $allowed = ['logement', 'nourriture', 'transport', 'loisirs', 'factures', 'autre'];
    if (!in_array($categorie, $allowed) || $montant <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Catégorie invalide ou montant manquant']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO depenses (utilisateur_id, categorie, montant, description, date_depense) VALUES (?, ?, ?, ?, ?) RETURNING id");
    $stmt->execute([$uid, $categorie, $montant, $description, $date_depense]);
    echo json_encode(['success' => true, 'id' => (int) $stmt->fetchColumn()]);
    exit;
}

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM depenses WHERE utilisateur_id = ? $where ORDER BY date_depense DESC, created_at DESC");
    $stmt->execute([$uid]);
    $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT categorie, SUM(montant) AS total FROM depenses WHERE utilisateur_id = ? $where GROUP BY categorie");
    $stmt2->execute([$uid]);
    $stats = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $totalGlobal = array_sum(array_column($stats, 'total'));

    echo json_encode([
        'depenses' => $depenses,
        'stats' => $stats,
        'total_global' => (float) $totalGlobal,
        'periode' => $filtre,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
