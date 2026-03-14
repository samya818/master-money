<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /master-money/connexion.php");
    exit;
}

include 'includes/header.php';

function icon($path,$size=16,$color='currentColor'){
    return "<svg viewBox=\"0 0 24 24\" style=\"width:{$size}px;height:{$size}px;stroke:{$color};fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;\">{$path}</svg>";
}
?>

<div style="max-width:900px;margin:2rem auto;padding:0 1.5rem 4rem;">

    <!-- Header -->
    <div style="margin-bottom:2rem;">
        <p style="font-size:0.7rem;font-weight:600;color:var(--accent-green);letter-spacing:2px;text-transform:uppercase;margin-bottom:0.4rem;">Ressources</p>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>', 22, 'var(--accent-green)') ?>
            Guide Financier Étudiant
        </h1>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.4rem;">Conseils pratiques pour maîtriser votre budget à l'université.</p>
    </div>

    <!-- Nav sections -->
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:2rem;">
        <?php
        $sections = [
            ['#economiser',   'Économiser',      '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
            ['#gerer',        'Gérer',           '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
            ['#dettes',       'Éviter les dettes','<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
            ['#astuces',      'Astuces',          '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
            ['#applications', 'Applications',    '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>'],
        ];
        foreach($sections as [$href,$label,$ipath]): ?>
        <a href="<?= $href ?>" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.4rem 0.9rem;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;color:var(--text-secondary);text-decoration:none;font-size:0.8rem;font-weight:500;transition:all 0.18s;"
           onmouseover="this.style.borderColor='rgba(0,229,160,0.3)';this.style.color='var(--accent-green)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-secondary)'">
            <?= icon($ipath, 14, 'currentColor') ?> <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Section 1 : Économiser -->
    <div id="economiser" class="card" style="margin-bottom:1.5rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>', 18, 'var(--accent-green)') ?>
            Économiser au quotidien
        </h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <?php
            $tips = [
                ['Cuisiner maison',       'Préparez vos repas plutôt que manger en dehors. Économie estimée : 200–400 MAD/mois.',     '<path d="M3 2h18"/><path d="M3 7h18"/><path d="M8 2v5"/><path d="M16 2v5"/><rect x="3" y="7" width="18" height="14" rx="1"/>'],
                ['Transport collectif',   'Utilisez les bus et taxis collectifs. Évitez les taxis individuels pour les trajets quotidiens.', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ['Achats groupés',        'Achetez en gros avec des camarades pour les produits alimentaires de base.', '<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5" rx="1"/>'],
                ['Éviter les impulsions', 'Attendez 24h avant tout achat non planifié. Cette règle permet d\'éliminer 80% des achats impulsifs.', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>'],
            ];
            foreach($tips as [$titre,$desc,$ipath]): ?>
            <div style="padding:1rem;background:var(--bg-secondary);border-radius:10px;border:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                    <?= icon($ipath, 15, 'var(--accent-green)') ?>
                    <strong style="font-size:0.85rem;color:var(--text-primary);"><?= $titre ?></strong>
                </div>
                <p style="font-size:0.8rem;color:var(--text-secondary);line-height:1.6;margin:0;"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2 : Règle 50/30/20 -->
    <div id="gerer" class="card" style="margin-bottom:1.5rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>', 18, 'var(--accent-blue)') ?>
            La règle 50/30/20
        </h2>
        <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1.5rem;line-height:1.65;">
            Divisez vos revenus en trois catégories pour un budget équilibré.
        </p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
            <?php
            $regle = [
                ['50%', 'Besoins essentiels', 'Loyer, alimentation, transport, factures.', 'var(--accent-green)'],
                ['30%', 'Envies & loisirs',   'Sorties, shopping, abonnements, voyages.', 'var(--accent-blue)'],
                ['20%', 'Épargne',            'Fonds d\'urgence, projets, investissements.', 'var(--warning)'],
            ];
            foreach($regle as [$pct,$titre,$desc,$col]): ?>
            <div style="text-align:center;padding:1.2rem;background:var(--bg-secondary);border-radius:12px;border:1px solid var(--border);">
                <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= $col ?>;margin-bottom:0.4rem;"><?= $pct ?></div>
                <div style="font-size:0.82rem;font-weight:600;margin-bottom:0.3rem;"><?= $titre ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);line-height:1.5;"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Calculateur 50/30/20 -->
        <div style="background:rgba(0,229,160,0.05);border:1px solid rgba(0,229,160,0.15);border-radius:12px;padding:1.2rem;">
            <h4 style="font-size:0.85rem;font-weight:600;margin-bottom:0.9rem;display:flex;align-items:center;gap:0.4rem;">
                <?= icon('<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>', 14, 'var(--accent-green)') ?>
                Calculateur 50/30/20
            </h4>
            <div style="display:flex;align-items:center;gap:0.8rem;flex-wrap:wrap;">
                <label style="font-size:0.82rem;color:var(--text-secondary);">Mon revenu mensuel (MAD) :</label>
                <input type="number" id="revenu-calc" placeholder="ex: 1500" oninput="calcRegle()"
                    style="padding:0.5rem 0.8rem;border-radius:7px;font-size:0.88rem;width:130px;">
            </div>
            <div id="regle-result" style="display:none;margin-top:1rem;display:grid;grid-template-columns:repeat(3,1fr);gap:0.7rem;">
                <div style="text-align:center;padding:0.7rem;background:rgba(0,229,160,0.08);border-radius:8px;">
                    <div style="font-size:0.7rem;color:var(--text-muted);">Besoins</div>
                    <div id="r50" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--accent-green);">—</div>
                </div>
                <div style="text-align:center;padding:0.7rem;background:rgba(79,142,247,0.08);border-radius:8px;">
                    <div style="font-size:0.7rem;color:var(--text-muted);">Loisirs</div>
                    <div id="r30" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--accent-blue);">—</div>
                </div>
                <div style="text-align:center;padding:0.7rem;background:rgba(255,176,32,0.08);border-radius:8px;">
                    <div style="font-size:0.7rem;color:var(--text-muted);">Épargne</div>
                    <div id="r20" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--warning);">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3 : Éviter les dettes -->
    <div id="dettes" class="card" style="margin-bottom:1.5rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>', 18, 'var(--danger)') ?>
            Éviter les dettes
        </h2>
        <?php
        $dettes = [
            ['Fonds d\'urgence',     'Constituez une réserve de 3 mois de dépenses. Elle vous évitera de recourir aux emprunts en cas de coup dur.',            'var(--accent-green)', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
            ['Éviter les crédits',   'Les crédits à la consommation (téléphone, vêtements) ont des taux d\'intérêt très élevés. Épargnez d\'abord, achetez ensuite.', 'var(--warning)', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
            ['Rembourser en priorité','Si vous avez des dettes, remboursez d\'abord les plus chères en intérêts. Méthode avalanche = économies maximales.',          'var(--danger)',       '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>'],
        ];
        foreach($dettes as [$titre,$desc,$col,$ipath]): ?>
        <div style="display:flex;gap:1rem;align-items:flex-start;padding:1rem;background:var(--bg-secondary);border-radius:10px;border-left:3px solid <?= $col ?>;margin-bottom:0.8rem;">
            <?= icon($ipath, 18, $col) ?>
            <div>
                <strong style="font-size:0.85rem;display:block;margin-bottom:0.3rem;"><?= $titre ?></strong>
                <p style="font-size:0.8rem;color:var(--text-secondary);line-height:1.6;margin:0;"><?= $desc ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Section 4 : Astuces -->
    <div id="astuces" class="card" style="margin-bottom:1.5rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>', 18, 'var(--warning)') ?>
            Astuces pour étudiants
        </h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;">
            <?php
            $astuces = [
                'Utilisez votre carte étudiante pour les réductions (-20% à -50% dans de nombreux commerces)',
                'Achetez vos livres d\'occasion ou partagez-les entre camarades',
                'Cuisinez en batch le weekend pour toute la semaine',
                'Profitez des activités gratuites du campus (sport, culture)',
                'Comparez les prix avant chaque achat important',
                'Désabonnez-vous des services non utilisés chaque mois',
            ];
            foreach($astuces as $astuce): ?>
            <div style="display:flex;gap:0.6rem;align-items:flex-start;padding:0.75rem;background:var(--bg-secondary);border-radius:8px;">
                <?= icon('<polyline points="20 6 9 17 4 12"/>', 14, 'var(--accent-green)') ?>
                <span style="font-size:0.8rem;color:var(--text-secondary);line-height:1.55;"><?= $astuce ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 5 : Applications -->
    <div id="applications" class="card">
        <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.6rem;">
            <?= icon('<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>', 18, 'var(--accent-blue)') ?>
            Outils recommandés
        </h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
            <?php
            $apps = [
                ['Master Money',     'Notre plateforme — tout-en-un pour gérer votre budget étudiant UMI.',                   'var(--accent-green)', '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
                ['Tableur Excel',    'Créez votre propre tableau budgétaire personnalisé avec formules automatiques.',          'var(--accent-blue)',  '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/>'],
                ['Notes téléphone',  'Notez chaque dépense dès qu\'elle a lieu. La simplicité garantit la régularité.',        'var(--warning)',      '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>'],
                ['Enveloppes cash',  'Retirez du cash en début de mois et répartissez par catégorie dans des enveloppes.',     'var(--danger)',       '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8l-4 4h16z"/>'],
            ];
            foreach($apps as [$nom,$desc,$col,$ipath]): ?>
            <div style="padding:1.2rem;background:var(--bg-secondary);border-radius:10px;border:1px solid var(--border);border-top:2px solid <?= $col ?>;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.6rem;">
                    <?= icon($ipath, 16, $col) ?>
                    <strong style="font-size:0.85rem;"><?= $nom ?></strong>
                </div>
                <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.6;margin:0;"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
function calcRegle() {
    const rev = parseFloat(document.getElementById('revenu-calc').value);
    const res = document.getElementById('regle-result');
    if (!rev || rev <= 0) { res.style.display='none'; return; }
    res.style.display = 'grid';
    document.getElementById('r50').textContent = Math.round(rev*0.5) + ' MAD';
    document.getElementById('r30').textContent = Math.round(rev*0.3) + ' MAD';
    document.getElementById('r20').textContent = Math.round(rev*0.2) + ' MAD';
}
</script>

<?php include 'includes/footer.php'; ?>