<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: /master-money/connexion.php"); exit; }
$uid = $_SESSION['user_id'];
$msg = "";

function icon($p,$s=16,$c='currentColor'){return "<svg viewBox=\"0 0 24 24\" style=\"width:{$s}px;height:{$s}px;stroke:{$c};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$p}</svg>";}

if (isset($_POST['ajouter'])) {
    $pdo->prepare("INSERT INTO objectifs (utilisateur_id,titre,montant_cible,duree_mois,date_debut) VALUES (?,?,?,?,?)")
        ->execute([$uid, htmlspecialchars($_POST['titre']), floatval($_POST['montant_cible']), intval($_POST['duree_mois']), $_POST['date_debut']]);
    $msg = "success";
}

if (isset($_POST['epargner'])) {
    $id   = intval($_POST['objectif_id']);
    $ajout = floatval($_POST['montant_ajout']);
    $pdo->prepare("UPDATE objectifs SET montant_actuel = LEAST(montant_actuel + ?, montant_cible) WHERE id=? AND utilisateur_id=?")
        ->execute([$ajout, $id, $uid]);
    $check = $pdo->prepare("SELECT id FROM objectifs WHERE id=? AND montant_actuel >= montant_cible");
    $check->execute([$id]);
    if ($check->fetch()) $pdo->prepare("UPDATE objectifs SET statut='atteint' WHERE id=?")->execute([$id]);
}

if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM objectifs WHERE id=? AND utilisateur_id=?")->execute([$_GET['supprimer'], $uid]);
    header("Location: /master-money/objectifs.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM objectifs WHERE utilisateur_id=? ORDER BY created_at DESC");
