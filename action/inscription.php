<?php
/* FST Clubs — inscription.php
   Traitement du formulaire d'inscription
   + réservation de créneau unique par étudiant */

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inscription.html');
    exit;
}

/* ── 1. Collecte & nettoyage ── */
$club          = sanitize($_POST['club']          ?? '');
$adhesion      = sanitize($_POST['adhesion']      ?? '');
$creneau_id    = (int)($_POST['creneau_id']       ?? 0);
$prenom        = sanitize($_POST['prenom']        ?? '');
$nom           = sanitize($_POST['nom']           ?? '');
$matricule     = sanitize($_POST['matricule']     ?? '');
$cin           = sanitize($_POST['cin']           ?? '');
$email         = sanitize($_POST['email']         ?? '');
$telephone     = sanitize($_POST['telephone']     ?? '');
$filiere       = sanitize($_POST['filiere']       ?? '');
$niveau        = sanitize($_POST['niveau']        ?? '');
$dispo         = sanitize($_POST['dispo']         ?? '');
$experience    = sanitize($_POST['experience']    ?? '');
$motivation    = sanitize($_POST['motivation']    ?? '');
$mode_paiement = sanitize($_POST['mode_paiement'] ?? '');
$newsletter    = isset($_POST['newsletter']) ? 1 : 0;

$validComp = ['prog','design','commun','gestion','reseau','photo','redac','video'];
$competences = [];
if (!empty($_POST['competences']) && is_array($_POST['competences'])) {
    foreach ($_POST['competences'] as $c) {
        $c = sanitize($c);
        if (in_array($c, $validComp, true)) $competences[] = $c;
    }
}

$validForm = array_keys(FORMATION_PRICES);
$formations = [];
if (!empty($_POST['formations']) && is_array($_POST['formations'])) {
    foreach ($_POST['formations'] as $f) {
        $f = sanitize($f);
        if (in_array($f, $validForm, true)) $formations[] = $f;
    }
}

/* ── 2. Validation ── */
$errors = [];

if (!array_key_exists($club, CLUB_PRICES))        $errors[] = "Club invalide.";
if (!array_key_exists($adhesion, ADHESION_LABELS)) $errors[] = "Type d'adhesion invalide.";
if (!array_key_exists($mode_paiement, PM_LABELS))  $errors[] = "Mode de paiement obligatoire.";
if (mb_strlen($prenom) < 2)                        $errors[] = "Prenom trop court.";
if (mb_strlen($nom) < 2)                           $errors[] = "Nom trop court.";
if (!isValidMatricule($matricule))                 $errors[] = "Matricule invalide (8 chiffres).";
if (!isValidCin($cin))                             $errors[] = "CIN invalide (8 chiffres).";
if (!isValidEmail($email))                         $errors[] = "Email invalide.";
if (!empty($telephone) && !isValidPhone($telephone)) $errors[] = "Telephone invalide.";
if (empty($filiere))                               $errors[] = "Filiere manquante.";
if (empty($niveau))                                $errors[] = "Niveau manquant.";
if (empty($dispo))                                 $errors[] = "Disponibilites manquantes.";
if (mb_strlen($motivation) < 100)                  $errors[] = "Motivation trop courte (min 100 car.).";

if (!empty($errors)) { echo renderErrors($errors); exit; }

/* ── 3. Calcul montant ── */
$cotisation        = CLUB_PRICES[$club];
$fraisAdmin        = 2;
$montantFormations = 0;
foreach ($formations as $f) $montantFormations += FORMATION_PRICES[$f] ?? 0;
$montantTotal = $cotisation + $fraisAdmin + $montantFormations;

/* ── 4. Insertion en base ── */
$ref = generateRef();
$pdo = getDB();

