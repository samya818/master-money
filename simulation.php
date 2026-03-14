<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: /master-money/connexion.php"); exit; }
$uid = $_SESSION['user_id'];

function icon($p,$s=16,$c='currentColor'){return "<svg viewBox=\"0 0 24 24\" style=\"width:{$s}px;height:{$s}px;stroke:{$c};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$p}</svg>";}

// Charger dernier budget comme base
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]); $base = $stmt->fetch(PDO::FETCH_ASSOC);

$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bourse       = floatval($_POST['bourse']);
    $aide         = floatval($_POST['aide_familiale']);
    $emploi       = floatval($_POST['emploi']);
    $loyer        = floatval($_POST['loyer']);
    $transport    = floatval($_POST['transport']);
    $alimentation = floatval($_POST['alimentation']);
    $loisirs      = floatval($_POST['loisirs']);
    $imprevus     = floatval($_POST['imprevus']);
    $epargne_obj  = floatval($_POST['epargne_objectif'] ?? 0);
    $duree        = intval($_POST['duree_mois'] ?? 6);

    $revenus  = $bourse + $aide + $emploi;
    $depenses = $loyer + $transport + $alimentation + $loisirs + $imprevus;
    $reste    = $revenus - $depenses;
    $disponible = $reste - $epargne_obj;

    $projection = [];
    $cumul = 0;
    for ($i = 1; $i <= $duree; $i++) {
        $cumul += max(0, $epargne_obj);
        $projection[] = ['mois' => 'M+'.$i, 'cumul' => $cumul, 'reste' => $disponible];
    }

    $resultat = compact('revenus','depenses','reste','disponible','epargne_obj','duree','projection','loyer','transport','alimentation','loisirs','imprevus','bourse','aide','emploi');
}

include 'includes/header.php';
?>

