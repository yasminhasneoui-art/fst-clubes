<?php
/* ═══════════════════════════════════════════
   FST CLUBS — PAIEMENT.PHP
   Interface de paiement post-inscription
═══════════════════════════════════════════ */

require_once __DIR__ . '/config.php';

$id  = (int)($_GET['id']  ?? $_POST['id']  ?? 0);
$ref = sanitize($_GET['ref'] ?? $_POST['ref'] ?? '');

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

/* ─── Traitement du paiement (POST) ─── */
$errPay = '';
$modePm = sanitize($_POST['mode_paiement'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modePm) {
    /* Mettre à jour le mode de paiement et confirmer */
    $upd = $pdo->prepare("UPDATE inscriptions
        SET mode_paiement = :mp, paiement_confirme = 1, date_paiement = NOW(), statut = 'en_attente'
        WHERE id = :id AND ref = :ref");
    $upd->execute([':mp' => $modePm, ':id' => $id, ':ref' => $ref]);

    /* Rediriger vers la page de succès */
    header('Location: succes.php?id=' . $id . '&ref=' . urlencode($ref));
    exit;
}

/* ─── Données du club ─── */
$clubKey  = $ins['club'];
$clubInfo = CLUBS[$clubKey] ?? [];
$clubLabel = $clubInfo['label'] ?? $clubKey;
$modePmSaved = $ins['mode_paiement'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Paiement — FST Clubs</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
</head>
<body class="pay-page">

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="index.html" class="logo">
      <div class="logo-icon"><span>FST</span></div>
      <div class="logo-text"><strong>FST Tunis</strong><small>Espace Clubs</small></div>
    </a>
  </div>
</nav>

<!-- ═══ HERO ═══ -->
<div class="pay-hero">
  <div class="section-tag">Finaliser l'inscription</div>
  <h1>Paiement de l'adhésion</h1>
  <p>Choisissez votre mode de règlement et confirmez votre inscription</p>
</div>

<!-- ═══ BODY ═══ -->
<div class="pay-body">

  <?php if ($errPay): ?>
  <div class="alert alert-err">⚠️ <?= htmlspecialchars($errPay) ?></div>
  <?php endif; ?>

  <!-- Référence -->
  <div class="pay-card" style="text-align:center">
    <div class="pay-ref">
      Référence d'inscription<br/>
      <strong><?= htmlspecialchars($ref) ?></strong>
    </div>
    <div style="font-size:14px;color:var(--text-lt)">
      <?= htmlspecialchars($ins['prenom'] . ' ' . $ins['nom']) ?> &nbsp;·&nbsp;
      <strong><?= htmlspecialchars($clubLabel) ?></strong>
    </div>
  </div>

  <!-- Total -->
  <div class="pay-card">
    <h3>Détail du montant</h3>
    <div class="summary-box">
      <div class="summary-row">
        <span>Cotisation club</span>
        <span><?= number_format((float)$ins['cotisation'], 2) ?> DT</span>
      </div>
      <div class="summary-row">
        <span>Formations inscrites</span>
        <span><?= number_format((float)$ins['montant_formations'], 2) ?> DT</span>
      </div>
      <div class="summary-row">
        <span>Frais administratifs</span>
        <span><?= number_format((float)$ins['frais_admin'], 2) ?> DT</span>
      </div>
      <div class="summary-row total">
        <span>Total à régler</span>
        <span><?= number_format((float)$ins['montant_total'], 2) ?> DT</span>
      </div>
    </div>
    <div class="pay-total">
      <small>Montant total</small>
      <strong><?= number_format((float)$ins['montant_total'], 2) ?> DT</strong>
    </div>
  </div>

  <!-- Formulaire de paiement -->
  <div class="pay-card">
    <h3>Mode de paiement</h3>
    <form method="POST">
      <input type="hidden" name="id" value="<?= $id ?>"/>
      <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>"/>

      <div class="pm-choice">

        <label class="pm-option">
          <input type="radio" name="mode_paiement" value="carte"
            <?= $modePmSaved === 'carte' ? 'checked' : '' ?>
            onchange="onPmChange(this)" required/>
          <span class="pm-o-icon">💳</span>
          <div>
            <strong>Carte bancaire</strong>
            <span>Paiement en ligne sécurisé — Visa / Mastercard</span>
          </div>
        </label>

        <label class="pm-option">
          <input type="radio" name="mode_paiement" value="virement"
            <?= $modePmSaved === 'virement' ? 'checked' : '' ?>
            onchange="onPmChange(this)"/>
          <span class="pm-o-icon">🏦</span>
          <div>
            <strong>Virement bancaire</strong>
            <span>Virement vers le compte FST · Délai : 48h ouvrables</span>
          </div>
        </label>

        <label class="pm-option">
          <input type="radio" name="mode_paiement" value="especes"
            <?= $modePmSaved === 'especes' ? 'checked' : '' ?>
            onchange="onPmChange(this)"/>
          <span class="pm-o-icon">💵</span>
          <div>
            <strong>Paiement en espèces</strong>
            <span>À la scolarité — bureau 104 · Lun–Ven 08h–15h</span>
          </div>
        </label>

      </div>

      <!-- Infos dynamiques selon le mode -->
      <div id="pmInfo" class="pm-details" style="display:none;margin-top:1rem"></div>

      <div style="margin-top:1.5rem">
        <button type="submit" class="btn-pay">✓ Confirmer et finaliser mon inscription</button>
      </div>
    </form>
  </div>

  <!-- Clause -->
  <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:1rem">
    En confirmant, vous acceptez le règlement intérieur des clubs FST Tunis.
    Votre dossier sera traité dans un délai de 3 jours ouvrables.
  </p>

</div>

<script src="script.js"></script>
<script>
  // Afficher les détails du mode pré-sélectionné au chargement
  document.addEventListener('DOMContentLoaded', function () {
    var checked = document.querySelector('input[name="mode_paiement"]:checked');
    if (checked) onPmChange(checked);
  });
</script>
</body>
</html>
