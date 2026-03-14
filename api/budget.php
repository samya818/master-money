<?php
/**
 * GET /api/budget — Liste des budgets (10 derniers).
 * POST /api/budget — Créer un budget (même logique que calculateur.php).
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$uid = $current_user_id;

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$uid]);
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['budgets' => $budgets]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $bourse = (float) ($input['bourse'] ?? 0);
    $aide = (float) ($input['aide_familiale'] ?? 0);
    $emploi = (float) ($input['emploi'] ?? 0);
    $loyer = (float) ($input['loyer'] ?? 0);
    $transport = (float) ($input['transport'] ?? 0);
    $alimentation = (float) ($input['alimentation'] ?? 0);
    $loisirs = (float) ($input['loisirs'] ?? 0);
    $imprevus = (float) ($input['imprevus'] ?? 0);
    $mois = $input['mois'] ?? date('Y-m');

    $total_revenus = $bourse + $aide + $emploi;
    $total_depenses = $loyer + $transport + $alimentation + $loisirs + $imprevus;
    $reste = $total_revenus - $total_depenses;

    $stmt = $pdo->prepare("
        INSERT INTO budgets
        (utilisateur_id, mois, bourse, aide_familiale, emploi, loyer, transport, alimentation, loisirs, imprevus, reste_a_vivre)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$uid, $mois, $bourse, $aide, $emploi, $loyer, $transport, $alimentation, $loisirs, $imprevus, $reste]);

    $alerte = $reste < 0
        ? 'Déficit de ' . number_format(abs($reste), 2) . ' MAD ce mois.'
        : ($reste < 200
            ? 'Budget très serré. Essayez de réduire vos dépenses.'
            : 'Bonne gestion ! Épargne potentielle : ' . number_format($reste, 2) . ' MAD.');
    $classe = $reste < 0 ? 'danger' : ($reste < 200 ? 'warning' : 'success');

    echo json_encode([
        'success' => true,
        'total_revenus' => $total_revenus,
        'total_depenses' => $total_depenses,
        'reste' => $reste,
        'classe' => $classe,
        'alerte' => $alerte,
        'loyer' => $loyer,
        'transport' => $transport,
        'alimentation' => $alimentation,
        'loisirs' => $loisirs,
        'imprevus' => $imprevus,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
