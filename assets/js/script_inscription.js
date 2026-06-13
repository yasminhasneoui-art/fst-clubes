/* FST Clubs — script_inscription.js */

var clubPrices = {ieee:40,enactus:30,chess:15,astro:20,securinettes:25,robotique:35,media:20,green:15};
var clubNames  = {ieee:'IEEE FST Tunis',enactus:'Enactus FST',chess:'Club Echecs FST',astro:'Club Astronomie FST',securinettes:'Securinettes FST',robotique:'Club Robotique FST',media:'Club Media & Creativite',green:'Green Campus FST'};
var clubIcons  = {ieee:'💻',enactus:'🚀',chess:'♟',astro:'🔭',securinettes:'🛡️',robotique:'🤖',media:'🎬',green:'🌱'};

var currentStep = 1;
var selectedClub = '';

/* ── Init: lire le club depuis l'URL ── */
window.addEventListener('DOMContentLoaded', function () {
  var params = new URLSearchParams(window.location.search);
  var club = params.get('club') || '';
  if (club && clubNames[club]) {
    selectedClub = club;
    document.getElementById('clubInput').value = club;
    /* Afficher le badge club */
    document.getElementById('clubBadge').style.display = 'inline-flex';
    document.getElementById('clubBadgeIcon').textContent = clubIcons[club] || '🏆';
    document.getElementById('clubBadgeName').textContent = clubNames[club];
    document.getElementById('clubBadgePrix').textContent = '(' + clubPrices[club] + ' DT)';
  }
  updateTotal();
});

/* ── Navigation entre étapes ── */
function goToStep(n) {
  document.querySelectorAll('.form-step').forEach(function (el) { el.classList.remove('active'); });
  var target = document.getElementById('step-' + n);
  if (target) target.classList.add('active');

  for (var i = 1; i <= 4; i++) {
    var si = document.getElementById('si-' + i);
    if (!si) continue;
    si.classList.remove('active', 'done');
    if (i < n) si.classList.add('done');
    else if (i === n) si.classList.add('active');
  }
  document.getElementById('progressFill').style.width = (n * 25) + '%';
  currentStep = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep(n) {
  if (n === 2 && !validateStep1()) return;
  if (n === 3) { loadCreneaux(); }
  if (n === 4) { updateSummary(); }
  goToStep(n);
}

function prevStep(n) { goToStep(n); }

/* ── Validation étape 1 ── */
function validateStep1() {
  if (!selectedClub) { alert('Aucun club selectionne. Retournez a l\'accueil et cliquez sur un club.'); return false; }
  var adhesion = document.querySelector('input[name="adhesion"]:checked');
  if (!adhesion) { alert('Veuillez selectionner un type d\'adhesion.'); return false; }
  var prenom = document.querySelector('[name="prenom"]');
  var nom    = document.querySelector('[name="nom"]');
  var mat    = document.querySelector('[name="matricule"]');
  var cin    = document.querySelector('[name="cin"]');
  var email  = document.querySelector('[name="email"]');
  var tel    = document.querySelector('[name="telephone"]');
  var fil    = document.querySelector('[name="filiere"]');
  var niv    = document.querySelector('[name="niveau"]');
  var dispo  = document.querySelector('[name="dispo"]');
  var motiv  = document.querySelector('[name="motivation"]');
  var fields = [prenom, nom, mat, cin, email, tel, fil, niv, dispo, motiv];
  for (var i = 0; i < fields.length; i++) {
    if (!fields[i] || !fields[i].value.trim()) {
      if (fields[i]) { fields[i].focus(); fields[i].style.borderColor = '#e24b4a'; setTimeout(function(el){return function(){el.style.borderColor='';};}(fields[i]), 2000); }
      alert('Veuillez remplir tous les champs obligatoires.');
      return false;
    }
  }
  if (motiv.value.length < 100) { motiv.focus(); alert('La motivation doit contenir au moins 100 caracteres.'); return false; }
  if (!/^\d{8}$/.test(mat.value)) { mat.focus(); alert('Matricule invalide (8 chiffres requis).'); return false; }
  if (!/^\d{8}$/.test(cin.value)) { cin.focus(); alert('CIN invalide (8 chiffres requis).'); return false; }
  return true;
}

/* ── Charger les créneaux via AJAX ── */
function loadCreneaux() {
  if (!selectedClub) return;
  document.getElementById('creneauxLoading').style.display = 'block';
  document.getElementById('creneauxContainer').style.display = 'none';
  document.getElementById('creneauxError').style.display = 'none';

  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'actions/get_creneaux.php?club=' + encodeURIComponent(selectedClub), true);
  xhr.onload = function () {
    document.getElementById('creneauxLoading').style.display = 'none';
    if (xhr.status === 200) {
      try {
        var data = JSON.parse(xhr.responseText);
        renderCreneaux(data);
      } catch (e) {
        document.getElementById('creneauxError').style.display = 'block';
      }
    } else {
      document.getElementById('creneauxError').style.display = 'block';
    }
  };
  xhr.onerror = function () {
    document.getElementById('creneauxLoading').style.display = 'none';
    document.getElementById('creneauxError').style.display = 'block';
  };
  xhr.send();
}
function renderCreneaux(data) {
  var grid = document.getElementById('creneauxGrid');
  var info = document.getElementById('creneauxInfo');
  var container = document.getElementById('creneauxContainer');

  if (!data || !data.creneaux) { document.getElementById('creneauxError').style.display = 'block'; return; }

  var libres = data.creneaux.filter(function (c) { return !c.pris; }).length;
  info.innerHTML = '&#128197; <strong>' + (data.date_label || '') + '</strong> &nbsp;·&nbsp; &#128205; ' + (data.lieu || '') +
    ' &nbsp;·&nbsp; ' + libres + ' creneau(x) disponible(s)';

  grid.innerHTML = '';
  if (data.creneaux.length === 0) {
    grid.innerHTML = '<p style="color:#9a9a90;font-size:13px">Aucun creneau disponible pour ce club.</p>';
    container.style.display = 'block';
    return;
  }

  data.creneaux.forEach(function (c) {
    var div = document.createElement('div');
    div.className = 'creneau-card' + (c.pris ? ' pris' : '');
    var inputId = 'cr-' + c.id;
    div.innerHTML = '<input type="radio" name="_creneau_display" id="' + inputId + '" value="' + c.id + '"' + (c.pris ? ' disabled' : '') + ' onchange="selectCreneau(' + c.id + ')">' +
      '<label for="' + inputId + '">' +
        '<span class="cr-time">' + c.heure_debut.slice(0,5) + ' – ' + c.heure_fin.slice(0,5) + '</span>' +
        '<span class="cr-lieu">' + (c.pris ? '🔴 Reserve' : '🟢 Disponible') + '</span>' +
      '</label>';
    grid.appendChild(div);
  });
  container.style.display = 'block';
}

