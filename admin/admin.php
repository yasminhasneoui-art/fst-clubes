<?php
/* FST Clubs — admin.php
   Administration des inscriptions
   Login simple (pas de session complexe, cookie sécurisé) */

require_once __DIR__ . '../includes/config.php';

/* ── Authentification par cookie simple ── */
$cookieName = 'fst_admin_ok';
$loggedIn   = (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === hash('sha256', ADMIN_PASSWORD));
$loginError = '';

/* Traitement POST connexion */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_LOGIN && $p === ADMIN_PASSWORD) {
        setcookie($cookieName, hash('sha256', ADMIN_PASSWORD), time() + 3600 * 8, '/', '', false, true);
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Identifiant ou mot de passe incorrect.';
    }
}

/* Déconnexion */
if (isset($_GET['logout'])) {
    setcookie($cookieName, '', time() - 3600, '/');
    header('Location: admin.php');
    exit;
}

/* ── Actions AJAX (POST JSON) ── */
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $pdo = getDB();
    $id  = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'changer_statut' && $id) {
        $statut = $_POST['statut'] ?? '';
        $valides = ['en_attente','valide','refuse','liste_attente'];
        if (!in_array($statut, $valides, true)) { echo json_encode(['ok'=>false,'msg'=>'Statut invalide']); exit; }
        $pdo->prepare("UPDATE inscriptions SET statut=:s WHERE id=:id")->execute([':s'=>$statut,':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if ($action === 'confirmer_paiement' && $id) {
        $pdo->prepare("UPDATE inscriptions SET paiement_confirme=1, date_paiement=CURDATE() WHERE id=:id")->execute([':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if ($action === 'supprimer' && $id) {
        $pdo->prepare("DELETE FROM inscriptions WHERE id=:id")->execute([':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if ($action === 'commentaire' && $id) {
        $comment = htmlspecialchars(trim($_POST['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8');
        $pdo->prepare("UPDATE inscriptions SET commentaire_admin=:c WHERE id=:id")->execute([':c'=>$comment,':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    echo json_encode(['ok'=>false,'msg'=>'Action inconnue']);
    exit;
}

/* ── Page de connexion ── */
if (!$loggedIn) { showLogin($loginError); exit; }

/* ── Page admin principale ── */
$pdo = getDB();

/* Filtres */
$filterClub   = sanitize($_GET['club']   ?? '');
$filterStatut = sanitize($_GET['statut'] ?? '');
$filterSearch = sanitize($_GET['q']      ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

/* Requête */
$where = ['1=1'];
$params = [];
if ($filterClub)   { $where[] = 'club=:club';   $params[':club']   = $filterClub; }
if ($filterStatut) { $where[] = 'statut=:stat';  $params[':stat']   = $filterStatut; }
if ($filterSearch) {
    $where[] = '(nom LIKE :q OR prenom LIKE :q OR matricule LIKE :q OR email LIKE :q OR ref LIKE :q)';
    $params[':q'] = '%' . $filterSearch . '%';
}
$whereSQL = implode(' AND ', $where);

$total      = (int)$pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE $whereSQL")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE $whereSQL")->execute($params) && ($pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE $whereSQL")->execute($params)) : 0;
$stTotal    = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE $whereSQL");
$stTotal->execute($params);
$total      = (int)$stTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stRows = $pdo->prepare("SELECT * FROM inscriptions WHERE $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stRows->execute($params);
$inscriptions = $stRows->fetchAll();

/* Stats résumé */
$stats = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(statut='valide') AS valides,
    SUM(statut='en_attente') AS attente,
    SUM(statut='refuse') AS refuses,
    SUM(paiement_confirme=1) AS payes,
    COALESCE(SUM(montant_total),0) AS revenus
FROM inscriptions")->fetch();

/* Export CSV */
if (isset($_GET['export'])) {
    $rows = $pdo->query("SELECT ref,club,adhesion,prenom,nom,matricule,cin,email,telephone,filiere,niveau,disponibilite,formations,mode_paiement,cotisation,montant_formations,frais_admin,montant_total,paiement_confirme,statut,created_at FROM inscriptions ORDER BY created_at DESC")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inscriptions_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Ref','Club','Adhesion','Prenom','Nom','Matricule','CIN','Email','Tel','Filiere','Niveau','Dispo','Formations','Paiement','Cotis','Formations DT','Frais','Total','Paye','Statut','Date'], ';');
    foreach ($rows as $r) fputcsv($out, array_values($r), ';');
    fclose($out);
    exit;
}

function statutBadge(string $s): string {
    return match($s) {
        'valide'        => '<span style="background:#EAF3DE;color:#27500A;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500">&#10003; Valide</span>',
        'refuse'        => '<span style="background:#FCEBEB;color:#791F1F;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500">&#10005; Refuse</span>',
        'liste_attente' => '<span style="background:#FAEEDA;color:#633806;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500">&#9208; Attente</span>',
        default         => '<span style="background:#f5f5f0;color:#5a5a50;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500">&#9203; En cours</span>',
    };
}
function pmBadge(string $pm, int $confirme): string {
    $label = PM_LABELS[$pm] ?? $pm;
    $col = $confirme ? '#3B6D11' : '#854F0B';
    $icon = $confirme ? '&#10003;' : '&#9203;';
    return "<span style='font-size:12px;color:{$col}'>{$icon} {$label}</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin — FST Clubs</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1628;--gold:#d4a017;--bg:#f5f5f0;--white:#fff;--border:#e8e8e0;--text:#1a1a2e;--g400:#9a9a90}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px}
    a{color:inherit;text-decoration:none}

    /* Sidebar */
    .sidebar{width:220px;background:var(--navy);min-height:100vh;position:fixed;top:0;left:0;padding:1.5rem 1rem;display:flex;flex-direction:column;gap:0.5rem;z-index:100}
    .sidebar .logo{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;padding-bottom:1.25rem;border-bottom:1px solid rgba(255,255,255,0.1)}
    .sidebar .logo-icon{width:38px;height:38px;background:var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--navy);font-family:'Playfair Display',serif;flex-shrink:0}
    .sidebar .logo-text{color:#fff;font-size:13px;font-weight:500}
    .sidebar .logo-text small{display:block;color:rgba(255,255,255,0.45);font-size:11px}
    .nav-item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13px;cursor:pointer;transition:all .15s}
    .nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.08);color:#fff}
    .nav-item span.ico{width:18px;text-align:center;flex-shrink:0}
    .sidebar-footer{margin-top:auto;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.1)}
    .sidebar-footer a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.45);font-size:12px;padding:6px 10px;border-radius:6px}
    .sidebar-footer a:hover{color:#fff;background:rgba(255,255,255,0.06)}

    /* Main */
    .main{margin-left:220px;padding:2rem}

    /* Stats */
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
    .stat-card{background:var(--white);border-radius:12px;border:1px solid var(--border);padding:1.25rem}
    .stat-card .val{font-size:2rem;font-weight:700;font-family:'Playfair Display',serif;color:var(--navy);line-height:1}
    .stat-card .lbl{font-size:12px;color:var(--g400);margin-top:4px}
    .stat-card.gold .val{color:var(--gold)}

    /* Filtres */
    .filters-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1.5rem}
    .filters-bar input,.filters-bar select{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;background:#fff}
    .filters-bar input:focus,.filters-bar select:focus{border-color:var(--gold)}
    .btn-filter{padding:8px 18px;background:var(--navy);color:var(--gold);border:none;border-radius:8px;cursor:pointer;font-size:13px;font-family:'DM Sans',sans-serif}
    .btn-reset{padding:8px 14px;background:#f5f5f0;color:#4a4a5a;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:13px;font-family:'DM Sans',sans-serif}

    /* Table */
    .table-wrap{background:var(--white);border-radius:12px;border:1px solid var(--border);overflow:hidden}
    .table-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;border-bottom:1px solid var(--border)}
    .table-head h3{font-size:15px}
    table{width:100%;border-collapse:collapse}
    thead{background:var(--bg)}
    th{padding:10px 14px;text-align:left;font-size:12px;font-weight:500;color:var(--g400);text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid var(--border)}
    td{padding:11px 14px;border-bottom:1px solid #f0f0e8;vertical-align:middle;font-size:13px}
    tr:last-child td{border:none}
    tr:hover td{background:#fafaf5}

    /* Action buttons */
    .action-btn{padding:4px 10px;border:none;border-radius:6px;cursor:pointer;font-size:11px;font-weight:500;font-family:'DM Sans',sans-serif;white-space:nowrap}
    .btn-valider{background:#EAF3DE;color:#27500A}
    .btn-refuser{background:#FCEBEB;color:#791F1F}
    .btn-attente{background:#FAEEDA;color:#633806}
    .btn-payer{background:#EAF3DE;color:#3B6D11}
    .btn-del{background:#f5f5f0;color:#5a5a50}

    /* Pills club */
    .pill-club{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:500}
    .club-ieee{background:#E6EEF8;color:#003366}
    .club-enactus{background:#E6F4EE;color:#004B28}
    .club-chess{background:#F0F0EE;color:#1a1a1a}
    .club-astro{background:#E8E8F8;color:#0a0a2e}
    .club-securinettes{background:#FDE8E8;color:#3a0a0a}
    .club-robotique{background:#EAF2FB;color:#1a3a5c}
    .club-media{background:#F0EBFE;color:#5b21b6}
    .club-green{background:#E8F5EE;color:#14532d}

    /* Pagination */
    .pagination{display:flex;align-items:center;gap:6px;padding:1rem 1.25rem;justify-content:center}
    .pg-btn{padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--text);background:#fff}
    .pg-btn.active,.pg-btn:hover{background:var(--navy);color:var(--gold);border-color:var(--navy)}
    .pg-info{font-size:12px;color:var(--g400);margin-left:8px}

    /* Overlay detail */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;overflow-y:auto}
    .overlay.open{display:flex;align-items:flex-start;justify-content:center;padding:3rem 1rem}
    .detail-box{background:#fff;border-radius:16px;width:100%;max-width:640px;position:relative;max-height:90vh;overflow-y:auto}
    .close-btn{position:absolute;top:12px;right:14px;background:none;border:none;font-size:18px;cursor:pointer;color:var(--g400);z-index:1}

    /* Toast */
    .toast{display:none;position:fixed;bottom:2rem;right:2rem;background:var(--navy);color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;z-index:999}

    /* Detail styles (injectés par admin_detail.php) */
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1.25rem}
    .detail-cell{background:#f5f5f0;border-radius:8px;padding:10px 14px}
    .detail-cell label{display:block;font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px}
    .motivation-box{background:#f5f5f0;border-radius:8px;padding:1rem;font-size:13px;line-height:1.7;color:#4a4a5a;margin-bottom:1.25rem;max-height:160px;overflow-y:auto}
    .comment-area{width:100%;border:1px solid #e8e8e0;border-radius:8px;padding:10px;font-size:13px;font-family:'DM Sans',sans-serif;resize:vertical;min-height:80px;margin-bottom:8px}
    .comment-area:focus{outline:none;border-color:#d4a017}

    @media(max-width:768px){
      .sidebar{display:none}
      .main{margin-left:0;padding:1rem}
      .detail-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="logo">
    <div class="logo-icon">FST</div>
    <div class="logo-text"><strong>Admin</strong><small>FST Clubs</small></div>
  </div>
  <a href="admin.php" class="nav-item active"><span class="ico">&#128101;</span> Inscriptions</a>
  <a href="../calendrier.html" target="_blank" class="nav-item"><span class="ico">&#128197;</span> Calendrier</a>
  <a href="../index.html" target="_blank" class="nav-item"><span class="ico">&#127760;</span> Voir le site</a>
  <div class="sidebar-footer">
    <a href="admin.php?logout=1"><span>&#128682;</span> Deconnexion</a>
  </div>
</nav>

<!-- MAIN -->
<main class="main">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div><h1 style="font-size:1.5rem;font-family:'Playfair Display',serif">Gestion des inscriptions</h1>
      <p style="color:var(--g400);font-size:13px">FST Clubs — Annee <?= ANNEE ?></p></div>
    <a href="admin.php?export=1&club=<?= urlencode($filterClub) ?>&statut=<?= urlencode($filterStatut) ?>&q=<?= urlencode($filterSearch) ?>" style="padding:8px 18px;background:var(--navy);color:var(--gold);border-radius:8px;font-size:13px">&#8595; Exporter CSV</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Total dossiers</div></div>
    <div class="stat-card"><div class="val" style="color:#27500A"><?= $stats['valides'] ?></div><div class="lbl">Valides</div></div>
    <div class="stat-card"><div class="val" style="color:#854F0B"><?= $stats['attente'] ?></div><div class="lbl">En attente</div></div>
    <div class="stat-card"><div class="val" style="color:#791F1F"><?= $stats['refuses'] ?></div><div class="lbl">Refuses</div></div>
    <div class="stat-card gold"><div class="val"><?= $stats['payes'] ?></div><div class="lbl">Payes</div></div>
    <div class="stat-card gold"><div class="val"><?= number_format($stats['revenus'], 0) ?></div><div class="lbl">DT revenus</div></div>
  </div>

  <!-- Filtres -->
  <form method="GET" action="admin.php">
    <div class="filters-bar">
      <input type="text" name="q" placeholder="&#128269; Nom, matricule, email, ref..." value="<?= htmlspecialchars($filterSearch) ?>" style="min-width:200px"/>
      <select name="club">
        <option value="">Tous les clubs</option>
        <?php foreach (CLUB_LABELS as $code => $label): ?>
        <option value="<?= $code ?>" <?= $filterClub===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="statut">
        <option value="">Tous les statuts</option>
        <option value="en_attente"    <?= $filterStatut==='en_attente'?'selected':'' ?>>&#9203; En attente</option>
        <option value="valide"        <?= $filterStatut==='valide'?'selected':'' ?>>&#10003; Valide</option>
        <option value="refuse"        <?= $filterStatut==='refuse'?'selected':'' ?>>&#10005; Refuse</option>
        <option value="liste_attente" <?= $filterStatut==='liste_attente'?'selected':'' ?>>&#9208; Liste attente</option>
      </select>
      <button type="submit" class="btn-filter">Filtrer</button>
      <a href="admin.php" class="btn-reset">&#10005; Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <div class="table-head">
      <h3>Dossiers <span style="color:var(--g400);font-size:13px;font-weight:400">(<?= $total ?> resultat<?= $total>1?'s':'' ?>)</span></h3>
    </div>
    <?php if (empty($inscriptions)): ?>
    <div style="text-align:center;padding:3rem;color:var(--g400)">
      <div style="font-size:3rem;margin-bottom:1rem">&#128235;</div>
      <p>Aucun dossier trouve.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Ref.</th><th>Etudiant</th><th>Club</th><th>Paiement</th><th>Montant</th><th>Statut</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inscriptions as $ins):
          $clubLabel = CLUB_LABELS[$ins['club']] ?? $ins['club'];
        ?>
        <tr>
          <td><button onclick="openDetail(<?= $ins['id'] ?>)" style="background:none;border:none;color:var(--gold);font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px"><?= htmlspecialchars($ins['ref']) ?></button></td>
          <td><strong><?= htmlspecialchars($ins['prenom'].' '.$ins['nom']) ?></strong><br/><small style="color:var(--g400)"><?= htmlspecialchars($ins['matricule']) ?> &middot; <?= htmlspecialchars($ins['email']) ?></small></td>
          <td><span class="pill-club club-<?= $ins['club'] ?>"><?= htmlspecialchars($clubLabel) ?></span></td>
          <td><?= pmBadge($ins['mode_paiement'], (int)$ins['paiement_confirme']) ?></td>
          <td><strong style="color:var(--gold)"><?= number_format((float)$ins['montant_total'],2) ?> DT</strong></td>
          <td><?= statutBadge($ins['statut']) ?></td>
          <td><small><?= date('d/m/Y', strtotime($ins['created_at'])) ?><br/><span style="color:var(--g400)"><?= date('H:i', strtotime($ins['created_at'])) ?></span></small></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap">
              <?php if ($ins['statut']!=='valide'): ?>
              <button class="action-btn btn-valider" onclick="changerStatut(<?= $ins['id'] ?>,'valide')">&#10003; Valider</button>
              <?php endif; ?>
              <?php if ($ins['statut']!=='refuse'): ?>
              <button class="action-btn btn-refuser" onclick="changerStatut(<?= $ins['id'] ?>,'refuse')">&#10005; Refuser</button>
              <?php endif; ?>
              <?php if (!$ins['paiement_confirme']): ?>
              <button class="action-btn btn-payer" onclick="confirmerPaiement(<?= $ins['id'] ?>)">&#128176; Paiement</button>
              <?php endif; ?>
              <button class="action-btn btn-del" onclick="supprimerDossier(<?= $ins['id'] ?>,'<?= htmlspecialchars($ins['ref']) ?>')">&#128465;</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php if ($totalPages>1): ?>
    <div class="pagination">
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
      <a href="admin.php?page=<?= $p ?>&club=<?= urlencode($filterClub) ?>&statut=<?= urlencode($filterStatut) ?>&q=<?= urlencode($filterSearch) ?>" class="pg-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <span class="pg-info">Page <?= $page ?> / <?= $totalPages ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Modal detail -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeDetail()">
  <div class="detail-box">
    <button class="close-btn" onclick="closeDetail()">&#10005;</button>
    <div id="detailContent" style="padding:1.5rem">
      <p style="color:var(--g400);text-align:center;padding:2rem">Chargement...</p>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
/* AJAX helper */
async function post(data) {
  var fd = new FormData();
  for (var k in data) fd.append(k, data[k]);
  var r = await fetch('admin.php', { method:'POST', body:fd });
  return r.json();
}
function toast(msg, ok) {
  var el = document.getElementById('toast');
  el.textContent = msg;
  el.style.background = (ok !== false) ? '#0a1628' : '#A32D2D';
  el.style.display = 'block';
  setTimeout(function(){ el.style.display='none'; }, 2800);
}
async function changerStatut(id, statut) {
  var labels = {valide:'Valider', refuse:'Refuser', liste_attente:"Mettre en liste d'attente", en_attente:'Remettre en attente'};
  if (!confirm((labels[statut]||statut) + ' ce dossier ?')) return;
  var res = await post({action:'changer_statut', id:id, statut:statut});
  if (res.ok) { toast('Statut mis a jour &#10003;'); setTimeout(function(){ location.reload(); }, 800); }
  else toast('Erreur : ' + (res.msg||''), false);
}
async function confirmerPaiement(id) {
  if (!confirm('Confirmer ce paiement ?')) return;
  var res = await post({action:'confirmer_paiement', id:id});
  if (res.ok) { toast('Paiement confirme &#10003;'); setTimeout(function(){ location.reload(); }, 800); }
  else toast('Erreur', false);
}
async function supprimerDossier(id, ref) {
  if (!confirm('Supprimer definitivement ' + ref + ' ?')) return;
  var res = await post({action:'supprimer', id:id});
  if (res.ok) { toast('Dossier supprime'); setTimeout(function(){ location.reload(); }, 800); }
  else toast('Erreur', false);
}
async function openDetail(id) {
  document.getElementById('overlay').classList.add('open');
  document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:2rem;color:#9a9a90">Chargement...</p>';
  var r = await fetch('admin_detail.php?id=' + id);
  var html = await r.text();
  document.getElementById('detailContent').innerHTML = html;
}
function closeDetail() {
  document.getElementById('overlay').classList.remove('open');
}
async function saveComment(id) {
  var c = document.getElementById('commentInput').value;
  var res = await post({action:'commentaire', id:id, commentaire:c});
  toast(res.ok ? 'Commentaire sauvegarde &#10003;' : 'Erreur', res.ok);
}
</script>
</body>
</html>
<?php
function showLogin(string $error = ''): void { ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Connexion Admin — FST Clubs</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:#0a1628;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
    .card{background:#fff;border-radius:20px;padding:2.5rem 2rem;width:100%;max-width:360px;text-align:center}
    .logo{width:56px;height:56px;background:#d4a017;border-radius:12px;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:#0a1628;margin:0 auto 1.25rem}
    h2{font-size:1.4rem;color:#1a1a2e;margin-bottom:4px}
    .sub{font-size:13px;color:#9a9a90;margin-bottom:1.75rem}
    .fg{text-align:left;margin-bottom:1rem}
    label{display:block;font-size:12px;font-weight:500;color:#4a4a5a;margin-bottom:5px}
    input{width:100%;padding:10px 14px;border:1px solid #e8e8e0;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none}
    input:focus{border-color:#d4a017;box-shadow:0 0 0 3px rgba(212,160,23,0.12)}
    .btn{width:100%;padding:12px;background:#0a1628;color:#d4a017;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;font-family:'DM Sans',sans-serif;margin-top:0.5rem}
    .err{background:#fcebeb;color:#791F1F;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:1rem}
    .back{display:block;margin-top:1.25rem;font-size:13px;color:#9a9a90}
    .back a{color:#d4a017}
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">FST</div>
    <h2>Espace Administration</h2>
    <p class="sub">FST Clubs — Gestion des inscriptions</p>
    <?php if ($error): ?><div class="err">&#9888; <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="do_login" value="1"/>
      <div class="fg"><label>Identifiant</label><input type="text" name="username" placeholder="admin" required autocomplete="username"/></div>
      <div class="fg"><label>Mot de passe</label><input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"/></div>
      <button type="submit" class="btn">Se connecter &#8594;</button>
    </form>
    <p class="back"><a href="../index.html">&#8592; Retour au site</a></p>
  </div>
</body>
</html>
<?php }
