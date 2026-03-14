<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /master-money/connexion.php");
    exit;
}

$uid      = $_SESSION['user_id'];
$resultat = null;
$alerte   = "";
$classe   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bourse       = floatval($_POST['bourse']);
    $aide         = floatval($_POST['aide_familiale']);
    $emploi       = floatval($_POST['emploi']);
    $loyer        = floatval($_POST['loyer']);
    $transport    = floatval($_POST['transport']);
    $alimentation = floatval($_POST['alimentation']);
    $loisirs      = floatval($_POST['loisirs']);
    $imprevus     = floatval($_POST['imprevus']);
    $mois         = $_POST['mois'] ?? date('Y-m');

    $total_revenus  = $bourse + $aide + $emploi;
    $total_depenses = $loyer + $transport + $alimentation + $loisirs + $imprevus;
    $reste          = $total_revenus - $total_depenses;

    if ($reste < 0)       { $alerte = "Déficit de ".number_format(abs($reste),2)." MAD ce mois."; $classe = "danger"; }
    elseif ($reste < 200) { $alerte = "Budget très serré. Essayez de réduire vos dépenses.";       $classe = "warning"; }
    else                  { $alerte = "Bonne gestion ! Épargne potentielle : ".number_format($reste,2)." MAD."; $classe = "success"; }

    $resultat = compact('total_revenus','total_depenses','reste','classe','loyer','transport','alimentation','loisirs','imprevus');

    $stmt = $pdo->prepare("INSERT INTO budgets
        (utilisateur_id, mois, bourse, aide_familiale, emploi, loyer, transport, alimentation, loisirs, imprevus, reste_a_vivre)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$uid, $mois, $bourse, $aide, $emploi, $loyer, $transport, $alimentation, $loisirs, $imprevus, $reste]);}

include 'includes/header.php';

// SVG helper
function icon($path, $size=16, $color='currentColor') {
    return "<svg viewBox=\"0 0 24 24\" style=\"width:{$size}px;height:{$size}px;stroke:{$color};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$path}</svg>";
}
?>