$stmt->execute([$uid]); $objectifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div style="max-width:900px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Épargne</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>',22,'var(--accent-green)') ?>
            Objectifs d'Épargne
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">Fixez vos objectifs et suivez votre progression mois par mois.</p>
    </div>

    <?php if ($msg === 'success'): ?>
    <div style="background:rgba(0,229,160,0.08);border:1px solid rgba(0,229,160,0.25);color:var(--accent-green);padding:0.85rem 1.1rem;border-radius:10px;margin-bottom:1.5rem;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;">
        <?= icon('<polyline points="20 6 9 17 4 12"/>',16,'var(--accent-green)') ?>
        Objectif ajouté avec succès !
    </div>
    <?php endif; ?>

    <!-- Formulaire nouvel objectif -->
    <div class="card" style="margin-bottom:2rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem;">
            <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',16,'var(--accent-green)') ?>
            Nouvel Objectif
        </h3>
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div style="grid-column:span 2;">
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Titre de l'objectif</label>
                <input type="text" name="titre" placeholder="Ex: Acheter un ordinateur portable" required
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>
            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Montant cible (MAD)</label>
                <input type="number" name="montant_cible" placeholder="3000" min="1" required
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>
            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Durée (mois)</label>
                <input type="number" name="duree_mois" placeholder="6" min="1" max="60" required
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>
            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.35rem;">Date de début</label>
                <input type="date" name="date_debut" value="<?= date('Y-m-d') ?>" required
                    style="width:100%;padding:0.7rem 1rem;border-radius:8px;font-size:0.88rem;">
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit" name="ajouter" class="btn-primary" style="width:100%;justify-content:center;border-radius:9px;">
                    <?= icon('<polyline points="20 6 9 17 4 12"/>',15) ?> Créer l'objectif
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des objectifs -->
    <?php if (empty($objectifs)): ?>
    <div class="card" style="text-align:center;padding:3rem;">
        <?= icon('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>',44,'var(--text-muted)') ?>
        <p style="color:var(--text-secondary);margin-top:1rem;">Aucun objectif défini. Commencez par en créer un !</p>
    </div>
    <?php else: ?>
    <div style="display:grid;gap:1.2rem;">
        <?php foreach($objectifs as $obj):
            $pct       = $obj['montant_cible']>0?min(round(($obj['montant_actuel']/$obj['montant_cible'])*100),100):0;
            $epargne_m = $obj['duree_mois']>0?ceil(($obj['montant_cible']-$obj['montant_actuel'])/$obj['duree_mois']):0;
            $couleur   = $obj['statut']==='atteint'?'var(--accent-green)':($pct>50?'var(--accent-blue)':'var(--warning)');
            $date_fin  = date('d/m/Y',strtotime($obj['date_debut'].' +'.$obj['duree_mois'].' months'));
        ?>
        <div class="card" style="position:relative;">
            <?php if($obj['statut']==='atteint'): ?>
            <div style="position:absolute;top:1rem;right:1rem;background:rgba(0,229,160,0.1);color:var(--accent-green);padding:0.2rem 0.8rem;border-radius:50px;font-size:0.73rem;font-weight:600;display:flex;align-items:center;gap:0.3rem;">
                <?= icon('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',13,'var(--accent-green)') ?> Atteint !
            </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
                <div>
                    <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:0.2rem;"><?= htmlspecialchars($obj['titre']) ?></h3>
                    <p style="color:var(--text-muted);font-size:0.76rem;display:flex;align-items:center;gap:0.3rem;">
                        <?= icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',12,'var(--text-muted)') ?>
                        Échéance : <?= $date_fin ?>
                    </p>
                </div>
                <a href="?supprimer=<?= $obj['id'] ?>" onclick="return confirm('Supprimer cet objectif ?')"
                   style="color:var(--danger);font-size:0.76rem;text-decoration:none;opacity:0.5;display:flex;align-items:center;gap:0.3rem;transition:opacity 0.15s;"
                   onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">
                    <?= icon('<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',14,'var(--danger)') ?> Supprimer
                </a>
            </div>

            <!-- Barre progression -->
            <div style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;font-size:0.81rem;color:var(--text-secondary);margin-bottom:0.4rem;">
                    <span><?= number_format($obj['montant_actuel'],0) ?> MAD épargnés</span>
                    <span style="color:<?= $couleur ?>;font-weight:600;"><?= $pct ?>%</span>
                </div>
                <div style="background:var(--bg-secondary);border-radius:8px;height:10px;overflow:hidden;">
                    <div style="background:<?= $couleur ?>;width:<?= $pct ?>%;height:10px;border-radius:8px;transition:width 1s ease;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.73rem;color:var(--text-muted);margin-top:0.3rem;">
                    <span>Cible : <?= number_format($obj['montant_cible'],0) ?> MAD</span>
                    <span>Reste : <?= number_format($obj['montant_cible']-$obj['montant_actuel'],0) ?> MAD</span>
                </div>
            </div>

            <?php if($obj['statut']!=='atteint'): ?>
            <!-- Info conseil -->
            <div style="background:rgba(0,229,160,0.06);border:1px solid rgba(0,229,160,0.15);border-radius:8px;padding:0.65rem 1rem;margin-bottom:1rem;font-size:0.81rem;color:var(--text-secondary);display:flex;align-items:center;gap:0.5rem;">
                <?= icon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',15,'var(--accent-green)') ?>
                Épargnez environ <strong style="color:var(--accent-green);">&nbsp;<?= number_format($epargne_m,0) ?> MAD/mois&nbsp;</strong> pour atteindre votre objectif à temps.
            </div>

            <!-- Ajouter épargne -->
            <form method="POST" style="display:flex;gap:0.8rem;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="objectif_id" value="<?= $obj['id'] ?>">
                <input type="number" name="montant_ajout" placeholder="Montant à ajouter (MAD)" min="1" required
                    style="flex:1;min-width:180px;padding:0.62rem 0.9rem;border-radius:8px;font-size:0.87rem;">
                <button type="submit" name="epargner" class="btn-primary" style="border-radius:8px;padding:0.62rem 1.2rem;font-size:0.85rem;">
                    <?= icon('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',15) ?> Épargner
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>