<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /master-money/connexion.php");
    exit;
}

$uid = $_SESSION['user_id'];

// ── FIX : s'assurer que les colonnes points et avatar existent ──
try { $pdo->exec("ALTER TABLE `utilisateurs` ADD COLUMN `points` INT DEFAULT 0"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE `utilisateurs` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) {}

function icon($p,$s=16,$c='currentColor'){
    return "<svg viewBox=\"0 0 24 24\" style=\"width:{$s}px;height:{$s}px;stroke:{$c};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$p}</svg>";
}

// ── Détecter colonnes disponibles dans budgets ──
$cols    = $pdo->query("SHOW COLUMNS FROM `budgets`")->fetchAll(PDO::FETCH_COLUMN);
$hasMois = in_array('mois', $cols);

// ── Dernier budget ──
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$uid]);
$dernier = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Historique 6 mois ──
$moisCol = $hasMois ? "mois" : "DATE_FORMAT(created_at,'%Y-%m')";
$stmt = $pdo->prepare("SELECT $moisCol as periode,
    (bourse+aide_familiale+emploi) as revenus,
    (loyer+transport+alimentation+loisirs+imprevus) as depenses,
    reste_a_vivre FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$uid]);
$historique = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// ── Points (colonne peut ne pas exister encore) ──
$points = 0;
try {
    $ptRow = $pdo->prepare("SELECT points FROM utilisateurs WHERE id=?");
    $ptRow->execute([$uid]);
    $points = $ptRow->fetchColumn() ?: 0;
} catch(Exception $e) {}

// ── Badges obtenus ──
$badgesObt = []; $badgesObtenusNoms = [];
try {
    $stmt = $pdo->prepare("SELECT b.nom,b.code FROM utilisateur_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.utilisateur_id=?");
    $stmt->execute([$uid]);
    $badgesObt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $badgesObtenusNoms = array_column($badgesObt,'nom');
} catch(Exception $e) {}

