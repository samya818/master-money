<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /master-money/tableau-de-bord.php");
    exit;
}

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp   = $_POST['mot_de_passe'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nom']    = $user['nom'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;
        $_SESSION['user_points'] = $user['points'] ?? 0;
        header("Location: /master-money/tableau-de-bord.php");
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}

include 'includes/header.php';
?>

<div style="min-height:70vh; display:flex; align-items:center; justify-content:center; padding:2rem;">
<div style="width:100%; max-width:400px;">

    <div style="text-align:center; margin-bottom:2rem;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(0,229,160,0.1);border:1px solid rgba(0,229,160,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#00e5a0;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:0.4rem;color:var(--text-primary);">Connexion</h2>
        <p style="color:var(--text-muted);font-size:0.86rem;">Accédez à votre espace budgétaire</p>
    </div>

    <?php if ($erreur): ?>
    <div style="background:rgba(255,79,109,0.08);border:1px solid rgba(255,79,109,0.25);color:var(--danger);padding:0.85rem 1.1rem;border-radius:10px;margin-bottom:1.5rem;font-size:0.85rem;text-align:center;display:flex;align-items:center;gap:0.5rem;justify-content:center;">
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($erreur) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;">

            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.45rem;font-weight:500;">Email universitaire</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);">
                        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:var(--text-muted);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <input type="email" name="email" placeholder="votre@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                        style="width:100%;padding:0.75rem 1rem 0.75rem 2.4rem;border-radius:8px;font-size:0.88rem;">
                </div>
            </div>

            <div>
                <label style="font-size:0.78rem;color:var(--text-secondary);display:block;margin-bottom:0.45rem;font-weight:500;">Mot de passe</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);">
                        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:var(--text-muted);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" name="mot_de_passe" id="mdp-input" placeholder="••••••••" required
                        style="width:100%;padding:0.75rem 2.8rem 0.75rem 2.4rem;border-radius:8px;font-size:0.88rem;">
                    <button type="button" id="toggle-mdp" onclick="toggleMdp()"
                        style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);display:flex;align-items:center;">
                        <svg id="eye-icon" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:0.82rem;font-size:0.92rem;border-radius:9px;margin-top:0.3rem;">
                Se connecter
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </form>
    </div>

    <p style="text-align:center;margin-top:1.5rem;font-size:0.84rem;color:var(--text-muted);">
        Pas encore de compte ?
        <a href="/master-money/inscription.php" style="color:var(--accent-green);text-decoration:none;font-weight:600;">Créer un compte</a>
    </p>
</div>
</div>

<script>
function toggleMdp() {
    const i = document.getElementById('mdp-input');
    const icon = document.getElementById('eye-icon');
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