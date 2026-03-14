<?php
/**
 * GET /api/objectifs — Liste des objectifs.
 * POST /api/objectifs — action=ajouter (créer) ou action=epargner (ajout montant).
 * DELETE /api/objectifs?id= — Supprimer un objectif.
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$uid = $current_user_id;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id requis']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM objectifs WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$id, $uid]);
    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount() > 0]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'ajouter';

    if ($action === 'ajouter') {
        $titre = htmlspecialchars(trim($input['titre'] ?? ''));
        $montant_cible = (float) ($input['montant_cible'] ?? 0);
        $duree_mois = (int) ($input['duree_mois'] ?? 1);
        $date_debut = $input['date_debut'] ?? date('Y-m-d');
        if ($titre === '' || $montant_cible <= 0 || $duree_mois < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Titre, montant cible et durée requis']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO objectifs (utilisateur_id, titre, montant_cible, duree_mois, date_debut) VALUES (?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$uid, $titre, $montant_cible, $duree_mois, $date_debut]);
        echo json_encode(['success' => true, 'id' => (int) $stmt->fetchColumn()]);
        exit;
    }

    if ($action === 'epargner') {
        $id = (int) ($input['objectif_id'] ?? 0);
        $ajout = (float) ($input['montant_ajout'] ?? 0);
        if ($id <= 0 || $ajout <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'objectif_id et montant_ajout requis']);
            exit;
        }
        $pdo->prepare("UPDATE objectifs SET montant_actuel = LEAST(montant_actuel + ?, montant_cible) WHERE id = ? AND utilisateur_id = ?")
            ->execute([$ajout, $id, $uid]);
        $check = $pdo->prepare("SELECT id FROM objectifs WHERE id = ? AND montant_actuel >= montant_cible");
        $check->execute([$id]);
        if ($check->fetch()) {
            $pdo->prepare("UPDATE objectifs SET statut = 'atteint' WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'action invalide']);
    exit;
}

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM objectifs WHERE utilisateur_id = ? ORDER BY created_at DESC");
    $stmt->execute([$uid]);
    $objectifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['objectifs' => $objectifs]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
