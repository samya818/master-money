<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: /master-money/connexion.php"); exit; }
$uid = $_SESSION['user_id'];

function icon($p,$s=16,$c='currentColor'){return "<svg viewBox=\"0 0 24 24\" style=\"width:{$s}px;height:{$s}px;stroke:{$c};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$p}</svg>";}

// Dernier budget utilisateur
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]); $monBudget = $stmt->fetch(PDO::FETCH_ASSOC);

// Moyennes étudiants
$moyennes = ['loyer'=>1200,'alimentation'=>600,'transport'=>250,'loisirs'=>150,'imprevus'=>100];
try {
    $stmt = $pdo->query("SELECT categorie, montant_moyen FROM moyennes_etudiants WHERE annee=2026");
    foreach($stmt->fetchAll() as $row) $moyennes[$row['categorie']] = $row['montant_moyen'];
} catch(Exception $e) {}

// Moyenne globale tous les utilisateurs (anonymisée)
$avgRows = [];
try {
    $stmt = $pdo->query("SELECT
        AVG(loyer) as loyer, AVG(transport) as transport, AVG(alimentation) as alimentation,
        AVG(loisirs) as loisirs, AVG(imprevus) as imprevus,
        AVG(bourse+aide_familiale+emploi) as revenus,
        AVG(reste_a_vivre) as reste,
        COUNT(*) as nb
        FROM budgets b
        JOIN (SELECT utilisateur_id, MAX(created_at) as last FROM budgets GROUP BY utilisateur_id) latest
        ON b.utilisateur_id=latest.utilisateur_id AND b.created_at=latest.last");
    $avgRows = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) { $avgRows = null; }

include 'includes/header.php';
?>

<div style="max-width:1000px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Analyse</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',22,'var(--accent-green)') ?>
            Comparaison Étudiants
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">Comparez votre budget aux moyennes des étudiants UMI.</p>
    </div>

    <?php if (!$monBudget): ?>
    <div class="card" style="text-align:center;padding:3rem;">
        <?= icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',44,'var(--text-muted)') ?>
        <p style="color:var(--text-secondary);margin-top:1rem;">Vous n'avez pas encore de budget. <a href="/master-money/calculateur.php" style="color:var(--accent-green);">Créez votre premier budget</a> pour voir la comparaison.</p>
    </div>
    <?php else:
        $monRev  = $monBudget['bourse']+$monBudget['aide_familiale']+$monBudget['emploi'];
        $monDep  = $monBudget['loyer']+$monBudget['transport']+$monBudget['alimentation']+$monBudget['loisirs']+$monBudget['imprevus'];
        $monReste = $monBudget['reste_a_vivre'];
    ?>

    <!-- Score global -->
    <?php
    $score = 0;
    $cats = ['loyer','transport','alimentation','loisirs','imprevus'];
    foreach($cats as $c) { if($monBudget[$c] <= ($moyennes[$c]??999)) $score++; }
    $pctScore = round(($score/count($cats))*100);
    $scoreCol = $pctScore>=80?'var(--accent-green)':($pctScore>=60?'var(--warning)':'var(--danger)');
    ?>
    <div class="card" style="margin-bottom:2rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
        <div style="text-align:center;flex-shrink:0;">
            <div style="width:90px;height:90px;border-radius:50%;background:conic-gradient(<?= $scoreCol ?> <?= $pctScore ?>%, rgba(255,255,255,0.05) 0);display:flex;align-items:center;justify-content:center;position:relative;">
                <div style="width:70px;height:70px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;flex-direction:column;">
                    <span style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:<?= $scoreCol ?>;"><?= $pctScore ?>%</span>
                </div>
            </div>
            <p style="font-size:0.72rem;color:var(--text-muted);margin-top:0.5rem;">Score budget</p>
        </div>
        <div>
            <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:0.4rem;">
                <?php if($pctScore>=80): ?>Excellent gestionnaire !
                <?php elseif($pctScore>=60): ?>Bonne gestion globale
                <?php else: ?>Budget à optimiser
                <?php endif; ?>
            </h3>
            <p style="font-size:0.84rem;color:var(--text-secondary);line-height:1.6;max-width:500px;">
                <?= $score ?>/<?= count($cats) ?> catégories sont dans la moyenne étudiante.
                <?php if($pctScore<80): ?> Concentrez-vous sur les postes en rouge ci-dessous pour améliorer votre score.<?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Comparaison par catégorie -->
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',16,'var(--accent-blue)') ?>
            Votre budget vs Moyennes UMI
        </h3>

        <?php
        $catInfo = [
            'loyer'        => ['Loyer',        '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
            'alimentation' => ['Alimentation',  '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>'],
            'transport'    => ['Transport',     '<rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
            'loisirs'      => ['Loisirs',       '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>'],
            'imprevus'     => ['Imprévus',      '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'],
        ];
        foreach($catInfo as $key=>[$label,$ipath]):
            $mon = $monBudget[$key]??0;
            $moy = $moyennes[$key]??0;
            $diff = $mon - $moy;
            $col = $diff > 0 ? 'var(--danger)' : 'var(--accent-green)';
            $pctMon = $moy>0?min(round(($mon/$moy)*100),200):0;
            $pctMoy = 100;
        ?>
        <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.8rem;flex-wrap:wrap;gap:0.5rem;">
                <span style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;font-weight:600;">
                    <?= icon($ipath,16,$col) ?> <?= $label ?>
                </span>
                <div style="display:flex;align-items:center;gap:1rem;font-size:0.82rem;">
                    <span style="color:var(--text-muted);">Moyenne : <strong style="color:var(--text-secondary);"><?= $moy ?> MAD</strong></span>
                    <span style="color:<?= $col ?>;font-weight:600;">
                        Vous : <?= number_format($mon,0) ?> MAD
                        (<?= $diff>0?'+':''; ?><?= number_format($diff,0) ?> MAD)
                    </span>
                </div>
            </div>
            <!-- Double barre -->
            <div style="display:flex;flex-direction:column;gap:0.4rem;">
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <span style="font-size:0.7rem;color:var(--text-muted);width:60px;text-align:right;flex-shrink:0;">Vous</span>
                    <div style="flex:1;background:var(--bg-secondary);border-radius:4px;height:8px;">
                        <div style="background:<?= $col ?>;width:<?= min($pctMon,100) ?>%;height:8px;border-radius:4px;transition:width 0.8s;"></div>
                    </div>
                    <span style="font-size:0.72rem;color:<?= $col ?>;width:70px;font-weight:600;"><?= number_format($mon,0) ?> MAD</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <span style="font-size:0.7rem;color:var(--text-muted);width:60px;text-align:right;flex-shrink:0;">Moyenne</span>
                    <div style="flex:1;background:var(--bg-secondary);border-radius:4px;height:8px;">
                        <div style="background:rgba(136,136,170,0.4);width:100%;height:8px;border-radius:4px;"></div>
                    </div>
                    <span style="font-size:0.72px;color:var(--text-muted);width:70px;"><?= $moy ?> MAD</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Comparaison plateforme (anonymisée) -->
    <?php if($avgRows && $avgRows['nb']>1): ?>
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>',16,'var(--accent-blue)') ?>
            Moyenne de la plateforme Master Money
            <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;">(<?= $avgRows['nb'] ?> étudiants — données anonymisées)</span>
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-top:1rem;">
            <?php
            $platStats = [
                ['Revenus moy.',   round($avgRows['revenus']??0),  'var(--accent-green)', '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>'],
                ['Dépenses moy.',  round($avgRows['loyer']+$avgRows['transport']+$avgRows['alimentation']+$avgRows['loisirs']+$avgRows['imprevus']), 'var(--accent-blue)', '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>'],
                ['Reste moy.',     round($avgRows['reste']??0),    round($avgRows['reste']??0)>=0?'var(--accent-green)':'var(--danger)', '<line x1="12" y1="1" x2="12" y2="23"/>'],
                ['Loyer moy.',     round($avgRows['loyer']??0),    'var(--text-secondary)', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'],
                ['Transp. moy.',   round($avgRows['transport']??0),'var(--text-secondary)', '<rect x="1" y="3" width="15" height="13" rx="2"/>'],
            ];
            foreach($platStats as [$l,$v,$col,$ipath]): ?>
            <div style="padding:0.9rem;background:var(--bg-secondary);border-radius:9px;border:1px solid var(--border);">
                <div style="font-size:0.68rem;color:var(--text-muted);display:flex;align-items:center;gap:0.3rem;margin-bottom:0.4rem;"><?= icon($ipath,12,$col) ?> <?= $l ?></div>
                <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:<?= $col ?>;"><?= number_format($v,0) ?> <span style="font-size:0.72rem;">MAD</span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Radar chart -->
    <div class="card">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/>',16,'var(--accent-green)') ?>
            Vue radar — Vous vs Moyenne
        </h3>
        <canvas id="radarChart" style="max-width:420px;margin:0 auto;display:block;" height="300"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: ['Loyer','Transport','Alimentation','Loisirs','Imprévus'],
            datasets: [
                {
                    label: 'Vous',
                    data: [<?= $monBudget['loyer']?>,<?= $monBudget['transport']?>,<?= $monBudget['alimentation']?>,<?= $monBudget['loisirs']?>,<?= $monBudget['imprevus']?>],
                    backgroundColor: 'rgba(0,229,160,0.15)',
                    borderColor: '#00e5a0',
                    pointBackgroundColor: '#00e5a0',
                    pointRadius: 4
                },
                {
                    label: 'Moyenne UMI',
                    data: [<?= $moyennes['loyer']?>,<?= $moyennes['transport']?>,<?= $moyennes['alimentation']?>,<?= $moyennes['loisirs']?>,<?= $moyennes['imprevus']?>],
                    backgroundColor: 'rgba(79,142,247,0.1)',
                    borderColor: '#4f8ef7',
                    pointBackgroundColor: '#4f8ef7',
                    pointRadius: 4
                }
            ]
        },
        options: {
            scales: {
                r: {
                    ticks: { color:'#8888aa', font:{size:10}, backdropColor:'transparent' },
                    grid:  { color:'rgba(255,255,255,0.07)' },
                    pointLabels: { color:'#aaaacc', font:{size:12} },
                    angleLines: { color:'rgba(255,255,255,0.07)' }
                }
            },
            plugins: { legend: { labels: { color:'#8888aa', font:{size:12} } } }
        }
    });
    </script>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>