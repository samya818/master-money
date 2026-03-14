<?php
/**
 * POST /api/simulation — Revenus, dépenses, épargne objectif, durée (pas d'écriture BDD).
 * Réponse : projection mensuelle (cumul épargne, reste).
 */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$bourse = (float) ($input['bourse'] ?? 0);
$aide = (float) ($input['aide_familiale'] ?? 0);
$emploi = (float) ($input['emploi'] ?? 0);
$loyer = (float) ($input['loyer'] ?? 0);
$transport = (float) ($input['transport'] ?? 0);
$alimentation = (float) ($input['alimentation'] ?? 0);
$loisirs = (float) ($input['loisirs'] ?? 0);
$imprevus = (float) ($input['imprevus'] ?? 0);
$epargne_obj = (float) ($input['epargne_objectif'] ?? 200);
$duree = (int) ($input['duree_mois'] ?? 6);

$revenus = $bourse + $aide + $emploi;
$depenses = $loyer + $transport + $alimentation + $loisirs + $imprevus;
$reste = $revenus - $depenses;
$disponible = $reste - $epargne_obj;

$projection = [];
$cumul = 0.0;
for ($i = 1; $i <= $duree; $i++) {
    $cumul += max(0, $epargne_obj);
    $projection[] = ['mois' => 'M+' . $i, 'cumul' => $cumul, 'reste' => $disponible];
}

echo json_encode([
    'revenus' => $revenus,
    'depenses' => $depenses,
    'reste' => $reste,
    'disponible' => $disponible,
    'epargne_obj' => $epargne_obj,
    'duree' => $duree,
    'projection' => $projection,
    'loyer' => $loyer,
    'transport' => $transport,
    'alimentation' => $alimentation,
    'loisirs' => $loisirs,
    'imprevus' => $imprevus,
    'bourse' => $bourse,
    'aide' => $aide,
    'emploi' => $emploi,
]);
