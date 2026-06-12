<?php
/* ═══════════════════════════════════════════
   FST CLUBS — SUCCES.PHP
   Page de confirmation d'inscription réussie
═══════════════════════════════════════════ */

require_once __DIR__ . '/config.php';

$id  = (int)($_GET['id']  ?? 0);
$ref = sanitize($_GET['ref'] ?? '');

if (!$id || !$ref) {
    header('Location: index.html');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM inscriptions WHERE id = :id AND ref = :ref LIMIT 1");
$stmt->execute([':id' => $id, ':ref' => $ref]);
$ins  = $stmt->fetch();

if (!$ins) {
    header('Location: index.html');
    exit;
}

$clubKey  = $ins['club'];
$clubInfo = CLUBS[$clubKey] ?? [];
$clubLabel = $clubInfo['label'] ?? $clubKey;

/* Formations listées */
$formList = $ins['formations'] ? array_filter(explode(',', $ins['formations'])) : [];

$adhesionLabels = [
    'membre'    => 'Membre actif',
    'bureau'    => 'Candidature au bureau',
    'formation' => 'Formation uniquement',
];
$pmLabels = [
    'carte'    => 'Carte bancaire',
    'virement' => 'Virement bancaire',
    'especes'  => 'Paiement en espèces',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inscription confirmée — FST Clubs</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
</head>
<body class="succes-page">

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="index.html" class="logo">
      <div class="logo-icon"><span>FST</span></div>
      <div class="logo-text"><strong>FST Tunis</strong><small>Espace Clubs</small></div>
    </a>
    <a href="index.html" class="btn-nav">← Accueil</a>
  </div>
</nav>

<div class="succes-body">

  <!-- Check icône -->
  <div class="succes-check">✓</div>
  <h1 class="succes-title">Inscription confirmée !</h1>
  <p class="succes-sub">
    Bienvenue <?= htmlspecialchars($ins['prenom']) ?> ! Votre dossier a bien été enregistré.<br/>
    <strong style="color:var(--navy)">Réf : <?= htmlspecialchars($ref) ?></strong>
  </p>

  <!-- Récapitulatif dossier -->
  <div class="succes-card">
    <h3>Récapitulatif de votre dossier</h3>
    <div class="succes-info-row">
      <span>Nom complet</span>
      <span><?= htmlspecialchars($ins['prenom'] . ' ' . $ins['nom']) ?></span>
    </div>
    <div class="succes-info-row">
      <span>Matricule</span>
      <span><?= htmlspecialchars($ins['matricule']) ?></span>
    </div>
    <div class="succes-info-row">
      <span>Email</span>
      <span><?= htmlspecialchars($ins['email']) ?></span>
    </div>
    <div class="succes-info-row">
      <span>Club rejoint</span>
      <span><strong style="color:var(--navy)"><?= htmlspecialchars($clubLabel) ?></strong></span>
    </div>
    <div class="succes-info-row">
      <span>Type d'adhésion</span>
      <span><?= htmlspecialchars($adhesionLabels[$ins['adhesion']] ?? $ins['adhesion']) ?></span>
    </div>
    <?php if (!empty($formList)): ?>
    <div class="succes-info-row">
      <span>Formations inscrites</span>
      <span>
        <?php foreach ($formList as $f):
          $fl = trim($f);
          echo htmlspecialchars(FORMATIONS[$fl]['label'] ?? $fl) . '<br/>';
        endforeach; ?>
      </span>
    </div>
    <?php endif; ?>
    <div class="succes-info-row">
      <span>Mode de paiement</span>
      <span><?= htmlspecialchars($pmLabels[$ins['mode_paiement']] ?? $ins['mode_paiement']) ?></span>
    </div>
    <div class="succes-info-row">
      <span>Cotisation club</span>
      <span><?= number_format((float)$ins['cotisation'], 2) ?> DT</span>
    </div>
    <div class="succes-info-row">
      <span>Formations</span>
      <span><?= number_format((float)$ins['montant_formations'], 2) ?> DT</span>
    </div>
    <div class="succes-info-row">
      <span>Frais administratifs</span>
      <span><?= number_format((float)$ins['frais_admin'], 2) ?> DT</span>
    </div>
    <div class="succes-info-row" style="font-size:16px;font-weight:700">
      <span>Total réglé</span>
      <span style="color:var(--gold)"><?= number_format((float)$ins['montant_total'], 2) ?> DT</span>
    </div>
  </div>

  <!-- Rendez-vous de recrutement -->
  <?php if (isset($clubInfo['date_recrutement'])): ?>
  <div class="recrutement-box">
    <h3>📅 Votre rendez-vous de recrutement</h3>
    <div class="recruit-row">
      <span class="r-icon">📆</span>
      <div class="r-text">
        <strong><?= htmlspecialchars($clubInfo['date_recrutement']) ?></strong>
        <span>Date de la séance de recrutement</span>
      </div>
    </div>
    <div class="recruit-row">
      <span class="r-icon">⏰</span>
      <div class="r-text">
        <strong><?= htmlspecialchars($clubInfo['heure_recrutement']) ?></strong>
        <span>Horaire de passage</span>
      </div>
    </div>
    <div class="recruit-row">
      <span class="r-icon">📍</span>
      <div class="r-text">
        <strong><?= htmlspecialchars($clubInfo['salle_recrutement']) ?></strong>
        <span>Lieu du recrutement</span>
      </div>
    </div>
    <div class="recruit-row">
      <span class="r-icon">📧</span>
      <div class="r-text">
        <strong><?= htmlspecialchars($clubInfo['email']) ?></strong>
        <span>Contact du club pour toute question</span>
      </div>
    </div>
    <div style="margin-top:1rem;padding:1rem;background:rgba(212,160,23,.12);border-radius:10px;border:1px solid rgba(212,160,23,.3)">
      <p style="font-size:13px;color:rgba(255,255,255,.85);line-height:1.7">
        ⚠️ Présentez-vous muni(e) de votre <strong>CIN</strong>, de votre <strong>carte étudiante</strong>
        et de ce récapitulatif (imprimé ou sur téléphone) à la date et au lieu indiqués ci-dessus.
        <?php if ($ins['mode_paiement'] === 'especes'): ?>
        <br/>💵 N'oubliez pas le montant en espèces : <strong><?= number_format((float)$ins['montant_total'], 2) ?> DT</strong>.
        <?php elseif ($ins['mode_paiement'] === 'virement'): ?>
        <br/>🏦 Apportez le justificatif de virement bancaire.
        <?php endif; ?>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Prochaines étapes -->
  <div class="succes-card">
    <h3>Prochaines étapes</h3>
    <div style="display:flex;flex-direction:column;gap:.9rem">
      <div style="display:flex;gap:.85rem;align-items:flex-start">
        <div style="width:28px;height:28px;background:var(--navy);color:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">1</div>
        <div>
          <strong style="font-size:14px">Confirmation par email</strong>
          <p style="font-size:13px;color:var(--text-lt);margin-top:.2rem">Un email de confirmation sera envoyé à <strong><?= htmlspecialchars($ins['email']) ?></strong> dans les 24h.</p>
        </div>
      </div>
      <div style="display:flex;gap:.85rem;align-items:flex-start">
        <div style="width:28px;height:28px;background:var(--navy);color:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">2</div>
        <div>
          <strong style="font-size:14px">Séance de recrutement</strong>
          <p style="font-size:13px;color:var(--text-lt);margin-top:.2rem">Présentez-vous au rendez-vous indiqué ci-dessus avec vos documents.</p>
        </div>
      </div>
      <div style="display:flex;gap:.85rem;align-items:flex-start">
        <div style="width:28px;height:28px;background:var(--navy);color:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">3</div>
        <div>
          <strong style="font-size:14px">Validation du dossier</strong>
          <p style="font-size:13px;color:var(--text-lt);margin-top:.2rem">L'équipe du club examinera votre candidature sous 3 jours ouvrables.</p>
        </div>
      </div>
      <div style="display:flex;gap:.85rem;align-items:flex-start">
        <div style="width:28px;height:28px;background:#16a34a;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">✓</div>
        <div>
          <strong style="font-size:14px">Bienvenue dans le club !</strong>
          <p style="font-size:13px;color:var(--text-lt);margin-top:.2rem">Vous recevrez un email de bienvenue avec les accès et le planning des activités.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Boutons -->
  <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;margin-top:1.5rem">
    <a href="index.html" class="btn-primary">← Retour à l'accueil</a>
    <a href="calendrier.html" class="btn-outline" style="border-color:var(--border);color:var(--text)">📅 Voir le calendrier</a>
  </div>
  <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:1.5rem">
    Conservez votre référence : <strong><?= htmlspecialchars($ref) ?></strong>
  </p>

</div>

<script src="script.js"></script>
<script>
  // Sauvegarder la référence dans localStorage pour accès au calendrier
  localStorage.setItem('fst_ins_ref', '<?= htmlspecialchars($ref, ENT_JS) ?>');
  localStorage.removeItem('fst_club');
</script>
</body>
</html>