<div style="max-width:900px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Outil</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',22,'var(--accent-green)') ?>
            Simulation Budgétaire
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">Projetez votre épargne sur plusieurs mois avec différents scénarios.</p>
    </div>

    <!-- Formulaire simulation -->
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>',16,'var(--accent-green)') ?>
            Paramètres de simulation
            <?php if($base): ?><span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;margin-left:0.5rem;">— Pré-rempli depuis votre dernier budget</span><?php endif; ?>
        </h3>
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

            <div style="grid-column:span 2;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="card" style="background:var(--bg-secondary);border:none;padding:1rem;">
                    <p style="font-size:0.72rem;color:var(--accent-green);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.8rem;display:flex;align-items:center;gap:0.3rem;">
                        <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>',13,'var(--accent-green)') ?> Revenus
                    </p>
                    <?php
                    $revFields = [['bourse','Bourse'],['aide_familiale','Aide familiale'],['emploi','Emploi étudiant']];
                    foreach($revFields as [$n,$l]): ?>
                    <div style="margin-bottom:0.7rem;">
                        <label style="font-size:0.76rem;color:var(--text-secondary);display:block;margin-bottom:0.28rem;"><?= $l ?> (MAD)</label>
                        <input type="number" name="<?= $n ?>" value="<?= $_POST[$n]??($base[$n]??0) ?>" min="0"
                            style="width:100%;padding:0.6rem 0.8rem;border-radius:7px;font-size:0.87rem;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card" style="background:var(--bg-secondary);border:none;padding:1rem;">
                    <p style="font-size:0.72rem;color:var(--accent-blue);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.8rem;display:flex;align-items:center;gap:0.3rem;">
                        <?= icon('<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>',13,'var(--accent-blue)') ?> Dépenses
                    </p>
                    <?php
                    $depFields = [['loyer','Loyer'],['transport','Transport'],['alimentation','Alimentation'],['loisirs','Loisirs'],['imprevus','Imprévus']];
                    foreach($depFields as [$n,$l]): ?>
                    <div style="margin-bottom:0.7rem;">
                        <label style="font-size:0.76rem;color:var(--text-secondary);display:block;margin-bottom:0.28rem;"><?= $l ?> (MAD)</label>
                        <input type="number" name="<?= $n ?>" value="<?= $_POST[$n]??($base[$n]??0) ?>" min="0"
                            style="width:100%;padding:0.6rem 0.8rem;border-radius:7px;font-size:0.87rem;">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Paramètres épargne -->
            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;display:flex;align-items:center;gap:0.4rem;">
                    <?= icon('<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',13,'var(--warning)') ?> Objectif d'épargne mensuelle (MAD)
                </label>
                <input type="number" name="epargne_objectif" value="<?= $_POST['epargne_objectif']??200 ?>" min="0"
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>
            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;display:flex;align-items:center;gap:0.4rem;">
                    <?= icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',13,'var(--warning)') ?> Durée de simulation (mois)
                </label>
                <input type="number" name="duree_mois" value="<?= $_POST['duree_mois']??6 ?>" min="1" max="24"
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>

            <div style="grid-column:span 2;text-align:center;margin-top:0.5rem;">
                <button type="submit" class="btn-primary" style="padding:0.82rem 2.5rem;font-size:0.92rem;border-radius:10px;">
                    <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>',16) ?> Lancer la simulation
                </button>
            </div>
        </form>
    </div>

    <?php if ($resultat): ?>
    <!-- Résultats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
        <?php
        $kpis = [
            ['Revenus','var(--accent-green)',$resultat['revenus'],'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>'],
            ['Dépenses','var(--accent-blue)',$resultat['depenses'],'<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>'],
            ['Reste / mois',$resultat['reste']>=0?'var(--accent-green)':'var(--danger)',$resultat['reste'],'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        ];
        foreach($kpis as [$l,$col,$val,$ipath]): ?>
        <div class="card" style="border-top:2px solid <?= $col ?>;text-align:center;">
            <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;display:flex;align-items:center;justify-content:center;gap:0.3rem;">
                <?= icon($ipath,13,$col) ?> <?= $l ?>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:<?= $col ?>;"><?= number_format($val,0) ?> <span style="font-size:0.8rem;">MAD</span></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Projection -->
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',16,'var(--accent-green)') ?>
            Projection d'épargne sur <?= $resultat['duree'] ?> mois
        </h3>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.2rem;">
            En épargnant <strong style="color:var(--accent-green);"><?= number_format($resultat['epargne_obj'],0) ?> MAD/mois</strong>,
            vous accumulerez <strong style="color:var(--warning);"><?= number_format($resultat['epargne_obj']*$resultat['duree'],0) ?> MAD</strong> en <?= $resultat['duree'] ?> mois.
        </p>
        <canvas id="projChart" height="200"></canvas>
    </div>

    <!-- Tableau projection -->
    <div class="card">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',15,'var(--accent-blue)') ?>
            Détail mensuel
        </h3>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.84rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <?php foreach(['Mois','Épargne cumulée','Disponible/mois','Statut'] as $th): ?>
                    <th style="padding:0.7rem 1rem;text-align:<?= $th==='Mois'?'left':'right' ?>;color:var(--text-muted);font-size:0.68rem;text-transform:uppercase;letter-spacing:1px;"><?= $th ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($resultat['projection'] as $p): ?>
            <tr style="border-bottom:1px solid var(--border);" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding:0.75rem 1rem;color:var(--text-primary);font-weight:500;"><?= $p['mois'] ?></td>
                <td style="padding:0.75rem 1rem;text-align:right;color:var(--accent-green);font-weight:600;"><?= number_format($p['cumul'],0) ?> MAD</td>
                <td style="padding:0.75rem 1rem;text-align:right;color:<?= $p['reste']>=0?'var(--accent-blue)':'var(--danger)' ?>;"><?= number_format($p['reste'],0) ?> MAD</td>
                <td style="padding:0.75rem 1rem;text-align:right;">
                    <?php if($p['reste']>=0): ?>
                    <span style="background:rgba(0,229,160,0.1);color:var(--accent-green);padding:0.18rem 0.6rem;border-radius:50px;font-size:0.72rem;">Équilibré</span>
                    <?php else: ?>
                    <span style="background:rgba(255,79,109,0.1);color:var(--danger);padding:0.18rem 0.6rem;border-radius:50px;font-size:0.72rem;">Déficit</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('projChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($resultat['projection'],'mois')) ?>,
            datasets: [{
                label: 'Épargne cumulée (MAD)',
                data: <?= json_encode(array_column($resultat['projection'],'cumul')) ?>,
                backgroundColor: 'rgba(0,229,160,0.25)',
                borderColor: '#00e5a0',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            plugins: { legend: { labels: { color:'#8888aa', font:{size:11} } } },
            scales: {
                x: { ticks:{color:'#8888aa'}, grid:{color:'rgba(255,255,255,0.04)'} },
                y: { ticks:{color:'#8888aa'}, grid:{color:'rgba(255,255,255,0.04)'} }
            }
        }
    });
    </script>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>