$allBadges = [];
try { $allBadges = $pdo->query("SELECT * FROM badges ORDER BY points_requis ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) {}

// ── Moyennes étudiants ──
$moyennes = ['loyer'=>1200,'alimentation'=>600,'transport'=>250,'loisirs'=>150,'imprevus'=>100];
try {
    $stmt = $pdo->query("SELECT categorie, montant_moyen FROM moyennes_etudiants WHERE annee=2026");
    foreach($stmt->fetchAll() as $row) $moyennes[$row['categorie']] = $row['montant_moyen'];
} catch(Exception $e) {}

// ── Attribution badges ──
function attribuerBadge($pdo, $uid, $code) {
    try {
        $b = $pdo->prepare("SELECT id FROM badges WHERE code=?"); $b->execute([$code]); $badge = $b->fetch();
        if (!$badge) return;
        $c = $pdo->prepare("SELECT id FROM utilisateur_badges WHERE utilisateur_id=? AND badge_id=?"); $c->execute([$uid,$badge['id']]);
        if (!$c->fetch()) {
            $pdo->prepare("INSERT INTO utilisateur_badges (utilisateur_id,badge_id) VALUES (?,?)")->execute([$uid,$badge['id']]);
            $pdo->prepare("UPDATE utilisateurs SET points=COALESCE(points,0)+10 WHERE id=?")->execute([$uid]);
        }
    } catch(Exception $e) {}
}
try {
    $nb = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id=?"); $nb->execute([$uid]);
    if ($nb->fetchColumn() >= 1) attribuerBadge($pdo,$uid,'premier_budget');
    if ($dernier && $dernier['reste_a_vivre'] > 0) attribuerBadge($pdo,$uid,'economiseur');
    $pos = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id=? AND reste_a_vivre>0"); $pos->execute([$uid]);
    $nbPos = $pos->fetchColumn();
    if ($nbPos >= 3) attribuerBadge($pdo,$uid,'bon_gestionnaire');
    if ($nbPos >= 6) attribuerBadge($pdo,$uid,'budget_master');
} catch(Exception $e) {}

// ── Recommandations ──
$recommandations = [];
if ($dernier) {
    if ($dernier['transport']    > $moyennes['transport'])    $recommandations[] = ['type'=>'warning','msg'=>'Transport ('.number_format($dernier['transport'],0).' MAD) dépasse la moyenne ('.$moyennes['transport'].' MAD). Pensez aux abonnements étudiants.'];
    if ($dernier['alimentation'] > $moyennes['alimentation']) $recommandations[] = ['type'=>'warning','msg'=>'Alimentation élevée ! Cuisiner maison peut vous faire économiser jusqu\'à 200 MAD/mois.'];
    if ($dernier['loisirs']      > $moyennes['loisirs'])      $recommandations[] = ['type'=>'warning','msg'=>'Loisirs au-dessus de la moyenne. Cherchez des activités gratuites sur le campus.'];
    if ($dernier['reste_a_vivre'] < 0)                        $recommandations[] = ['type'=>'danger', 'msg'=>'Budget déficitaire ce mois ! Réduisez les dépenses non essentielles en priorité.'];
    elseif ($dernier['reste_a_vivre'] > 500)                  $recommandations[] = ['type'=>'success','msg'=>'Excellent ! Vous pouvez mettre '.number_format($dernier['reste_a_vivre']*0.5,0).' MAD de côté pour votre épargne.'];
}
if (empty($recommandations)) $recommandations[] = ['type'=>'success','msg'=>'Votre budget semble équilibré. Continuez comme ça !'];

include 'includes/header.php';
?>

<div style="max-width:1100px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Tableau de bord</p>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;">
                Bonjour, <?= htmlspecialchars($_SESSION['user_nom']) ?>
            </h1>
            <p style="color:var(--text-secondary);font-size:0.85rem;margin-top:0.3rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',14,'var(--text-muted)') ?>
                <?= date('d/m/Y') ?>
                <span style="color:var(--border);">·</span>
                <?= icon('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',13,'var(--warning)') ?>
                <span style="color:var(--warning);font-weight:600;"><?= $points ?> points</span>
            </p>
        </div>
        <a href="/master-money/calculateur.php" class="btn-primary">
            <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',15) ?>
            Nouveau Budget
        </a>
    </div>

    <!-- KPI -->
    <?php if ($dernier):
        $revenus  = $dernier['bourse']+$dernier['aide_familiale']+$dernier['emploi'];
        $depenses = $dernier['loyer']+$dernier['transport']+$dernier['alimentation']+$dernier['loisirs']+$dernier['imprevus'];
        $reste    = $dernier['reste_a_vivre'];
        $taux     = $revenus > 0 ? round(($depenses/$revenus)*100) : 0;
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
        <?php
        $kpis = [
            ['Revenus du mois',  number_format($revenus,0).' MAD',  'var(--accent-green)', '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
            ['Dépenses du mois', number_format($depenses,0).' MAD', 'var(--accent-blue)',  '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>'],
            ['Reste à vivre',    number_format($reste,0).' MAD',    $reste>=0?'var(--accent-green)':'var(--danger)', '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
            ['Taux de dépense',  $taux.'%',                         'var(--warning)',      '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ];
        foreach($kpis as [$label,$val,$col,$ipath]): ?>
        <div class="card" style="border-left:3px solid <?= $col ?>;">
            <div style="font-size:0.7rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.4rem;">
                <?= icon($ipath,13,$col) ?> <?= $label ?>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:800;color:<?= $col ?>;"><?= $val ?></div>
            <?php if($label==='Taux de dépense'): ?>
            <div style="background:var(--bg-secondary);border-radius:4px;height:3px;margin-top:0.6rem;">
                <div style="background:<?= $col ?>;width:<?= min($taux,100) ?>%;height:3px;border-radius:4px;"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center;padding:3rem;margin-bottom:2rem;">
        <?= icon('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',44,'var(--text-muted)') ?>
        <p style="color:var(--text-secondary);margin-top:1rem;">Aucun budget enregistré. <a href="/master-money/calculateur.php" style="color:var(--accent-green);">Créez votre premier budget !</a></p>
    </div>
    <?php endif; ?>

    <!-- Graphiques -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10H12V2z"/>',16,'var(--accent-green)') ?>
                Répartition des dépenses
            </h3>
            <?php if($dernier): ?><canvas id="donutChart" height="220"></canvas>
            <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:2rem;">Pas de données</p><?php endif; ?>
        </div>
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>',16,'var(--accent-blue)') ?>
                Évolution mensuelle
            </h3>
            <?php if(!empty($historique)): ?><canvas id="lineChart" height="220"></canvas>
            <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:2rem;">Pas de données</p><?php endif; ?>
        </div>
    </div>

    <!-- Comparaison & Recommandations -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>',16,'var(--accent-blue)') ?>
                Vs Moyenne Étudiants
            </h3>
            <?php if($dernier):
                $cats = ['loyer'=>'Loyer','alimentation'=>'Alimentation','transport'=>'Transport','loisirs'=>'Loisirs'];
                foreach($cats as $key=>$label):
                    $val=$dernier[$key]; $moy=$moyennes[$key]??0;
                    $pct=$moy>0?min(round(($val/$moy)*100),100):0;
                    $col=$val>$moy?'var(--danger)':'var(--accent-green)';
            ?>
            <div style="margin-bottom:0.9rem;">
                <div style="display:flex;justify-content:space-between;font-size:0.81rem;color:var(--text-secondary);margin-bottom:0.3rem;">
                    <span><?= $label ?></span>
                    <span style="color:<?= $col ?>;"><?= number_format($val,0) ?> / <?= $moy ?> MAD</span>
                </div>
                <div style="background:var(--bg-secondary);border-radius:4px;height:5px;">
                    <div style="background:<?= $col ?>;width:<?= $pct ?>%;height:5px;border-radius:4px;"></div>
                </div>
            </div>
            <?php endforeach;
            else: ?><p style="color:var(--text-muted);">Créez un budget pour voir la comparaison.</p><?php endif; ?>
        </div>

        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',16,'var(--warning)') ?>
                Recommandations
            </h3>
            <?php foreach($recommandations as $r):
                $rColors = [
                    'success'=>['rgba(0,229,160,0.07)','var(--accent-green)','<polyline points="20 6 9 17 4 12"/>'],
                    'warning'=>['rgba(255,176,32,0.07)','var(--warning)',     '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'],
                    'danger' =>['rgba(255,79,109,0.07)','var(--danger)',      '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>'],
                ];
                [$rbg,$rcol,$ripath] = $rColors[$r['type']];
            ?>
            <div style="display:flex;gap:0.7rem;align-items:flex-start;margin-bottom:0.8rem;padding:0.75rem;background:<?= $rbg ?>;border-radius:9px;border-left:2px solid <?= $rcol ?>;">
                <?= icon($ripath,15,$rcol) ?>
                <p style="font-size:0.82rem;color:var(--text-secondary);line-height:1.55;margin:0;"><?= htmlspecialchars($r['msg']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Alertes -->
    <?php if($dernier && ($dernier['reste_a_vivre']<200 || $dernier['transport']>$moyennes['transport'] || $dernier['alimentation']>$moyennes['alimentation'])): ?>
    <div class="card" style="margin-bottom:2rem;border:1px solid rgba(255,79,109,0.2);">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1rem;color:var(--danger);display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',16,'var(--danger)') ?>
            Alertes Financières
        </h3>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
            <?php if($dernier['reste_a_vivre']<0): ?>
            <div style="background:rgba(255,79,109,0.07);border:1px solid rgba(255,79,109,0.18);padding:0.75rem 1rem;border-radius:8px;font-size:0.84rem;color:var(--danger);">
                <strong>Déficit budgétaire !</strong> Vos dépenses dépassent vos revenus de <?= number_format(abs($dernier['reste_a_vivre']),2) ?> MAD.
            </div>
            <?php elseif($dernier['reste_a_vivre']<200): ?>
            <div style="background:rgba(255,176,32,0.07);border:1px solid rgba(255,176,32,0.18);padding:0.75rem 1rem;border-radius:8px;font-size:0.84rem;color:var(--warning);">
                <strong>Budget serré !</strong> Il ne vous reste que <?= number_format($dernier['reste_a_vivre'],2) ?> MAD.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Badges -->
    <?php if(!empty($allBadges)): ?>
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',16,'var(--warning)') ?>
            Mes Badges
        </h3>
        <div style="display:flex;flex-wrap:wrap;gap:0.9rem;">
            <?php foreach($allBadges as $badge):
                $obtenu = in_array($badge['nom'],$badgesObtenusNoms); ?>
            <div style="text-align:center;padding:1rem 1.2rem;background:var(--bg-secondary);border-radius:12px;border:1px solid <?= $obtenu?'var(--accent-green)':'var(--border)' ?>;opacity:<?= $obtenu?'1':'0.4' ?>;min-width:90px;">
                <div style="display:flex;justify-content:center;margin-bottom:0.5rem;">
                    <?= icon('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',28,$obtenu?'var(--warning)':'var(--text-muted)') ?>
                </div>
                <div style="font-size:0.74rem;font-weight:600;color:<?= $obtenu?'var(--accent-green)':'var(--text-muted)' ?>;"><?= htmlspecialchars($badge['nom']) ?></div>
                <div style="font-size:0.63rem;color:<?= $obtenu?'var(--accent-green)':'var(--text-muted)' ?>;margin-top:0.15rem;"><?= $obtenu?'Obtenu':'Verrouillé' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <h3 style="font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',16,'var(--accent-green)') ?>
                Historique des budgets
            </h3>
            <a href="/master-money/calculateur.php" style="font-size:0.8rem;color:var(--accent-green);text-decoration:none;display:flex;align-items:center;gap:0.3rem;">
                <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',13,'var(--accent-green)') ?> Nouveau
            </a>
        </div>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$uid]); $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if(empty($budgets)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:2rem;">Aucun budget enregistré.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.86rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <?php foreach(['Période','Revenus','Dépenses','Reste','Statut'] as $th): ?>
                    <th style="padding:0.75rem 1rem;text-align:<?= in_array($th,['Période','Statut'])?'left':'right' ?>;color:var(--text-muted);font-size:0.68rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;"><?= $th ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($budgets as $b):
                $rev     = $b['bourse']+$b['aide_familiale']+$b['emploi'];
                $dep     = $b['loyer']+$b['transport']+$b['alimentation']+$b['loisirs']+$b['imprevus'];
                $periode = $hasMois&&!empty($b['mois'])?$b['mois']:date('Y-m',strtotime($b['created_at']));
            ?>
            <tr style="border-bottom:1px solid var(--border);transition:background 0.15s;"
                onmouseover="this.style.background='rgba(128,128,128,0.06)'" onmouseout="this.style.background='transparent'">
                <td style="padding:0.85rem 1rem;color:var(--text-primary);"><?= htmlspecialchars($periode) ?></td>
                <td style="padding:0.85rem 1rem;text-align:right;color:var(--accent-green);"><?= number_format($rev,0) ?> MAD</td>
                <td style="padding:0.85rem 1rem;text-align:right;color:var(--accent-blue);"><?= number_format($dep,0) ?> MAD</td>
                <td style="padding:0.85rem 1rem;text-align:right;color:<?= $b['reste_a_vivre']>=0?'var(--accent-green)':'var(--danger)' ?>;font-weight:600;"><?= number_format($b['reste_a_vivre'],0) ?> MAD</td>
                <td style="padding:0.85rem 1rem;">
                    <?php if($b['reste_a_vivre']>=500): ?>
                        <span style="background:rgba(0,229,160,0.1);color:var(--accent-green);padding:0.2rem 0.65rem;border-radius:50px;font-size:0.73rem;">Excellent</span>
                    <?php elseif($b['reste_a_vivre']>=0): ?>
                        <span style="background:rgba(255,176,32,0.1);color:var(--warning);padding:0.2rem 0.65rem;border-radius:50px;font-size:0.73rem;">Serré</span>
                    <?php else: ?>
                        <span style="background:rgba(255,79,109,0.1);color:var(--danger);padding:0.2rem 0.65rem;border-radius:50px;font-size:0.73rem;">Déficit</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if($dernier): ?>
new Chart(document.getElementById('donutChart'),{type:'doughnut',data:{labels:['Loyer','Transport','Alimentation','Loisirs','Imprévus','Épargne'],datasets:[{data:[<?= $dernier['loyer']?>,<?= $dernier['transport']?>,<?= $dernier['alimentation']?>,<?= $dernier['loisirs']?>,<?= $dernier['imprevus']?>,<?= max(0,$dernier['reste_a_vivre'])?>],backgroundColor:['#4f8ef7','#00e5a0','#9b6dff','#ffb020','#ff4f6d','#00ffb3'],borderWidth:0,hoverOffset:8}]},options:{cutout:'65%',plugins:{legend:{labels:{color:'#8888aa',font:{size:11},padding:12}}}}});
<?php endif; ?>
<?php if(!empty($historique)): ?>
new Chart(document.getElementById('lineChart'),{type:'line',data:{labels:<?= json_encode(array_column($historique,'periode')) ?>,datasets:[{label:'Revenus',data:<?= json_encode(array_map(fn($h)=>floatval($h['revenus']),$historique)) ?>,borderColor:'#00e5a0',backgroundColor:'rgba(0,229,160,0.08)',tension:0.4,fill:true,pointRadius:4,pointBackgroundColor:'#00e5a0'},{label:'Dépenses',data:<?= json_encode(array_map(fn($h)=>floatval($h['depenses']),$historique)) ?>,borderColor:'#ff4f6d',backgroundColor:'rgba(255,79,109,0.08)',tension:0.4,fill:true,pointRadius:4,pointBackgroundColor:'#ff4f6d'}]},options:{plugins:{legend:{labels:{color:'#8888aa',font:{size:11}}}},scales:{x:{ticks:{color:'#8888aa'},grid:{color:'rgba(128,128,128,0.08)'}},y:{ticks:{color:'#8888aa'},grid:{color:'rgba(128,128,128,0.08)'}}}}}});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>