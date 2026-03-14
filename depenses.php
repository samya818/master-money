<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: /master-money/connexion.php"); exit; }
$uid = $_SESSION['user_id'];

function icon($p,$s=16,$c='currentColor'){return "<svg viewBox=\"0 0 24 24\" style=\"width:{$s}px;height:{$s}px;stroke:{$c};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$p}</svg>";}

// Ajouter dépense
if (isset($_POST['ajouter'])) {
    $pdo->prepare("INSERT INTO depenses (utilisateur_id,categorie,montant,description,date_depense) VALUES (?,?,?,?,?)")
        ->execute([$uid, $_POST['categorie'], floatval($_POST['montant']), htmlspecialchars($_POST['description']), $_POST['date_depense']]);
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM depenses WHERE id=? AND utilisateur_id=?")->execute([$_GET['supprimer'], $uid]);
    header("Location: /master-money/depenses.php"); exit;
}

// Filtre
$filtre = $_GET['periode'] ?? 'mois';
$where  = match($filtre) {
    'semaine' => "AND date_depense >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
    'jour'    => "AND date_depense = CURDATE()",
    default   => "AND MONTH(date_depense)=MONTH(CURDATE()) AND YEAR(date_depense)=YEAR(CURDATE())"
};

$stmt = $pdo->prepare("SELECT * FROM depenses WHERE utilisateur_id=? $where ORDER BY date_depense DESC, created_at DESC");
$stmt->execute([$uid]); $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT categorie, SUM(montant) as total FROM depenses WHERE utilisateur_id=? $where GROUP BY categorie");
$stmt2->execute([$uid]); $stats = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$totalGlobal = array_sum(array_column($stats, 'total'));

// Icônes SVG par catégorie (paths Heroicons)
$catPaths = [
    'logement'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'nourriture' => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>',
    'transport'  => '<rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    'loisirs'    => '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>',
    'factures'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    'autre'      => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
];
$catColors = ['logement'=>'#4f8ef7','nourriture'=>'#00e5a0','transport'=>'#9b6dff','loisirs'=>'#ffb020','factures'=>'#ff4f6d','autre'=>'#8888aa'];
$catLabels = ['logement'=>'Logement','nourriture'=>'Nourriture','transport'=>'Transport','loisirs'=>'Loisirs','factures'=>'Factures','autre'=>'Autre'];

include 'includes/header.php';
?>

