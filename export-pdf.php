<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /master-money/connexion.php");
    exit;
}

$uid = $_SESSION['user_id'];

// ── Infos utilisateur ──
$stmtU = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=?");
$stmtU->execute([$uid]);
$user = $stmtU->fetch(PDO::FETCH_ASSOC);

// ── Budgets (12 derniers) ──
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT 12");
$stmt->execute([$uid]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Dernier budget ──
$dernier = $budgets[0] ?? null;

// ── Stats globales ──
$stmtS = $pdo->prepare("SELECT
    COUNT(*) as nb_budgets,
    AVG(reste_a_vivre) as moy_reste,
    SUM(bourse+aide_familiale+emploi) as total_revenus,
    SUM(loyer+transport+alimentation+loisirs+imprevus) as total_depenses,
    SUM(CASE WHEN reste_a_vivre > 0 THEN reste_a_vivre ELSE 0 END) as total_epargne
    FROM budgets WHERE utilisateur_id=?");
$stmtS->execute([$uid]);
$stats = $stmtS->fetch(PDO::FETCH_ASSOC);

// ── Dépenses ce mois ──
$stmtD = $pdo->prepare("SELECT categorie, SUM(montant) as total FROM depenses
    WHERE utilisateur_id=? AND MONTH(date_depense)=MONTH(CURDATE()) GROUP BY categorie");
$stmtD->execute([$uid]);
$depMois = $stmtD->fetchAll(PDO::FETCH_ASSOC);

// ── Badges ──
$badges = [];
try {
    $stmtB = $pdo->prepare("SELECT b.icone, b.nom FROM utilisateur_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.utilisateur_id=?");
    $stmtB->execute([$uid]);
    $badges = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Calculs dernier budget
$rev = $dep = $reste = 0;
if ($dernier) {
    $rev   = $dernier['bourse'] + $dernier['aide_familiale'] + $dernier['emploi'];
    $dep   = $dernier['loyer'] + $dernier['transport'] + $dernier['alimentation'] + $dernier['loisirs'] + $dernier['imprevus'];
    $reste = $dernier['reste_a_vivre'];
}
$periode = $dernier ? ($dernier['mois'] ?? date('Y-m', strtotime($dernier['created_at']))) : date('Y-m');
?>

<div style="max-width:1000px; margin:2.5rem auto; padding:0 2rem 4rem;">

    <!-- Titre page -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:2.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <p style="color:var(--accent-green); font-size:0.75rem; letter-spacing:2px; text-transform:uppercase; margin-bottom:0.4rem;">Export</p>
            <h1 style="font-family:'Syne',sans-serif; font-size:2rem; font-weight:800;">📄 Export de Rapport</h1>
            <p style="color:var(--text-secondary); font-size:0.88rem; margin-top:0.3rem;">Téléchargez votre rapport budgétaire complet en PDF ou CSV.</p>
        </div>
        <!-- Boutons export -->
        <div style="display:flex; gap:0.8rem; flex-wrap:wrap; align-items:center;">
            <button onclick="exporterCSV()" class="btn-secondary" style="border-radius:10px;">
                📊 CSV
            </button>
            <button onclick="exporterPDF()" class="btn-primary" style="border-radius:10px;" id="btn-pdf">
                📄 Télécharger PDF
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         RAPPORT — zone capturée en PDF
    ══════════════════════════════════════ -->
    <div id="rapport-pdf" style="background:white; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.3);">

        <!-- ── EN-TÊTE ── -->
        <div style="background:linear-gradient(135deg,#0d0d1a 0%,#111827 50%,#0d1f15 100%); padding:2.5rem; color:white; position:relative; overflow:hidden;">
            <!-- Cercles décoratifs -->
            <div style="position:absolute; top:-40px; right:-40px; width:200px; height:200px; border-radius:50%; background:rgba(0,229,160,0.06);"></div>
            <div style="position:absolute; bottom:-30px; left:30%; width:150px; height:150px; border-radius:50%; background:rgba(79,142,247,0.06);"></div>

            <div style="display:flex; justify-content:space-between; align-items:flex-start; position:relative; flex-wrap:wrap; gap:1rem;">
                <div>
                    <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:0.8rem;">
                        <div style="font-size:2rem;">💰</div>
                        <div>
                            <h1 style="font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:900; color:white; letter-spacing:-0.5px; margin:0;">MASTER MONEY</h1>
                            <p style="color:#00e5a0; font-size:0.7rem; letter-spacing:2px; text-transform:uppercase; margin:0;">Rapport Budgétaire Mensuel</p>
                        </div>
                    </div>
                    <p style="color:rgba(255,255,255,0.4); font-size:0.75rem;">Généré le <?= date('d/m/Y à H:i') ?></p>
                </div>
                <div style="text-align:right;">
                    <div style="background:rgba(0,229,160,0.15); border:1px solid rgba(0,229,160,0.3); border-radius:10px; padding:0.8rem 1.2rem;">
                        <p style="font-size:0.65rem; color:rgba(255,255,255,0.5); margin-bottom:0.3rem; text-transform:uppercase; letter-spacing:1px;">Période</p>
                        <p style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; color:#00e5a0;"><?= $periode ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding:2rem; background:white; color:#1a1a2e;">

            <!-- ── INFOS ÉTUDIANT ── -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div style="background:#f8faff; border-radius:10px; padding:1.2rem; border-left:4px solid #00e5a0;">
                    <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.6rem;">👤 Étudiant(e)</p>
                    <p style="font-family:'Syne',sans-serif; font-weight:800; color:#1a1a2e; font-size:1rem; margin-bottom:0.2rem;"><?= htmlspecialchars($user['nom']) ?></p>
                    <p style="color:#666; font-size:0.8rem; margin-bottom:0.2rem;"><?= htmlspecialchars($user['email']) ?></p>
                    <p style="color:#888; font-size:0.72rem;">Université Moulay Ismaïl — UMI</p>
                </div>
                <div style="background:#f8faff; border-radius:10px; padding:1.2rem; border-left:4px solid #4f8ef7;">
                    <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.6rem;">📊 Statistiques globales</p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                        <div>
                            <p style="font-size:0.65rem; color:#888; margin-bottom:0.2rem;">Budgets créés</p>
                            <p style="font-family:'Syne',sans-serif; font-weight:800; color:#4f8ef7; font-size:1.1rem;"><?= $stats['nb_budgets'] ?? 0 ?></p>
                        </div>
                        <div>
                            <p style="font-size:0.65rem; color:#888; margin-bottom:0.2rem;">Épargne totale</p>
                            <p style="font-family:'Syne',sans-serif; font-weight:800; color:#00a87a; font-size:1.1rem;"><?= number_format($stats['total_epargne'] ?? 0, 0) ?> MAD</p>
                        </div>
                        <div>
                            <p style="font-size:0.65rem; color:#888; margin-bottom:0.2rem;">Reste moyen</p>
                            <p style="font-family:'Syne',sans-serif; font-weight:800; color:<?= ($stats['moy_reste']??0) >= 0 ? '#00a87a' : '#e74c3c' ?>; font-size:1.1rem;"><?= number_format($stats['moy_reste'] ?? 0, 0) ?> MAD</p>
                        </div>
                        <div>
                            <p style="font-size:0.65rem; color:#888; margin-bottom:0.2rem;">Points gagnés</p>
                            <p style="font-family:'Syne',sans-serif; font-weight:800; color:#ffb020; font-size:1.1rem;">⭐ <?= $user['points'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── KPI DERNIER BUDGET ── -->
            <?php if ($dernier): ?>
            <div style="margin-bottom:1.5rem;">
                <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.8rem; padding-bottom:0.4rem; border-bottom:2px solid #f0f0f0;">💰 Budget du mois — <?= $periode ?></p>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.8rem;">
                    <div style="text-align:center; background:linear-gradient(135deg,#f0fff8,#e8f8f0); border-radius:10px; padding:1.2rem; border:1px solid #b8f0d8;">
                        <p style="font-size:0.62rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.4rem;">Total Revenus</p>
                        <p style="font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:900; color:#00a87a;"><?= number_format($rev, 0) ?></p>
                        <p style="font-size:0.65rem; color:#aaa; margin-top:0.2rem;">MAD</p>
                    </div>
                    <div style="text-align:center; background:linear-gradient(135deg,#f0f4ff,#e8eeff); border-radius:10px; padding:1.2rem; border:1px solid #c0d0f8;">
                        <p style="font-size:0.62rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.4rem;">Total Dépenses</p>
                        <p style="font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:900; color:#4f8ef7;"><?= number_format($dep, 0) ?></p>
                        <p style="font-size:0.65rem; color:#aaa; margin-top:0.2rem;">MAD</p>
                    </div>
                    <div style="text-align:center; background:<?= $reste>=0?'linear-gradient(135deg,#f0fff8,#e8f8f0)':'linear-gradient(135deg,#fff0f0,#ffe8e8)' ?>; border-radius:10px; padding:1.2rem; border:1px solid <?= $reste>=0?'#b8f0d8':'#f8c0c0' ?>;">
                        <p style="font-size:0.62rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.4rem;">Reste à Vivre</p>
                        <p style="font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:900; color:<?= $reste>=0?'#00a87a':'#e74c3c' ?>;"><?= number_format($reste, 0) ?></p>
                        <p style="font-size:0.65rem; color:#aaa; margin-top:0.2rem;">MAD</p>
                    </div>
                </div>
            </div>

            <!-- ── DÉTAIL DÉPENSES ── -->
            <div style="margin-bottom:1.5rem;">
                <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.8rem; padding-bottom:0.4rem; border-bottom:2px solid #f0f0f0;">📊 Répartition des dépenses</p>
                <?php
                $cats = [
                    'Loyer'        => ['val'=>$dernier['loyer'],        'color'=>'#4f8ef7'],
                    'Transport'    => ['val'=>$dernier['transport'],    'color'=>'#00e5a0'],
                    'Alimentation' => ['val'=>$dernier['alimentation'], 'color'=>'#9b6dff'],
                    'Loisirs'      => ['val'=>$dernier['loisirs'],      'color'=>'#ffb020'],
                    'Imprévus'     => ['val'=>$dernier['imprevus'],     'color'=>'#ff4f6d'],
                ];
                foreach ($cats as $label => $c):
                    $pct = $dep > 0 ? round(($c['val']/$dep)*100) : 0;
                ?>
                <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.7rem;">
                    <div style="width:100px; font-size:0.8rem; color:#444; flex-shrink:0;"><?= $label ?></div>
                    <div style="flex:1; background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
                        <div style="background:<?= $c['color'] ?>; width:<?= $pct ?>%; height:8px; border-radius:4px;"></div>
                    </div>
                    <div style="width:100px; text-align:right; font-size:0.8rem; color:#444; font-weight:600;"><?= number_format($c['val'],0) ?> MAD</div>
                    <div style="width:35px; text-align:right; font-size:0.72rem; color:#888;"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- ── HISTORIQUE ── -->
            <?php if (!empty($budgets)): ?>
            <div style="margin-bottom:1.5rem;">
                <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.8rem; padding-bottom:0.4rem; border-bottom:2px solid #f0f0f0;">📋 Historique des budgets</p>
                <table style="width:100%; border-collapse:collapse; font-size:0.78rem;">
                    <thead>
                        <tr style="background:#f5f5ff;">
                            <th style="padding:0.6rem 0.8rem; text-align:left; color:#666; font-weight:600; border-bottom:2px solid #eee;">Période</th>
                            <th style="padding:0.6rem 0.8rem; text-align:right; color:#666; font-weight:600; border-bottom:2px solid #eee;">Revenus</th>
                            <th style="padding:0.6rem 0.8rem; text-align:right; color:#666; font-weight:600; border-bottom:2px solid #eee;">Dépenses</th>
                            <th style="padding:0.6rem 0.8rem; text-align:right; color:#666; font-weight:600; border-bottom:2px solid #eee;">Épargne</th>
                            <th style="padding:0.6rem 0.8rem; text-align:center; color:#666; font-weight:600; border-bottom:2px solid #eee;">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($budgets, 0, 8) as $i => $b):
                        $r = $b['bourse']+$b['aide_familiale']+$b['emploi'];
                        $d = $b['loyer']+$b['transport']+$b['alimentation']+$b['loisirs']+$b['imprevus'];
                        $p = !empty($b['mois']) ? $b['mois'] : date('Y-m', strtotime($b['created_at']));
                        $bg = $i % 2 === 0 ? '#ffffff' : '#fafafe';
                    ?>
                    <tr style="background:<?= $bg ?>;">
                        <td style="padding:0.6rem 0.8rem; color:#333; border-bottom:1px solid #f0f0f0; font-weight:600;"><?= htmlspecialchars($p) ?></td>
                        <td style="padding:0.6rem 0.8rem; text-align:right; color:#00a87a; border-bottom:1px solid #f0f0f0;"><?= number_format($r,0) ?> MAD</td>
                        <td style="padding:0.6rem 0.8rem; text-align:right; color:#4f8ef7; border-bottom:1px solid #f0f0f0;"><?= number_format($d,0) ?> MAD</td>
                        <td style="padding:0.6rem 0.8rem; text-align:right; font-weight:700; color:<?= $b['reste_a_vivre']>=0?'#00a87a':'#e74c3c' ?>; border-bottom:1px solid #f0f0f0;"><?= number_format($b['reste_a_vivre'],0) ?> MAD</td>
                        <td style="padding:0.6rem 0.8rem; text-align:center; border-bottom:1px solid #f0f0f0;">
                            <?php if ($b['reste_a_vivre'] >= 500): ?>
                            <span style="background:#e8fff4; color:#00a87a; padding:0.15rem 0.6rem; border-radius:50px; font-size:0.68rem; font-weight:600;">✅ Excellent</span>
                            <?php elseif ($b['reste_a_vivre'] >= 0): ?>
                            <span style="background:#fffbea; color:#d4910a; padding:0.15rem 0.6rem; border-radius:50px; font-size:0.68rem; font-weight:600;">⚡ Serré</span>
                            <?php else: ?>
                            <span style="background:#fff0f0; color:#e74c3c; padding:0.15rem 0.6rem; border-radius:50px; font-size:0.68rem; font-weight:600;">🚨 Déficit</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- ── BADGES ── -->
            <?php if (!empty($badges)): ?>
            <div style="margin-bottom:1.5rem;">
                <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.8rem; padding-bottom:0.4rem; border-bottom:2px solid #f0f0f0;">🏅 Badges obtenus</p>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                    <?php foreach ($badges as $b): ?>
                    <span style="background:#f0fff8; border:1px solid #b8f0d8; color:#00a87a; padding:0.3rem 0.8rem; border-radius:50px; font-size:0.75rem; font-weight:600;">
                        <?= $b['icone'] ?> <?= htmlspecialchars($b['nom']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── RECOMMANDATIONS ── -->
            <?php if ($dernier): ?>
            <div style="margin-bottom:1.5rem;">
                <p style="font-size:0.65rem; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:0.8rem; padding-bottom:0.4rem; border-bottom:2px solid #f0f0f0;">💡 Recommandations personnalisées</p>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <?php
                    $moyennes = ['loyer'=>1200,'alimentation'=>600,'transport'=>250,'loisirs'=>150];
                    if ($dernier['transport'] > $moyennes['transport']):
                    ?>
                    <div style="display:flex; gap:0.6rem; padding:0.6rem 0.8rem; background:#fffbea; border-radius:6px; border-left:3px solid #ffb020; font-size:0.78rem; color:#444;">
                        🚌 Votre transport (<?= number_format($dernier['transport'],0) ?> MAD) dépasse la moyenne (<?= $moyennes['transport'] ?> MAD). Pensez aux abonnements étudiants.
                    </div>
                    <?php endif; ?>
                    <?php if ($dernier['alimentation'] > $moyennes['alimentation']): ?>
                    <div style="display:flex; gap:0.6rem; padding:0.6rem 0.8rem; background:#fffbea; border-radius:6px; border-left:3px solid #ffb020; font-size:0.78rem; color:#444;">
                        🛒 Alimentation élevée (<?= number_format($dernier['alimentation'],0) ?> MAD). Cuisiner à la maison peut vous faire économiser jusqu'à 200 MAD/mois.
                    </div>
                    <?php endif; ?>
                    <?php if ($reste > 500): ?>
                    <div style="display:flex; gap:0.6rem; padding:0.6rem 0.8rem; background:#f0fff8; border-radius:6px; border-left:3px solid #00a87a; font-size:0.78rem; color:#444;">
                        💰 Excellent ! Vous pouvez épargner <?= number_format($reste*0.5,0) ?> MAD supplémentaires ce mois.
                    </div>
                    <?php elseif ($reste < 0): ?>
                    <div style="display:flex; gap:0.6rem; padding:0.6rem 0.8rem; background:#fff0f0; border-radius:6px; border-left:3px solid #e74c3c; font-size:0.78rem; color:#444;">
                        🚨 Budget déficitaire. Réduisez les dépenses non essentielles en priorité.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── FOOTER RAPPORT ── -->
            <div style="margin-top:1.5rem; padding-top:1rem; border-top:2px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <div>
                    <p style="font-size:0.7rem; color:#888;">Master Money — Plateforme Budgétaire Étudiante</p>
                    <p style="font-size:0.65rem; color:#bbb;">Université Moulay Ismaïl (UMI) · G-IADT SG 82 · <?= date('Y') ?></p>
                </div>
                <div style="text-align:right;">
                    <p style="font-size:0.65rem; color:#bbb;">Rapport confidentiel — Usage personnel uniquement</p>
                    <p style="font-size:0.65rem; color:#bbb;">💰 Master Money · <?= date('d/m/Y') ?></p>
                </div>
            </div>

        </div><!-- fin padding -->
    </div><!-- fin rapport-pdf -->

</div><!-- fin container -->

<!-- ══ LIBRARIES ══ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// ── EXPORT PDF ──
async function exporterPDF() {
    const btn = document.getElementById('btn-pdf');
    btn.textContent = '⏳ Génération...';
    btn.disabled = true;

    try {
        const element = document.getElementById('rapport-pdf');
        const canvas = await html2canvas(element, {
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false,
            allowTaint: true
        });

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        const pageWidth  = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const imgWidth   = pageWidth - 20; // marges 10mm de chaque côté
        const imgHeight  = (canvas.height * imgWidth) / canvas.width;
        const imgData    = canvas.toDataURL('image/png', 1.0);

        // Si le rapport dépasse une page, on pagine
        let position = 10;
        let remainingHeight = imgHeight;

        if (imgHeight <= pageHeight - 20) {
            // Tient sur une page
            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
        } else {
            // Plusieurs pages
            let page = 1;
            while (remainingHeight > 0) {
                if (page > 1) pdf.addPage();
                const sourceY = (page - 1) * (pageHeight - 20) * (canvas.width / imgWidth);
                const sliceHeight = Math.min(
                    (pageHeight - 20) * (canvas.width / imgWidth),
                    remainingHeight * (canvas.width / imgWidth)
                );

                // Canvas slice
                const sliceCanvas = document.createElement('canvas');
                sliceCanvas.width  = canvas.width;
                sliceCanvas.height = sliceHeight;
                const ctx = sliceCanvas.getContext('2d');
                ctx.drawImage(canvas, 0, -sourceY);
                const sliceData = sliceCanvas.toDataURL('image/png', 1.0);

                pdf.addImage(sliceData, 'PNG', 10, 10, imgWidth, Math.min(pageHeight - 20, remainingHeight));
                remainingHeight -= (pageHeight - 20);
                page++;
            }
        }

        // Métadonnées PDF
        pdf.setProperties({
            title: 'Rapport Master Money — <?= htmlspecialchars($user['nom']) ?>',
            subject: 'Rapport Budgétaire Étudiant',
            author: 'Master Money — UMI',
            creator: 'Master Money App'
        });

        pdf.save('master-money-rapport-<?= $periode ?>.pdf');
        btn.textContent = '✅ PDF téléchargé !';
        setTimeout(() => {
            btn.textContent = '📄 Télécharger PDF';
            btn.disabled = false;
        }, 3000);

    } catch (error) {
        console.error('Erreur PDF:', error);
        btn.textContent = '❌ Erreur — Réessayez';
        btn.disabled = false;
    }
}

// ── EXPORT CSV ──
function exporterCSV() {
    const BOM = '\uFEFF'; // UTF-8 BOM pour Excel
    const rows = [
        ['Rapport Master Money — <?= htmlspecialchars($user['nom']) ?>'],
        ['Généré le', '<?= date('d/m/Y H:i') ?>'],
        [''],
        ['HISTORIQUE DES BUDGETS'],
        ['Période', 'Bourse (MAD)', 'Aide familiale (MAD)', 'Emploi (MAD)', 'Total Revenus (MAD)', 'Loyer (MAD)', 'Transport (MAD)', 'Alimentation (MAD)', 'Loisirs (MAD)', 'Imprévus (MAD)', 'Total Dépenses (MAD)', 'Reste à Vivre (MAD)', 'Statut'],
        <?php foreach ($budgets as $b):
            $r = $b['bourse']+$b['aide_familiale']+$b['emploi'];
            $d = $b['loyer']+$b['transport']+$b['alimentation']+$b['loisirs']+$b['imprevus'];
            $p = !empty($b['mois']) ? $b['mois'] : date('Y-m', strtotime($b['created_at']));
            $statut = $b['reste_a_vivre'] >= 500 ? 'Excellent' : ($b['reste_a_vivre'] >= 0 ? 'Serré' : 'Déficit');
        ?>
        ['<?= $p ?>','<?= $b['bourse'] ?>','<?= $b['aide_familiale'] ?>','<?= $b['emploi'] ?>','<?= $r ?>','<?= $b['loyer'] ?>','<?= $b['transport'] ?>','<?= $b['alimentation'] ?>','<?= $b['loisirs'] ?>','<?= $b['imprevus'] ?>','<?= $d ?>','<?= $b['reste_a_vivre'] ?>','<?= $statut ?>'],
        <?php endforeach; ?>
        [''],
        ['STATISTIQUES GLOBALES'],
        ['Total budgets créés', '<?= $stats['nb_budgets'] ?? 0 ?>'],
        ['Épargne totale', '<?= number_format($stats['total_epargne']??0,2) ?>'],
        ['Reste à vivre moyen', '<?= number_format($stats['moy_reste']??0,2) ?>'],
    ];

    const csvContent = BOM + rows.map(r => r.map(c => `"${c}"`).join(',')).join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = 'master-money-<?= $periode ?>.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>