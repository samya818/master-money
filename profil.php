<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /master-money/connexion.php");
    exit;
}

$uid = $_SESSION['user_id'];
$msg = "";

// Ajouter colonne avatar si manquante
try { $pdo->exec("ALTER TABLE `utilisateurs` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) {}

$upload_dir = __DIR__ . '/images/avatars/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Upload photo
if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
    $file    = $_FILES['photo'];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK)          { $msg = "upload_error"; }
    elseif (!in_array($file['type'], $allowed))     { $msg = "type_error"; }
    elseif ($file['size'] > 5*1024*1024)            { $msg = "size_error"; }
    else {
        // Supprimer ancienne photo
        $old = $pdo->prepare("SELECT avatar FROM utilisateurs WHERE id=?"); $old->execute([$uid]);
        $old_av = $old->fetchColumn();
        if ($old_av && file_exists($upload_dir.$old_av)) unlink($upload_dir.$old_av);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar_'.$uid.'_'.time().'.'.$ext;
        if (move_uploaded_file($file['tmp_name'], $upload_dir.$filename)) {
            // Redimensionner avec GD
            $src = match($file['type']) {
                'image/jpeg','image/jpg' => imagecreatefromjpeg($upload_dir.$filename),
                'image/png'              => imagecreatefrompng($upload_dir.$filename),
                'image/webp'             => imagecreatefromwebp($upload_dir.$filename),
                default => null
            };
            if ($src) {
                $ow = imagesx($src); $oh = imagesy($src);
                $min = min($ow,$oh);
                $dst = imagecreatetruecolor(300,300);
                imagecopyresampled($dst,$src,0,0,($ow-$min)/2,($oh-$min)/2,300,300,$min,$min);
                $newfile = 'avatar_'.$uid.'_'.time().'.jpg';
                imagejpeg($dst,$upload_dir.$newfile,90);
                imagedestroy($src); imagedestroy($dst);
                unlink($upload_dir.$filename);
                $filename = $newfile;
            }
            $pdo->prepare("UPDATE utilisateurs SET avatar=? WHERE id=?")->execute([$filename,$uid]);
            $_SESSION['user_avatar'] = $filename;
            $msg = "photo_ok";
        } else { $msg = "upload_error"; }
    }
}

// Supprimer photo
if (isset($_POST['supprimer_photo'])) {
    $old = $pdo->prepare("SELECT avatar FROM utilisateurs WHERE id=?"); $old->execute([$uid]);
    $old_av = $old->fetchColumn();
    if ($old_av && file_exists($upload_dir.$old_av)) unlink($upload_dir.$old_av);
    $pdo->prepare("UPDATE utilisateurs SET avatar=NULL WHERE id=?")->execute([$uid]);
    $_SESSION['user_avatar'] = null;
    $msg = "photo_deleted";
}

