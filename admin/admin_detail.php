<?php
/* FST Clubs — admin_detail.php
   Retourne le HTML du detail d'un dossier (appel AJAX depuis admin.php) */

require_once __DIR__ . '/config.php';

/* Vérif cookie admin */
$cookieName = 'fst_admin_ok';
$loggedIn   = (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === hash('sha256', ADMIN_PASSWORD));

if (!$loggedIn) {
    echo '<p style="color:red;padding:1rem">Session expiree. <a href="admin.php">Reconnectez-vous</a></p>';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p style="padding:1rem;color:#9a9a90">ID manquant.</p>'; exit; }

$pdo = getDB();

$stmt = $pdo->prepare("SELECT i.*, c.date_cr, c.heure_debut, c.heure_fin, c.lieu AS cr_lieu
    FROM inscriptions i
    LEFT JOIN creneaux_recrutement c ON c.id = i.creneau_id
    WHERE i.id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$ins = $stmt->fetch();

if (!$ins) { echo '<p style="padding:1rem;color:#9a9a90">Dossier introuvable.</p>'; exit; }

$clubLabel     = CLUB_LABELS[$ins['club']]      ?? $ins['club'];
$adhesionLabel = ADHESION_LABELS[$ins['adhesion']] ?? $ins['adhesion'];
$pmLabel       = PM_LABELS[$ins['mode_paiement']]  ?? $ins['mode_paiement'];
$competences   = $ins['competences'] ? explode(',', $ins['competences']) : [];
$formations    = $ins['formations']  ? array_map('trim', explode(',', $ins['formations'])) : [];

$compLabels = ['prog'=>'Programmation','design'=>'Design UI/UX','commun'=>'Communication',
    'gestion'=>'Gestion de projet','reseau'=>'Reseaux','photo'=>'Photographie',
    'redac'=>'Redaction','video'=>'Montage video'];

$statutBadge = match($ins['statut']) {
    'valide'        => '<span style="background:#EAF3DE;color:#27500A;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500">&#10003; Valide</span>',
    'refuse'        => '<span style="background:#FCEBEB;color:#791F1F;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500">&#10005; Refuse</span>',
    'liste_attente' => '<span style="background:#FAEEDA;color:#633806;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500">&#9208; Liste attente</span>',
    default         => '<span style="background:#f5f5f0;color:#5a5a50;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500">&#9203; En attente</span>',
};
?>

<h3 style="font-size:17px;margin-bottom:12px">
  Dossier <?= htmlspecialchars($ins['ref']) ?>
  <span style="margin-left:8px"><?= $statutBadge ?></span>
</h3>

<!-- Infos -->
<div class="detail-grid">
  <div class="detail-cell"><label>Prenom &amp; Nom</label><span><?= htmlspecialchars($ins['prenom'].' '.$ins['nom']) ?></span></div>
  <div class="detail-cell"><label>Matricule / CIN</label><span><?= htmlspecialchars($ins['matricule']) ?> / <?= htmlspecialchars($ins['cin']??'—') ?></span></div>
  <div class="detail-cell"><label>Email</label><span><a href="mailto:<?= htmlspecialchars($ins['email']) ?>" style="color:#185FA5"><?= htmlspecialchars($ins['email']) ?></a></span></div>
  <div class="detail-cell"><label>Telephone</label><span><?= htmlspecialchars($ins['telephone']??'—') ?></span></div>
  <div class="detail-cell"><label>Filiere / Niveau</label><span><?= htmlspecialchars($ins['filiere']??'—') ?> — <?= htmlspecialchars($ins['niveau']??'—') ?></span></div>
  <div class="detail-cell"><label>Disponibilites</label><span><?= htmlspecialchars($ins['disponibilite']??'—') ?></span></div>
  <div class="detail-cell"><label>Club</label><span><strong><?= htmlspecialchars($clubLabel) ?></strong></span></div>
  <div class="detail-cell"><label>Adhesion</label><span><?= htmlspecialchars($adhesionLabel) ?></span></div>
  <div class="detail-cell"><label>Date inscription</label><span><?= date('d/m/Y a H:i', strtotime($ins['created_at'])) ?></span></div>
  <?php if ($ins['date_cr']): ?>
  <div class="detail-cell"><label>Creneau entretien</label>
    <span style="color:#27500A;font-weight:500">
      <?= date('d/m/Y', strtotime($ins['date_cr'])) ?> &nbsp; <?= htmlspecialchars($ins['heure_debut']) ?> – <?= htmlspecialchars($ins['heure_fin']) ?>
      <small style="display:block;color:#9a9a90">&#128205; <?= htmlspecialchars($ins['cr_lieu']??'') ?></small>
    </span>
  </div>
  <?php endif; ?>
</div>

<!-- Competences -->
<?php if (!empty($competences)): ?>
<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Competences</p>
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:1.25rem">
  <?php foreach ($competences as $c): ?>
  <span style="background:#f5f5f0;color:#4a4a5a;padding:3px 10px;border-radius:20px;font-size:12px">
    <?= htmlspecialchars($compLabels[trim($c)] ?? trim($c)) ?>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Formations -->
<?php if (!empty($formations)): ?>
<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Formations</p>
<div style="margin-bottom:1.25rem">
  <?php foreach ($formations as $f): ?>
  <span style="display:inline-block;background:#FAEEDA;color:#633806;padding:3px 10px;border-radius:20px;font-size:12px;margin:3px">
    &#10003; <?= htmlspecialchars(FORMATION_LABELS[$f] ?? $f) ?>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Expérience -->
<?php if (!empty($ins['experience'])): ?>
<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Experience</p>
<p style="font-size:13px;color:#4a4a5a;margin-bottom:1.25rem"><?= htmlspecialchars($ins['experience']) ?></p>
<?php endif; ?>

<!-- Motivation -->
<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Lettre de motivation</p>
<div class="motivation-box"><?= nl2br(htmlspecialchars($ins['motivation']??'')) ?></div>

<!-- Paiement -->
<div style="background:#f5f5f0;border-radius:10px;padding:1rem;margin-bottom:1.25rem">
  <p style="font-size:12px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px">Paiement</p>
  <?php
  $lines = [
      ['Cotisation',  $ins['cotisation']],
      ['Formations',  $ins['montant_formations']],
      ['Frais admin', $ins['frais_admin']],
  ];
  foreach ($lines as $l): ?>
  <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #e8e8e0;font-size:13px">
    <span style="color:#5a5a50"><?= $l[0] ?></span><strong><?= number_format((float)$l[1],2) ?> DT</strong>
  </div>
  <?php endforeach; ?>
  <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:15px">
    <span><strong>Total</strong></span>
    <strong style="color:#d4a017"><?= number_format((float)$ins['montant_total'],2) ?> DT</strong>
  </div>
  <div style="margin-top:10px;font-size:12px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
    <span style="color:#5a5a50">Mode : <strong><?= htmlspecialchars($pmLabel) ?></strong></span>
    <?php if ($ins['paiement_confirme']): ?>
    <span style="color:#3B6D11;font-weight:500">&#10003; Confirme le <?= date('d/m/Y', strtotime($ins['date_paiement']??'now')) ?></span>
    <?php else: ?>
    <span style="color:#854F0B">&#9203; En attente</span>
    <button onclick="confirmerPaiement(<?= $ins['id'] ?>);closeDetail()" class="action-btn btn-payer" style="margin-left:auto">Confirmer</button>
    <?php endif; ?>
  </div>
</div>

<!-- Note admin -->
<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Note interne</p>
<textarea id="commentInput" class="comment-area" placeholder="Ajouter une note..."><?= htmlspecialchars($ins['commentaire_admin']??'') ?></textarea>
<button onclick="saveComment(<?= $ins['id'] ?>)" style="padding:7px 18px;background:#0a1628;color:#d4a017;border:none;border-radius:8px;font-size:13px;cursor:pointer;font-family:'DM Sans',sans-serif;margin-bottom:1.25rem">Sauvegarder</button>

<!-- Actions -->
<div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:1.25rem;border-top:1px solid #e8e8e0">
  <button onclick="changerStatut(<?= $ins['id'] ?>,'valide');closeDetail()" class="action-btn btn-valider" style="padding:8px 16px">&#10003; Valider</button>
  <button onclick="changerStatut(<?= $ins['id'] ?>,'refuse');closeDetail()" class="action-btn btn-refuser" style="padding:8px 16px">&#10005; Refuser</button>
  <button onclick="changerStatut(<?= $ins['id'] ?>,'liste_attente');closeDetail()" class="action-btn btn-attente" style="padding:8px 16px">&#9208; Liste attente</button>
</div>