function selectCreneau(id) {
  document.getElementById('creneauInput').value = id;
}

/* ── Compteur motivation ── */
document.addEventListener('input', function (e) {
  if (e.target.name === 'motivation') {
    var n = e.target.value.length;
    var el = document.getElementById('charCount');
    if (el) { el.textContent = n; el.style.color = n >= 100 ? '#3B6D11' : '#e24b4a'; }
  }
});

/* ── Total ── */
function updateTotal() {
  var total = 0;
  document.querySelectorAll('input[name="formations[]"]:checked').forEach(function (cb) {
    var card = cb.closest('.formation-select-card');
    if (card) total += parseInt(card.dataset.price || 0);
  });
  return total;
}

function updateSummary() {
  var cotis = clubPrices[selectedClub] || 0;
  var formations = updateTotal();
  var total = cotis + formations + 2;

  document.getElementById('recapClub').textContent = clubNames[selectedClub] || selectedClub;
  document.getElementById('recapCotis').textContent = cotis + ' DT';
  document.getElementById('recapTotal').textContent = total + ' DT';

  var formRow = document.getElementById('recapFormationsRow');
  if (formations > 0) {
    formRow.style.display = 'flex';
    document.getElementById('recapFormations').textContent = formations + ' DT';
  } else {
    formRow.style.display = 'none';
  }
}

/* ── Mode de paiement ── */
function selectPM(method, btn) {
  document.querySelectorAll('.pm-form').forEach(function (el) { el.classList.remove('active'); });
  document.querySelectorAll('.pm-tab').forEach(function (el) { el.classList.remove('active'); });
  var form = document.getElementById('pm-' + method);
  if (form) form.classList.add('active');
  if (btn) btn.classList.add('active');
  document.getElementById('modeInput').value = method;
  var alert = document.getElementById('pmAlert');
  if (alert) alert.style.display = 'none';
}

/* ── Format carte ── */
function formatCard(input) {
  var v = input.value.replace(/\D/g, '').slice(0, 16);
  input.value = v.replace(/(.{4})/g, '$1  ').trim();
}

/* ── Submit final ── */
function validateAndSubmit() {
  var pm = document.getElementById('modeInput').value;
  if (!pm) {
    var al = document.getElementById('pmAlert');
    if (al) { al.style.display = 'block'; al.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    return false;
  }
  return true;
}

/* ── Navbar scroll ── */
window.addEventListener('scroll', function () {
  var nav = document.getElementById('navbar');
  if (nav) nav.style.boxShadow = window.scrollY > 30 ? '0 4px 24px rgba(0,0,0,0.2)' : 'none';
});