<div style="max-width:760px; margin:2rem auto; padding:0 1.5rem 4rem;">

    <!-- En-tête -->
    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Budget</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>', 22, 'var(--accent-green)') ?>
            Calculateur de Budget
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">
            Bonjour <strong style="color:var(--accent-green);"><?= htmlspecialchars($_SESSION['user_nom']) ?></strong> — Saisissez vos données du mois
        </p>
    </div>

    <?php if ($resultat): ?>
    <!-- Résultat alerte -->
    <?php
    $alertColors = [
        'success' => ['rgba(0,229,160,0.08)','rgba(0,229,160,0.25)','var(--accent-green)', '<polyline points="20 6 9 17 4 12"/>'],
        'warning' => ['rgba(255,176,32,0.08)','rgba(255,176,32,0.25)','var(--warning)',      '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
        'danger'  => ['rgba(255,79,109,0.08)', 'rgba(255,79,109,0.25)', 'var(--danger)',     '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
    ];
    [$bg,$border,$color,$ipath] = $alertColors[$resultat['classe']];
    ?>
    <div style="background:<?= $bg ?>;border:1px solid <?= $border ?>;color:<?= $color ?>;padding:1rem 1.2rem;border-radius:12px;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.7rem;font-size:0.9rem;font-weight:500;">
        <?= icon($ipath, 18, $color) ?>
        <?= htmlspecialchars($alerte) ?>
    </div>

    <!-- KPI résultat -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
        <?php
        $kpis = [
            ['Revenus totaux',  $resultat['total_revenus'],  'var(--accent-green)', '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
            ['Dépenses totales',$resultat['total_depenses'], 'var(--accent-blue)',  '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>'],
            ['Reste à vivre',   $resultat['reste'],          $resultat['reste']>=0?'var(--accent-green)':'var(--danger)', '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        ];
        foreach($kpis as [$label,$val,$col,$ipath]): ?>
        <div class="card" style="border-top:2px solid <?= $col ?>;">
            <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.3rem;">
                <?= icon($ipath, 13, $col) ?> <?= $label ?>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:<?= $col ?>;">
                <?= number_format($val, 0) ?> <span style="font-size:0.85rem;">MAD</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Graphique donut -->
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/>', 16, 'var(--accent-green)') ?>
            Répartition des dépenses
        </h3>
        <canvas id="budgetChart" style="max-width:360px;margin:0 auto;display:block;"></canvas>
    </div>

    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem;">
        <a href="/master-money/tableau-de-bord.php" class="btn-primary">
            <?= icon('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>', 15) ?>
            Voir mon Dashboard
        </a>
        <a href="/master-money/calculateur.php" class="btn-secondary">
            <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>', 15) ?>
            Nouveau calcul
        </a>
    </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="POST">

        <!-- Sélecteur de mois -->
        <div class="card" style="margin-bottom:1rem;padding:1rem 1.4rem;">
            <label style="display:flex;align-items:center;gap:0.7rem;font-size:0.85rem;color:var(--text-secondary);">
                <?= icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', 16, 'var(--accent-green)') ?>
                Mois concerné :
                <input type="month" name="mois" value="<?= date('Y-m') ?>"
                       style="padding:0.4rem 0.7rem;border-radius:7px;font-size:0.85rem;margin-left:0.3rem;">
            </label>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

            <!-- Revenus -->
            <div class="card">
                <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;color:var(--accent-green);">
                    <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>', 16, 'var(--accent-green)') ?>
                    Revenus
                </h3>
                <?php
                $fields_rev = [
                    ['bourse',         'Bourse'],
                    ['aide_familiale',  'Aide familiale'],
                    ['emploi',         'Emploi étudiant'],
                ];
                foreach($fields_rev as [$name,$label]): ?>
                <div style="margin-bottom:0.9rem;">
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;"><?= $label ?> <span style="color:var(--text-muted);">(MAD)</span></label>
                    <input type="number" name="<?= $name ?>" value="<?= $_POST[$name] ?? 0 ?>" min="0"
                        style="width:100%;padding:0.65rem 0.9rem;border-radius:8px;font-size:0.9rem;">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Dépenses -->
            <div class="card">
                <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;color:var(--accent-blue);">
                    <?= icon('<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>', 16, 'var(--accent-blue)') ?>
                    Dépenses
                </h3>
                <?php
                $fields_dep = [
                    ['loyer',         'Loyer'],
                    ['transport',     'Transport'],
                    ['alimentation',  'Alimentation'],
                    ['loisirs',       'Loisirs'],
                    ['imprevus',      'Imprévus'],
                ];
                foreach($fields_dep as [$name,$label]): ?>
                <div style="margin-bottom:0.9rem;">
                    <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;"><?= $label ?> <span style="color:var(--text-muted);">(MAD)</span></label>
                    <input type="number" name="<?= $name ?>" value="<?= $_POST[$name] ?? 0 ?>" min="0"
                        style="width:100%;padding:0.65rem 0.9rem;border-radius:8px;font-size:0.9rem;">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="text-align:center;margin-top:1.5rem;">
            <button type="submit" class="btn-primary" style="padding:0.85rem 2.5rem;font-size:0.95rem;border-radius:10px;">
                <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>', 16) ?>
                Calculer mon budget
            </button>
        </div>
    </form>
</div>

<?php if ($resultat): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('budgetChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Loyer','Transport','Alimentation','Loisirs','Imprévus','Épargne'],
        datasets: [{
            data: [<?= $resultat['loyer']?>,<?= $resultat['transport']?>,<?= $resultat['alimentation']?>,<?= $resultat['loisirs']?>,<?= $resultat['imprevus']?>,<?= max(0,$resultat['reste'])?>],
            backgroundColor: ['#4f8ef7','#00e5a0','#9b6dff','#ffb020','#ff4f6d','#00ffb3'],
            borderWidth: 0, hoverOffset: 8
        }]
    },
    options: {
        cutout: '62%',
        plugins: { legend: { labels: { color:'#8888aa', font:{family:'DM Sans',size:12}, padding:14 } } }
    }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>