// Modifier nom
if (isset($_POST['update'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    if (!empty($nom)) {
        $pdo->prepare("UPDATE utilisateurs SET nom=? WHERE id=?")->execute([$nom,$uid]);
        $_SESSION['user_nom'] = $nom;
        $msg = "success";
    }
}

// Changer mot de passe
if (isset($_POST['change_pwd'])) {
    $stmtP = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id=?"); $stmtP->execute([$uid]);
    $hash  = $stmtP->fetchColumn();
    if (password_verify($_POST['ancien_mdp'], $hash)) {
        if (strlen($_POST['nouveau_mdp']) >= 8) {
            $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?")
                ->execute([password_hash($_POST['nouveau_mdp'], PASSWORD_DEFAULT), $uid]);
            $msg = "pwd_ok";
        } else { $msg = "pwd_court"; }
    } else { $msg = "pwd_err"; }
}

// Données utilisateur
$stmtU = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=?"); $stmtU->execute([$uid]);
$user  = $stmtU->fetch(PDO::FETCH_ASSOC);

// Stats
$nb  = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id=?"); $nb->execute([$uid]);  $nbBudgets = $nb->fetchColumn();
$moy = $pdo->prepare("SELECT AVG(reste_a_vivre) FROM budgets WHERE utilisateur_id=?"); $moy->execute([$uid]); $moyReste = round($moy->fetchColumn() ?? 0);
$pos = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE utilisateur_id=? AND reste_a_vivre>0"); $pos->execute([$uid]); $nbPos = $pos->fetchColumn();
try {
    $nbObj = $pdo->prepare("SELECT COUNT(*) FROM objectifs WHERE utilisateur_id=?"); $nbObj->execute([$uid]); $nbObjectifs = $nbObj->fetchColumn();
    $nbAtt = $pdo->prepare("SELECT COUNT(*) FROM objectifs WHERE utilisateur_id=? AND statut='atteint'"); $nbAtt->execute([$uid]); $nbAtteints = $nbAtt->fetchColumn();
} catch(Exception $e) { $nbObjectifs=0; $nbAtteints=0; }

$badges = [];
try {
    $stmtB = $pdo->prepare("SELECT b.* FROM utilisateur_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.utilisateur_id=?");
    $stmtB->execute([$uid]); $badges = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$points     = $user['points'] ?? 0;
$memberDays = (int)((time() - strtotime($user['created_at'])) / 86400);
$avatar_url = (!empty($user['avatar']) && file_exists($upload_dir.$user['avatar']))
              ? '/master-money/images/avatars/'.$user['avatar'] : null;

include 'includes/header.php';
?>

<div style="max-width:860px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <!-- En-tête page -->
    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Compte</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <svg viewBox="0 0 24 24" style="width:22px;height:22px;stroke:var(--accent-green);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Mon Profil
        </h1>
    </div>

    <!-- Messages -->
    <?php
    $msgs = [
        'success'       => ['green','Profil mis à jour avec succès.'],
        'photo_ok'      => ['green','Photo de profil mise à jour.'],
        'photo_deleted' => ['green','Photo supprimée.'],
        'pwd_ok'        => ['green','Mot de passe modifié avec succès.'],
        'pwd_err'       => ['red',  'Ancien mot de passe incorrect.'],
        'pwd_court'     => ['red',  'Le mot de passe doit contenir au moins 8 caractères.'],
        'type_error'    => ['red',  'Format non supporté. Utilisez JPG, PNG ou WEBP.'],
        'size_error'    => ['red',  'Fichier trop lourd. Maximum 5 MB.'],
        'upload_error'  => ['red',  'Erreur lors de l\'upload. Réessayez.'],
    ];
    if ($msg && isset($msgs[$msg])): [$col,$txt] = $msgs[$msg];
    $isGreen = $col === 'green';
    ?>
    <div style="background:rgba(<?= $isGreen?'0,229,160':'255,79,109' ?>,0.08);border:1px solid rgba(<?= $isGreen?'0,229,160':'255,79,109' ?>,0.25);color:var(--<?= $isGreen?'accent-green':'danger' ?>);padding:0.85rem 1.1rem;border-radius:10px;margin-bottom:1.5rem;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;">
        <?php if($isGreen): ?>
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
        <?php else: ?>
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
        <?= htmlspecialchars($txt) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:260px 1fr;gap:1.5rem;align-items:start;">

        <!-- Colonne gauche -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

            <!-- Card avatar -->
            <div class="card" style="text-align:center;padding:2rem 1.5rem;">
                <div style="position:relative;display:inline-block;margin-bottom:1.2rem;">
                    <?php if ($avatar_url): ?>
                    <img src="<?= $avatar_url ?>?v=<?= time() ?>" alt="Avatar"
                         style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2.5px solid var(--accent-green);box-shadow:0 0 20px rgba(0,229,160,0.25);">
                    <?php else: ?>
                    <div id="av-placeholder" style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--accent-green),var(--accent-blue));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;color:#09090f;margin:0 auto;border:2.5px solid var(--accent-green);box-shadow:0 0 20px rgba(0,229,160,0.25);">
                        <?= strtoupper(mb_substr($user['nom'],0,1)) ?>
                    </div>
                    <?php endif; ?>
                    <!-- Bouton caméra -->
                    <label for="photo-input" title="Changer la photo"
                           style="position:absolute;bottom:2px;right:2px;width:28px;height:28px;border-radius:50%;background:var(--accent-green);color:#09090f;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid var(--bg);box-shadow:0 2px 8px rgba(0,0,0,0.4);transition:transform 0.2s;"
                           onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </label>
                </div>

                <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;margin-bottom:0.2rem;"><?= htmlspecialchars($user['nom']) ?></h3>
                <p style="color:var(--text-muted);font-size:0.75rem;margin-bottom:0.9rem;"><?= htmlspecialchars($user['email']) ?></p>

                <div style="display:inline-flex;align-items:center;gap:0.35rem;background:rgba(255,176,32,0.08);border:1px solid rgba(255,176,32,0.2);color:var(--warning);padding:0.3rem 0.8rem;border-radius:50px;font-size:0.74rem;font-weight:600;">
                    <svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?= $points ?> points
                </div>
                <p style="color:var(--text-muted);font-size:0.7rem;margin-top:0.7rem;display:flex;align-items:center;gap:0.3rem;justify-content:center;">
                    <svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Membre depuis <?= $memberDays ?> jour<?= $memberDays>1?'s':'' ?>
                </p>

                <!-- Form upload caché -->
                <form method="POST" enctype="multipart/form-data" id="form-photo" style="display:none;">
                    <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
                    <input type="hidden" name="upload_photo" value="1">
                </form>

                <?php if ($avatar_url): ?>
                <form method="POST" style="margin-top:0.8rem;">
                    <input type="hidden" name="supprimer_photo" value="1">
                    <button type="submit" onclick="return confirm('Supprimer la photo ?')"
                        style="background:none;border:none;color:var(--danger);font-size:0.74rem;cursor:pointer;font-family:'DM Sans',sans-serif;opacity:0.6;display:inline-flex;align-items:center;gap:0.3rem;transition:opacity 0.2s;"
                        onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                        <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        Supprimer la photo
                    </button>
                </form>
                <?php endif; ?>

                <!-- Preview -->
                <div id="preview-box" style="display:none;margin-top:1rem;">
                    <img id="preview-img" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2.5px solid var(--accent-green);">
                    <p style="font-size:0.72rem;color:var(--text-muted);margin:0.4rem 0;">Aperçu</p>
                    <button onclick="document.getElementById('form-photo').submit()" class="btn-primary"
                            style="border-radius:50px;font-size:0.8rem;padding:0.45rem 1.1rem;justify-content:center;width:100%;margin-bottom:0.3rem;">
                        <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Confirmer
                    </button>
                    <button onclick="cancelPreview()" style="background:none;border:none;color:var(--text-muted);font-size:0.74rem;cursor:pointer;font-family:'DM Sans',sans-serif;width:100%;">Annuler</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="card">
                <h4 style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:1rem;display:flex;align-items:center;gap:0.4rem;">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Statistiques
                </h4>
                <?php
                $stats = [
                    ['Budgets créés',   $nbBudgets,  'var(--accent-green)', '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
                    ['Mois positifs',   $nbPos,      'var(--accent-green)', '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
                    ['Reste moyen',     number_format($moyReste,0).' MAD', $moyReste>=0?'var(--accent-green)':'var(--danger)', '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
                    ['Objectifs',       $nbAtteints.'/'.$nbObjectifs, 'var(--accent-blue)', '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/>'],
                    ['Badges obtenus',  count($badges), 'var(--warning)', '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
                ];
                foreach($stats as [$label,$val,$color,$path]): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.45rem 0;border-bottom:1px solid var(--border);">
                    <span style="font-size:0.82rem;color:var(--text-secondary);display:flex;align-items:center;gap:0.4rem;">
                        <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:<?= $color ?>;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;"><?= $path ?></svg>
                        <?= $label ?>
                    </span>
                    <strong style="font-size:0.85rem;color:<?= $color ?>;"><?= $val ?></strong>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Badges -->
            <?php if (!empty($badges)): ?>
            <div class="card">
                <h4 style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:0.9rem;display:flex;align-items:center;gap:0.4rem;">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:var(--warning);fill:none;stroke-width:2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Mes Badges
                </h4>
                <div style="display:flex;flex-wrap:wrap;gap:0.4rem;">
                    <?php foreach($badges as $b): ?>
                    <span title="<?= htmlspecialchars($b['description']) ?>"
                          style="background:rgba(0,229,160,0.07);border:1px solid rgba(0,229,160,0.18);border-radius:7px;padding:0.3rem 0.65rem;font-size:0.73rem;color:var(--accent-green);">
                        <?= htmlspecialchars($b['icone'].' '.$b['nom']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne droite -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

            <!-- Modifier nom -->
            <div class="card">
                <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.4rem;display:flex;align-items:center;gap:0.5rem;">
                    <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--accent-green);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Modifier mes informations
                </h3>
                <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                    <div>
                        <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.45rem;font-weight:500;">Nom complet</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required
                            style="width:100%;padding:0.75rem 1rem;border-radius:8px;font-size:0.9rem;">
                    </div>
                    <div>
                        <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.45rem;font-weight:500;">Email universitaire</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                            style="width:100%;padding:0.75rem 1rem;border-radius:8px;font-size:0.9rem;opacity:0.45;cursor:not-allowed;">
                    </div>
                    <div>
                        <button type="submit" name="update" class="btn-primary" style="border-radius:9px;">
                            <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Changer mot de passe -->
            <div class="card">
                <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:1.4rem;display:flex;align-items:center;gap:0.5rem;">
                    <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--accent-blue);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Changer le mot de passe
                </h3>
                <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                    <?php
                    $pwdFields = [
                        ['ancien_mdp',  'Ancien mot de passe', 'eye-a'],
                        ['nouveau_mdp', 'Nouveau mot de passe (min. 8 caractères)', 'eye-b'],
                    ];
                    foreach($pwdFields as [$name,$label,$eyeId]): ?>
                    <div>
                        <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.45rem;font-weight:500;"><?= $label ?></label>
                        <div style="position:relative;">
                            <input type="password" name="<?= $name ?>" id="<?= $eyeId ?>" required
                                style="width:100%;padding:0.75rem 2.8rem 0.75rem 1rem;border-radius:8px;font-size:0.88rem;">
                            <button type="button" onclick="togglePwd('<?= $eyeId ?>','<?= $eyeId ?>-icon')"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);display:flex;align-items:center;">
                                <svg id="<?= $eyeId ?>-icon" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div>
                        <button type="submit" name="change_pwd" class="btn-secondary" style="border-radius:9px;">
                            <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>

            <!-- Zone danger -->
            <div class="card" style="border-color:rgba(255,79,109,0.15);">
                <h3 style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:0.5rem;color:var(--danger);display:flex;align-items:center;gap:0.5rem;">
                    <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Zone de danger
                </h3>
                <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1rem;">Ces actions sont irréversibles.</p>
                <a href="/master-money/deconnexion.php"
                   style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1.1rem;border:1px solid rgba(255,79,109,0.25);border-radius:8px;color:var(--danger);font-size:0.84rem;text-decoration:none;transition:all 0.2s;font-weight:500;"
                   onmouseover="this.style.background='rgba(255,79,109,0.08)'" onmouseout="this.style.background='transparent'">
                    <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Se déconnecter
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('preview-box').style.display = 'block';
            const pl = document.getElementById('av-placeholder');
            if (pl) pl.style.opacity = '0.3';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function cancelPreview() {
    document.getElementById('preview-box').style.display = 'none';
    document.getElementById('photo-input').value = '';
    const pl = document.getElementById('av-placeholder');
    if (pl) pl.style.opacity = '1';
}
function togglePwd(inputId, iconId) {
    const i = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (i.type === 'password') {
        i.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        i.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>