<?php
// ── Session safe ──
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Infos utilisateur pour le header ──
$user_nom    = $_SESSION['user_nom']    ?? '';
$user_id     = $_SESSION['user_id']     ?? null;
$user_points = $_SESSION['user_points'] ?? 0;
$user_avatar = $_SESSION['user_avatar'] ?? '';
$user_initiale = $user_nom ? mb_strtoupper(mb_substr($user_nom, 0, 1)) : 'U';

// ── Lien actif ──
$current = basename($_SERVER['PHP_SELF']);
function isActive($file) {
    global $current;
    return $current === $file ? 'style="color:var(--accent-green)!important;background:rgba(0,229,160,0.07)!important;"' : '';
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Money</title>
    <link rel="icon" type="image/png" href="/master-money/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/master-money/css/style.css">

    <!-- ══ ANTI-FLASH : applique le thème avant tout rendu ══ -->
    <script>
    (function() {
        var t = localStorage.getItem('mm_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>

    <style>
    /* ══════════════════════════════════════════
       VARIABLES CSS — DARK (défaut)
    ══════════════════════════════════════════ */
    :root,
    [data-theme="dark"] {
        --bg:            #09090f;
        --bg-nav:        rgba(9,9,15,0.88);
        --bg-card:       #111118;
        --bg-secondary:  #17171f;
        --bg-primary:    #09090f;
        --border:        rgba(255,255,255,0.07);
        --border-accent: rgba(0,229,160,0.25);
        --accent-green:  #00e5a0;
        --accent-blue:   #4f8ef7;
        --danger:        #ff4f6d;
        --warning:       #ffb020;
        --text-primary:  #f0f0ff;
        --text-secondary:#9999bb;
        --text-muted:    #55556a;
        --shadow:        rgba(0,0,0,0.5);
        --input-bg:      #17171f;
        --input-border:  rgba(255,255,255,0.1);
        --scrollbar-bg:  #17171f;
        --scrollbar-thumb: rgba(255,255,255,0.12);
    }

    /* ══════════════════════════════════════════
       VARIABLES CSS — LIGHT
    ══════════════════════════════════════════ */
    [data-theme="light"] {
        --bg:            #f4f5fb;
        --bg-nav:        rgba(255,255,255,0.92);
        --bg-card:       #ffffff;
        --bg-secondary:  #eef0f8;
        --bg-primary:    #f4f5fb;
        --border:        rgba(0,0,0,0.09);
        --border-accent: rgba(0,150,100,0.25);
        --accent-green:  #00a375;
        --accent-blue:   #2563eb;
        --danger:        #dc2626;
        --warning:       #d97706;
        --text-primary:  #0d0d1a;
        --text-secondary:#444466;
        --text-muted:    #9999aa;
        --shadow:        rgba(0,0,0,0.1);
        --input-bg:      #f0f1f9;
        --input-border:  rgba(0,0,0,0.12);
        --scrollbar-bg:  #e8e9f5;
        --scrollbar-thumb: rgba(0,0,0,0.15);
    }

    /* ══════════════════════════════════════════
       BASE
    ══════════════════════════════════════════ */
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    html { scroll-behavior: smooth; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg);
        color: var(--text-primary);
        min-height: 100vh;
        padding-top: 62px; /* hauteur navbar */
        transition: background 0.3s ease, color 0.3s ease;
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--scrollbar-bg); }
    ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 3px; }

    /* Inputs globaux */
    input, select, textarea {
        background: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
        font-family: 'DM Sans', sans-serif;
        outline: none;
        transition: border-color 0.2s, background 0.3s;
    }
    input:focus, select:focus, textarea:focus {
        border-color: var(--accent-green) !important;
        box-shadow: 0 0 0 2px rgba(0,229,160,0.12) !important;
    }
    select option {
        background: var(--bg-card);
        color: var(--text-primary);
    }

    /* Cards */
    .card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.5rem;
        transition: transform 0.2s, box-shadow 0.2s, background 0.3s, border-color 0.3s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px var(--shadow);
    }

    /* Boutons */
    .btn-primary {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: var(--accent-green); color: #09090f;
        padding: 0.7rem 1.4rem; border-radius: 50px;
        font-weight: 700; font-size: 0.88rem;
        text-decoration: none; border: none; cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        transition: all 0.2s;
    }
    .btn-primary:hover {
        filter: brightness(1.1); transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0,229,160,0.3);
    }
    .btn-secondary {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: transparent; color: var(--text-primary);
        padding: 0.7rem 1.4rem; border-radius: 50px;
        font-weight: 600; font-size: 0.88rem;
        text-decoration: none; border: 1px solid var(--border); cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        transition: all 0.2s;
    }
    .btn-secondary:hover { border-color: var(--accent-green); color: var(--accent-green); }

    /* ══════════════════════════════════════════
       NAVBAR
    ══════════════════════════════════════════ */
    .mm-nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        height: 62px;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 1.8rem;
        background: var(--bg-nav);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border);
        transition: background 0.3s, border-color 0.3s;
        gap: 1rem;
    }

    /* Logo */
    .mm-logo {
        display: flex; align-items: center; gap: 0.55rem;
        text-decoration: none; color: var(--text-primary);
        font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.05rem;
        flex-shrink: 0;
    }
    .mm-logo img { width: 28px; height: 28px; border-radius: 7px; }
    .mm-logo span { color: var(--accent-green); }

    /* Liens centre */
    .mm-nav-links {
        display: flex; align-items: center; gap: 0.1rem;
        list-style: none; flex: 1; justify-content: center;
    }
    .mm-nav-links a {
        display: flex; align-items: center; gap: 0.32rem;
        padding: 0.38rem 0.72rem; border-radius: 8px;
        text-decoration: none; color: var(--text-secondary);
        font-size: 0.82rem; font-weight: 500;
        transition: all 0.18s; white-space: nowrap;
    }
    .mm-nav-links a:hover, .mm-nav-links a.active {
        color: var(--accent-green);
        background: rgba(0,229,160,0.07);
    }

    /* Actions droite */
    .mm-nav-actions {
        display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0;
    }

    /* Points badge */
    .mm-points-badge {
        display: flex; align-items: center; gap: 0.3rem;
        background: rgba(255,176,32,0.1); border: 1px solid rgba(255,176,32,0.2);
        color: var(--warning); padding: 0.28rem 0.7rem;
        border-radius: 50px; font-size: 0.75rem; font-weight: 600;
    }

    /* Theme toggle */
    .mm-theme-btn {
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        border: 1px solid var(--border);
        background: var(--bg-secondary);
        cursor: pointer; transition: all 0.2s;
        color: var(--text-secondary);
        flex-shrink: 0;
    }
    .mm-theme-btn:hover {
        border-color: var(--accent-green);
        color: var(--accent-green);
        background: rgba(0,229,160,0.07);
    }

    /* Avatar */
    .mm-avatar-btn {
        width: 36px; height: 36px; border-radius: 50%;
        border: 2px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.85rem;
        background: linear-gradient(135deg, var(--accent-green), var(--accent-blue));
        color: #09090f; text-decoration: none; cursor: pointer;
        overflow: hidden; transition: border-color 0.2s, transform 0.2s;
        flex-shrink: 0;
    }
    .mm-avatar-btn:hover { border-color: var(--accent-green); transform: scale(1.05); }
    .mm-avatar-btn img { width: 100%; height: 100%; object-fit: cover; }

    /* Bouton + Budget */
    .mm-new-budget {
        display: flex; align-items: center; gap: 0.4rem;
        background: var(--accent-green); color: #09090f;
        padding: 0.42rem 0.9rem; border-radius: 8px;
        font-weight: 700; font-size: 0.8rem;
        text-decoration: none; border: none; cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        transition: all 0.18s; white-space: nowrap;
    }
    .mm-new-budget:hover { filter: brightness(1.08); transform: translateY(-1px); }

    /* Hamburger mobile */
    .mm-hamburger {
        display: none;
        flex-direction: column; gap: 5px; cursor: pointer;
        width: 38px; height: 38px; align-items: center; justify-content: center;
        border-radius: 9px; border: 1px solid var(--border);
        background: var(--bg-secondary); transition: all 0.2s;
    }
    .mm-hamburger span {
        width: 18px; height: 2px;
        background: var(--text-secondary); border-radius: 2px;
        transition: all 0.3s;
    }
    .mm-hamburger:hover { border-color: var(--accent-green); }

    /* Menu mobile */
    .mm-mobile-menu {
        display: none;
        position: fixed; top: 62px; left: 0; right: 0;
        background: var(--bg-nav);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border);
        padding: 1rem 1.5rem 1.5rem;
        flex-direction: column; gap: 0.3rem;
        z-index: 999;
        transition: background 0.3s;
    }
    .mm-mobile-menu.open { display: flex; }
    .mm-mobile-menu a {
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.6rem 0.8rem; border-radius: 9px;
        text-decoration: none; color: var(--text-secondary);
        font-size: 0.9rem; font-weight: 500;
        transition: all 0.18s;
    }
    .mm-mobile-menu a:hover { color: var(--accent-green); background: rgba(0,229,160,0.07); }
    .mm-mobile-menu .mm-divider {
        height: 1px; background: var(--border); margin: 0.4rem 0;
    }

    /* ══ Toast thème ══ */
    .mm-toast {
        position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: 50px; padding: 0.5rem 1rem;
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.78rem; color: var(--text-secondary);
        box-shadow: 0 4px 20px var(--shadow);
        opacity: 0; transform: translateY(12px);
        transition: opacity 0.3s, transform 0.3s;
        pointer-events: none;
    }
    .mm-toast.show { opacity: 1; transform: translateY(0); }

    /* ══ Responsive ══ */
    @media (max-width: 900px) {
        .mm-nav-links { display: none; }
        .mm-hamburger { display: flex; }
    }
    @media (max-width: 600px) {
        .mm-nav { padding: 0 1rem; }
        .mm-points-badge { display: none; }
    }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="mm-nav">

    <!-- Logo -->
    <a href="/master-money/index.html" class="mm-logo">
        <img src="/master-money/images/favicon.png" alt="Logo">
        Master <span>Money</span>
    </a>

    <!-- Liens desktop -->
    <ul class="mm-nav-links">
        <?php if ($user_id): ?>
        <li><a href="/master-money/tableau-de-bord.php" <?= isActive('tableau-de-bord.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a></li>
        <li><a href="/master-money/calculateur.php" <?= isActive('calculateur.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
            Budget
        </a></li>
        <li><a href="/master-money/depenses.php" <?= isActive('depenses.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            Dépenses
        </a></li>
        <li><a href="/master-money/objectifs.php" <?= isActive('objectifs.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
            Objectifs
        </a></li>
        <li><a href="/master-money/simulation.php" <?= isActive('simulation.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
            Simulation
        </a></li>
        <li><a href="/master-money/comparaison.php" <?= isActive('comparaison.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            Comparaison
        </a></li>
        
        <li><a href="/master-money/guide.php" <?= isActive('guide.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Guide
        </a></li>
       
        <?php else: ?>
        <li><a href="/master-money/guide.php" <?= isActive('guide.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Guide
        </a></li>
        <li><a href="/master-money/bons-plans.php" <?= isActive('bons-plans.php') ?>>
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Bons Plans
        </a></li>
        <?php endif; ?>
    </ul>

    <!-- Actions droite -->
    <div class="mm-nav-actions">

        <?php if ($user_id && $user_points): ?>
        <div class="mm-points-badge">
            <svg viewBox="0 0 24 24" style="width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?= $user_points ?> pts
        </div>
        <?php endif; ?>

        <!-- Toggle thème -->
        <button class="mm-theme-btn" id="mmThemeBtn" onclick="mmToggleTheme()" title="Changer le thème (Alt+T)">
            <svg id="mmIconSun" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;display:none;">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <svg id="mmIconMoon" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        </button>

        <?php if ($user_id): ?>
        <!-- Bouton nouveau budget -->
        <a href="/master-money/calculateur.php" class="mm-new-budget">
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Budget
        </a>
        <!-- Avatar -->
        <a href="/master-money/profil.php" class="mm-avatar-btn" title="Mon profil — <?= htmlspecialchars($user_nom) ?>">
            <?php if ($user_avatar && file_exists(__DIR__.'/../images/avatars/'.basename($user_avatar))): ?>
            <img src="/master-money/images/avatars/<?= htmlspecialchars(basename($user_avatar)) ?>" alt="Avatar">
            <?php else: ?>
            <?= $user_initiale ?>
            <?php endif; ?>
        </a>
        <?php else: ?>
        <a href="/master-money/connexion.php" class="btn-secondary" style="padding:0.42rem 1rem;font-size:0.82rem;border-radius:8px;">Connexion</a>
        <a href="/master-money/inscription.php" class="btn-primary" style="padding:0.42rem 1rem;font-size:0.82rem;border-radius:8px;">S'inscrire</a>
        <?php endif; ?>

        <!-- Hamburger -->
        <button class="mm-hamburger" id="mmHamburger" onclick="mmToggleMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- ══ MENU MOBILE ══ -->
<div class="mm-mobile-menu" id="mmMobileMenu">
    <?php if ($user_id): ?>
    <a href="/master-money/tableau-de-bord.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
    </a>
    <a href="/master-money/calculateur.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
        Calculateur de budget
    </a>
    <a href="/master-money/depenses.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/></svg>
        Dépenses quotidiennes
    </a>
    <a href="/master-money/objectifs.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
        Objectifs d'épargne
    </a>
    <a href="/master-money/simulation.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/></svg>
        Simulation budgétaire
    </a>
    <a href="/master-money/comparaison.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Comparaison étudiants
    </a>
    <div class="mm-divider"></div>
    <a href="/master-money/bons-plans.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Bons Plans
    </a>
    <a href="/master-money/guide.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Guide financier
    </a>
    <!-- ══ CHATBOT MOBILE ══ -->
    <a href="/master-money/chatbot.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Conseiller IA
    </a>
    <a href="/master-money/profil.php">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon profil
    </a>
    <div class="mm-divider"></div>
    <a href="/master-money/deconnexion.php" style="color:var(--danger)!important;">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
    </a>
    <?php else: ?>
    <a href="/master-money/guide.php">Guide financier</a>
    <div class="mm-divider"></div>
    <a href="/master-money/connexion.php">Connexion</a>
    <a href="/master-money/inscription.php">S'inscrire</a>
    <?php endif; ?>
</div>

<!-- Toast thème -->
<div class="mm-toast" id="mmToast">
    <svg id="mmToastIcon" viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
    <span id="mmToastText">Mode sombre activé</span>
</div>

<script>
// ══════════════════════════════════════════════
//  THEME SYSTEM — Master Money
// ══════════════════════════════════════════════
(function() {
    var sun   = document.getElementById('mmIconSun');
    var moon  = document.getElementById('mmIconMoon');
    var toast = document.getElementById('mmToast');
    var toastTxt = document.getElementById('mmToastText');
    var toastIco = document.getElementById('mmToastIcon');
    var _timer;

    function mmApplyTheme(theme, showToast) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('mm_theme', theme);

        if (theme === 'dark') {
            moon.style.display = 'block';
            sun.style.display  = 'none';
        } else {
            moon.style.display = 'none';
            sun.style.display  = 'block';
        }

        if (showToast) {
            toastTxt.textContent = theme === 'dark' ? 'Mode sombre activé' : 'Mode clair activé';
            toastIco.innerHTML = theme === 'dark'
                ? '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'
                : '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
            toast.classList.add('show');
            clearTimeout(_timer);
            _timer = setTimeout(function() { toast.classList.remove('show'); }, 2000);
        }
    }

    var saved = localStorage.getItem('mm_theme') || 'dark';
    mmApplyTheme(saved, false);

    window.mmToggleTheme = function() {
        var cur = document.documentElement.getAttribute('data-theme');
        mmApplyTheme(cur === 'dark' ? 'light' : 'dark', true);
    };

    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 't') window.mmToggleTheme();
    });
})();

// ══ Menu hamburger ══
function mmToggleMenu() {
    var menu = document.getElementById('mmMobileMenu');
    menu.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    var menu = document.getElementById('mmMobileMenu');
    var btn  = document.getElementById('mmHamburger');
    if (menu.classList.contains('open') && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove('open');
    }
});
</script>