/* Doublon : même matricule + même club */
$check = $pdo->prepare("SELECT id, ref FROM inscriptions WHERE matricule=:m AND club=:c LIMIT 1");
$check->execute([':m'=>$matricule, ':c'=>$club]);
$existing = $check->fetch();
if ($existing) {
    echo renderErrors(["Vous etes deja inscrit au club <strong>" . (CLUB_LABELS[$club] ?? $club) . "</strong> avec ce matricule (dossier <strong>{$existing['ref']}</strong>)."]);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO inscriptions
          (ref, club, adhesion, prenom, nom, matricule, cin, email, telephone,
           filiere, niveau, disponibilite, experience, motivation, competences, formations,
           mode_paiement, cotisation, montant_formations, frais_admin, montant_total,
           newsletter, statut, created_at)
        VALUES
          (:ref,:club,:adhesion,:prenom,:nom,:matricule,:cin,:email,:telephone,
           :filiere,:niveau,:dispo,:experience,:motivation,:competences,:formations,
           :mode,:cotis,:mf,:fa,:total,
           :newsletter,'en_attente',NOW())
    ");
    $stmt->execute([
        ':ref'=>$ref, ':club'=>$club, ':adhesion'=>$adhesion,
        ':prenom'=>$prenom, ':nom'=>$nom, ':matricule'=>$matricule, ':cin'=>$cin,
        ':email'=>$email, ':telephone'=>$telephone,
        ':filiere'=>$filiere, ':niveau'=>$niveau, ':dispo'=>$dispo,
        ':experience'=>$experience, ':motivation'=>$motivation,
        ':competences'=>implode(',',$competences),
        ':formations'=>implode(',',$formations),
        ':mode'=>$mode_paiement,
        ':cotis'=>$cotisation, ':mf'=>$montantFormations, ':fa'=>$fraisAdmin, ':total'=>$montantTotal,
        ':newsletter'=>$newsletter,
    ]);
    $inscriptionId = (int) $pdo->lastInsertId();

    /* Réserver le créneau si demandé */
    $creneauInfo = null;
    if ($creneau_id > 0) {
        /* Vérifier que le créneau appartient bien au club ET est libre */
        $chk = $pdo->prepare("SELECT id, date_cr, heure_debut, heure_fin, lieu, club
            FROM creneaux_recrutement WHERE id=:id AND club=:club AND inscription_id IS NULL FOR UPDATE");
        $chk->execute([':id'=>$creneau_id, ':club'=>$club]);
        $creneauInfo = $chk->fetch();

        if ($creneauInfo) {
            $pdo->prepare("UPDATE creneaux_recrutement SET inscription_id=:iid WHERE id=:id AND inscription_id IS NULL")
                ->execute([':iid'=>$inscriptionId, ':id'=>$creneau_id]);
            /* Mettre à jour l'inscription avec le creneau_id */
            $pdo->prepare("UPDATE inscriptions SET creneau_id=:cid WHERE id=:id")
                ->execute([':cid'=>$creneau_id, ':id'=>$inscriptionId]);
        } else {
            /* Créneau déjà pris entre temps */
            $pdo->rollBack();
            echo renderErrors(["Ce creneau vient d'etre reserve par un autre etudiant. Veuillez revenir en arriere et choisir un autre creneau."]);
            exit;
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[FST-CLUBS] Insert error: ' . $e->getMessage());
    echo renderErrors(["Erreur base de donnees. Veuillez reessayer."]);
    exit;
}

/* ── 5. Page de succès ── */
$clubLabel     = CLUB_LABELS[$club]          ?? $club;
$adhesionLabel = ADHESION_LABELS[$adhesion]  ?? $adhesion;
$pmLabel       = PM_LABELS[$mode_paiement]   ?? $mode_paiement;

/* Informations créneau pour la page succès */
$creneauHtml = '';
if ($creneauInfo) {
    $dateFormatee = date('d/m/Y', strtotime($creneauInfo['date_cr']));
    $creneauHtml = '
    <div style="background:#EAF3DE;border:1px solid rgba(59,109,17,0.2);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem">
      <p style="font-size:13px;font-weight:600;color:#27500A;margin-bottom:8px">&#128197; Votre entretien de recrutement</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div><small style="font-size:11px;color:#3B6D11;text-transform:uppercase;letter-spacing:0.04em">Date</small>
          <strong style="display:block;font-size:14px;color:#1a1a2e">' . $dateFormatee . '</strong></div>
        <div><small style="font-size:11px;color:#3B6D11;text-transform:uppercase;letter-spacing:0.04em">Heure</small>
          <strong style="display:block;font-size:14px;color:#1a1a2e">' . htmlspecialchars($creneauInfo['heure_debut']) . ' &ndash; ' . htmlspecialchars($creneauInfo['heure_fin']) . '</strong></div>
        <div style="grid-column:1/-1"><small style="font-size:11px;color:#3B6D11;text-transform:uppercase;letter-spacing:0.04em">Lieu</small>
          <strong style="display:block;font-size:14px;color:#1a1a2e">&#128205; ' . htmlspecialchars($creneauInfo['lieu']) . '</strong></div>
      </div>
    </div>';
}

/* Paiement info */
$paiementInfo = '';
if ($mode_paiement === 'especes') {
    $paiementInfo = '<div style="background:#fff9ec;border:1px solid rgba(212,160,23,0.3);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem">
      <p style="font-size:13px;font-weight:600;color:#92400e;margin-bottom:8px">&#128181; Paiement en especes</p>
      <p style="font-size:13px;color:#633806">Bureau A-104 (Lun-Ven, 08h-16h) — montant exact : <strong>' . $montantTotal . ' DT</strong></p></div>';
} elseif ($mode_paiement === 'virement') {
    $paiementInfo = '<div style="background:#fff9ec;border:1px solid rgba(212,160,23,0.3);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem">
      <p style="font-size:13px;font-weight:600;color:#92400e;margin-bottom:8px">&#127963; Virement bancaire</p>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-bottom:1px solid #f0e0c0"><span>Banque</span><strong>BNA</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-bottom:1px solid #f0e0c0"><span>RIB</span><strong>10 006 0351234567890 23</strong></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0"><span>Reference</span><strong style="color:#d4a017">FST-CLUBS-2024 / ' . $ref . '</strong></div></div>';
} elseif ($mode_paiement === 'carte') {
    $paiementInfo = '<div style="background:#EAF3DE;border:1px solid rgba(59,109,17,0.2);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem">
      <p style="font-size:13px;font-weight:600;color:#27500A">&#10003; Paiement par carte de <strong>' . $montantTotal . ' DT</strong> confirme.</p></div>';
}

/* Formations display */
$formationsDisplay = '';
if (!empty($formations)) {
    $formationsDisplay = '<div style="background:#f5f5f0;border-radius:8px;padding:1rem;margin-bottom:1.5rem">
      <p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Formations inscrites</p>' .
      implode('', array_map(function($f) {
          return '<div style="font-size:13px;padding:4px 0;color:#3B6D11">&#10003; ' . (FORMATION_LABELS[$f] ?? $f) . '</div>';
      }, $formations)) . '</div>';
}

?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inscription confirmee — FST Clubs</title>
  <link rel="stylesheet" href="../assets/cs/style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    .success-wrapper{max-width:580px;margin:0 auto;padding:2rem 1rem 4rem}
    .success-card{background:#fff;border-radius:16px;border:1px solid #e8e8e0;overflow:hidden}
    .success-header{background:linear-gradient(135deg,#0a1628,#162847);padding:2.5rem 2rem;text-align:center}
    .success-icon{width:72px;height:72px;border-radius:50%;background:rgba(59,109,17,0.2);border:2px solid #3B6D11;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 1rem}
    .success-header h2{color:#fff;font-size:1.6rem;margin-bottom:0.5rem}
    .success-header p{color:rgba(255,255,255,0.65);font-size:14px}
    .success-body{padding:1.75rem 2rem}
    .ref-badge{display:inline-block;background:#FAEEDA;color:#633806;font-size:22px;font-weight:700;font-family:'Playfair Display',serif;padding:10px 24px;border-radius:10px;letter-spacing:0.04em;margin-bottom:1rem}
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1.5rem}
    .detail-cell{background:#f5f5f0;border-radius:8px;padding:10px 14px}
    .detail-cell small{display:block;font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px}
    .detail-cell strong{font-size:13px;color:#1a1a2e}
    .status-badge{display:inline-block;background:#f5f5f0;color:#5a5a50;padding:5px 14px;border-radius:20px;font-size:13px}
    .success-actions{display:flex;gap:1rem;flex-wrap:wrap;margin-top:1.5rem}
    @media(max-width:480px){.detail-grid{grid-template-columns:1fr}.success-actions{flex-direction:column}}
  </style>
</head>
<body class="inscription-page">

<nav class="navbar">
  <div class="nav-inner">
    <a href="index.html" class="logo">
      <div class="logo-icon"><span>FST</span></div>
      <div class="logo-text"><strong>FST Tunis</strong><small>Espace Clubs</small></div>
    </a>
  </div>
</nav>

<main>
  <div class="success-wrapper">
    <div class="success-card">

      <div class="success-header">
        <div class="success-icon">&#10003;</div>
        <h2>Inscription confirmee !</h2>
        <p>Votre dossier a ete enregistre avec succes, <?= htmlspecialchars($prenom) ?> !</p>
      </div>

      <div class="success-body">
        <div style="text-align:center;margin-bottom:1.5rem">
          <small style="display:block;font-size:12px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Numero de dossier</small>
          <span class="ref-badge"><?= $ref ?></span><br/>
          <span class="status-badge">&#9203; En attente de validation</span>
        </div>

        <div class="detail-grid">
          <div class="detail-cell"><small>Club</small><strong><?= htmlspecialchars($clubLabel) ?></strong></div>
          <div class="detail-cell"><small>Adhesion</small><strong><?= htmlspecialchars($adhesionLabel) ?></strong></div>
          <div class="detail-cell"><small>Cotisation</small><strong><?= $cotisation ?> DT</strong></div>
          <div class="detail-cell"><small>Formations</small><strong><?= $montantFormations > 0 ? $montantFormations . ' DT' : '—' ?></strong></div>
          <div class="detail-cell" style="grid-column:1/-1">
            <small>Total a payer</small><strong style="font-size:18px;color:#d4a017"><?= $montantTotal ?> DT</strong>
          </div>
        </div>

        <?= $creneauHtml ?>

        <?= $formationsDisplay ?>

        <?= $paiementInfo ?>

        <p style="font-size:13px;color:#4a4a5a;background:#f5f5f0;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.5rem">
          &#128231; Un email de confirmation a ete envoye a <strong><?= htmlspecialchars($email) ?></strong>.
          Le bureau du club vous contactera sous <strong>5 jours ouvrables</strong>.
        </p>

        <div class="success-actions">
          <a href="../index.html" class="btn-primary">&#8592; Retour a l'accueil</a>
          <a href="../calendrier.html" style="border:1px solid #e8e8e0;color:#4a4a5a;padding:11px 22px;border-radius:10px;font-size:14px;display:inline-block;text-decoration:none">&#128197; Calendrier &amp; Workshops</a>
        </div>
      </div>

    </div>
  </div>
</main>

</body>
</html>
<?php

/* ── Fonctions locales ── */
function renderErrors(array $errors): string {
    $list = '';
    foreach ($errors as $e) {
        $list .= '<li style="font-size:13px;color:#4a4a5a;display:flex;gap:8px;margin-top:6px"><span style="color:#e24b4a">&#10005;</span>' . $e . '</li>';
    }
    return '<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Erreur — FST Clubs</title>
<link rel="stylesheet" href="../assets/css/style.css"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
</head><body class="inscription-page">
<nav class="navbar"><div class="nav-inner">
<a href="../index.html" class="logo"><div class="logo-icon"><span>FST</span></div>
<div class="logo-text"><strong>FST Tunis</strong><small>Espace Clubs</small></div></a>
</div></nav>
<div style="max-width:540px;margin:4rem auto;padding:0 1rem">
<div style="background:#fff;border-radius:16px;border:1px solid #e8e8e0;overflow:hidden">
<div style="background:#fcebeb;padding:1.5rem 2rem;border-bottom:1px solid #f0c3c3">
<h2 style="color:#791F1F;font-size:1.2rem;margin-bottom:4px">&#9888;&#65039; Erreurs de validation</h2>
<p style="font-size:13px;color:#A32D2D">Corrigez les points suivants :</p>
</div>
<div style="padding:1.5rem 2rem">
<ul style="list-style:none;margin-bottom:1.5rem">' . $list . '</ul>
<a href="javascript:history.back()" style="display:inline-block;padding:10px 22px;background:#0a1628;color:#d4a017;border-radius:10px;font-size:14px;text-decoration:none">&#8592; Corriger le formulaire</a>
</div></div></div>
</body></html>';
}