<div style="max-width:1000px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Suivi</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',22,'var(--accent-green)') ?>
            Dépenses Quotidiennes
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">Enregistrez et analysez vos dépenses jour par jour.</p>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;align-items:start;">

        <!-- Formulaire sticky -->
        <div class="card" style="position:sticky;top:76px;">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',16,'var(--accent-green)') ?>
                Nouvelle Dépense
            </h3>
            <form method="POST" style="display:flex;flex-direction:column;gap:0.85rem;">
                <div>
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Catégorie</label>
                    <select name="categorie" required style="width:100%;padding:0.7rem;border-radius:8px;font-size:0.88rem;">
                        <?php foreach($catLabels as $val=>$label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Montant (MAD)</label>
                    <input type="number" name="montant" placeholder="Ex: 35" min="0" step="0.01" required style="width:100%;padding:0.7rem;border-radius:8px;font-size:0.88rem;">
                </div>
                <div>
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Description</label>
                    <input type="text" name="description" placeholder="Ex: Repas restaurant" style="width:100%;padding:0.7rem;border-radius:8px;font-size:0.88rem;">
                </div>
                <div>
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Date</label>
                    <input type="date" name="date_depense" value="<?= date('Y-m-d') ?>" required style="width:100%;padding:0.7rem;border-radius:8px;font-size:0.88rem;">
                </div>
                <button type="submit" name="ajouter" class="btn-primary" style="justify-content:center;border-radius:9px;">
                    <?= icon('<polyline points="20 6 9 17 4 12"/>',15) ?> Enregistrer
                </button>
            </form>

            <?php if (!empty($stats)): ?>
            <div style="margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--border);">
                <p style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:0.9rem;">Répartition</p>
                <?php foreach($stats as $s):
                    $pct = $totalGlobal>0?round(($s['total']/$totalGlobal)*100):0;
                    $col = $catColors[$s['categorie']]??'#8888aa';
                    $ipath = $catPaths[$s['categorie']]??$catPaths['autre'];
                ?>
                <div style="margin-bottom:0.75rem;">
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text-secondary);margin-bottom:0.28rem;align-items:center;">
                        <span style="display:flex;align-items:center;gap:0.35rem;"><?= icon($ipath,13,$col) ?> <?= ucfirst($s['categorie']) ?></span>
                        <span style="color:<?= $col ?>;font-weight:600;"><?= number_format($s['total'],0) ?> MAD</span>
                    </div>
                    <div style="background:var(--bg-secondary);border-radius:4px;height:4px;">
                        <div style="background:<?= $col ?>;width:<?= $pct ?>%;height:4px;border-radius:4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:0.8rem;padding-top:0.7rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:0.84rem;">
                    <span style="color:var(--text-secondary);">Total</span>
                    <strong><?= number_format($totalGlobal,0) ?> MAD</strong>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Liste dépenses -->
        <div>
            <!-- Filtres période -->
            <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                <?php foreach(['jour'=>"Aujourd'hui",'semaine'=>'Cette semaine','mois'=>'Ce mois'] as $k=>$v): ?>
                <a href="?periode=<?= $k ?>"
                   style="padding:0.38rem 1rem;border-radius:50px;font-size:0.8rem;text-decoration:none;font-weight:500;
                          background:<?= $filtre===$k?'var(--accent-green)':'var(--bg-card)' ?>;
                          color:<?= $filtre===$k?'#09090f':'var(--text-secondary)' ?>;
                          border:1px solid <?= $filtre===$k?'var(--accent-green)':'var(--border)' ?>;">
                    <?= $v ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($depenses)): ?>
            <div class="card" style="text-align:center;padding:3rem;">
                <?= icon('<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>',40,'var(--text-muted)') ?>
                <p style="color:var(--text-secondary);margin-top:1rem;">Aucune dépense enregistrée pour cette période.</p>
            </div>
            <?php else:
                $grouped = [];
                foreach($depenses as $d) $grouped[$d['date_depense']][] = $d;
                foreach($grouped as $date=>$items):
                    $totalJour = array_sum(array_column($items,'montant'));
            ?>
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.7rem;padding:0 0.2rem;">
                    <span style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;display:flex;align-items:center;gap:0.4rem;">
                        <?= icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',12,'var(--text-muted)') ?>
                        <?= date('d/m/Y',strtotime($date))===date('d/m/Y')?"Aujourd'hui":date('d M Y',strtotime($date)) ?>
                    </span>
                    <span style="font-size:0.8rem;color:var(--text-secondary);">Total : <strong style="color:var(--text-primary);"><?= number_format($totalJour,0) ?> MAD</strong></span>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.55rem;">
                    <?php foreach($items as $d):
                        $col   = $catColors[$d['categorie']]??'#8888aa';
                        $ipath = $catPaths[$d['categorie']]??$catPaths['autre'];
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:0.8rem 1rem;border-left:3px solid <?= $col ?>;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div style="width:34px;height:34px;border-radius:8px;background:<?= $col ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <?= icon($ipath,16,$col) ?>
                            </div>
                            <div>
                                <div style="font-size:0.87rem;color:var(--text-primary);font-weight:500;"><?= htmlspecialchars($d['description']?:ucfirst($d['categorie'])) ?></div>
                                <div style="font-size:0.71rem;color:var(--text-muted);"><?= $catLabels[$d['categorie']]??ucfirst($d['categorie']) ?></div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <span style="font-family:'Syne',sans-serif;font-weight:700;color:<?= $col ?>;"><?= number_format($d['montant'],0) ?> MAD</span>
                            <a href="?supprimer=<?= $d['id'] ?>" onclick="return confirm('Supprimer ?')"
                               style="display:flex;align-items:center;color:var(--danger);opacity:0.45;text-decoration:none;transition:opacity 0.15s;"
                               onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.45'">
                                <?= icon('<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',15,'var(--danger)') ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>