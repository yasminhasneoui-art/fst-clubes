/* ═══════════════════════════════════════════
   FST CLUBS — SCRIPT.JS
   JS simple et basique
═══════════════════════════════════════════ */

/* ─── NAVBAR scroll ─── */
window.addEventListener('scroll', function () {
  var nav = document.getElementById('navbar');
  if (nav) {
    nav.style.boxShadow = window.scrollY > 30 ? '0 4px 24px rgba(0,0,0,0.2)' : 'none';
  }
});

/* ─── Menu mobile ─── */
function toggleMenu() {
  var m = document.getElementById('mobileMenu');
  if (m) m.classList.toggle('open');
}

/* ─── INSCRIPTION : Étapes du formulaire ─── */
var currentStep = 1;
var totalSteps  = 3;

function showStep(n) {
  document.querySelectorAll('.form-step').forEach(function (s) { s.classList.remove('active'); });
  document.querySelectorAll('.insc-step').forEach(function (s) { s.classList.remove('active'); });

  var step = document.getElementById('step-' + n);
  if (step) step.classList.add('active');

  var si = document.getElementById('si-' + n);
  if (si) si.classList.add('active');

  var fill = document.getElementById('progressFill');
  if (fill) fill.style.width = (n / totalSteps * 100) + '%';

  currentStep = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep(n) {
  if (!validateStep(currentStep)) return;
  showStep(n);
}

function prevStep(n) {
  showStep(n);
}

/* ─── Validation basique de l'étape courante ─── */
function validateStep(n) {
  var step = document.getElementById('step-' + n);
  if (!step) return true;

  var inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
  var ok = true;

  inputs.forEach(function (inp) {
    inp.classList.remove('invalid');
    var val = inp.value.trim();

    if (inp.type === 'radio') {
      // Vérifier qu'au moins un radio du groupe est coché
      var name   = inp.name;
      var group  = step.querySelectorAll('input[name="' + name + '"]');
      var checked = false;
      group.forEach(function (r) { if (r.checked) checked = true; });
      if (!checked) {
        ok = false;
        group.forEach(function (r) { r.closest('.radio-opt') && r.closest('.radio-opt').classList.add('invalid'); });
      }
      return;
    }

    if (!val) {
      inp.classList.add('invalid');
      ok = false;
      return;
    }

    if (inp.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
      inp.classList.add('invalid');
      ok = false;
    }

    if (inp.name === 'matricule' && !/^\d{8}$/.test(val)) {
      inp.classList.add('invalid');
      ok = false;
    }

    if (inp.name === 'cin' && !/^\d{8}$/.test(val)) {
      inp.classList.add('invalid');
      ok = false;
    }
  });

  if (!ok) {
    var first = step.querySelector('.invalid');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    showAlert('Veuillez remplir correctement tous les champs obligatoires.', 'err');
  }
  return ok;
}

/* ─── Calcul du total en temps réel ─── */
var clubPrices = {
  ieee: 40, enactus: 30, chess: 15, astro: 20,
  securinettes: 25, robotique: 35, environnement: 15, journalisme: 20
};

var formationPrices = {
  iot: 80, ml: 120, leadership: 60, ctf: 100,
  forensics: 90, astrophysique: 40, robotique: 75
};

function recalcTotal() {
  var club      = document.getElementById('hiddenClub');
  var adhesion  = document.getElementById('hiddenAdhesion');
  if (!club) return;

  var clubKey   = club.value;
  var cotisation = (clubPrices[clubKey] || 0);

  // Formations cochées
  var formations = document.querySelectorAll('input[name="formations[]"]:checked');
  var montantForm = 0;
  formations.forEach(function (f) { montantForm += (formationPrices[f.value] || 0); });

  var frais = 5;
  var total = cotisation + montantForm + frais;

  setId('tot-cotisation', cotisation.toFixed(2) + ' DT');
  setId('tot-formations', montantForm.toFixed(2) + ' DT');
  setId('tot-frais',      frais.toFixed(2) + ' DT');
  setId('tot-total',      total.toFixed(2) + ' DT');

  var hid = document.getElementById('hiddenTotal');
  if (hid) hid.value = total.toFixed(2);
  var hidForm = document.getElementById('hiddenFormationsTotal');
  if (hidForm) hidForm.value = montantForm.toFixed(2);
}

function setId(id, val) {
  var el = document.getElementById(id);
  if (el) el.textContent = val;
}

/* ─── Mode paiement : afficher détails ─── */
function showPmDetails(mode) {
  var details = {
    carte:    '💳 Vous serez invité(e) à saisir vos données bancaires à l\'étape suivante. Paiement sécurisé.',
    virement: '🏦 Virement vers : Compte FST Tunis · RIB : 07 XXX 0000012345 67<br>Indiquer votre nom et numéro de référence.',
    especes:  '💵 Paiement en espèces à la scolarité de la FST, bureau 104, muni(e) de ce récapitulatif.'
  };
  var box = document.getElementById('pmDetails');
  if (box) {
    box.style.display = 'block';
    box.innerHTML = details[mode] || '';
  }
}

/* ─── Alerte inline ─── */
function showAlert(msg, type) {
  var a = document.getElementById('formAlert');
  if (!a) return;
  a.className = 'alert alert-' + type;
  a.innerHTML = (type === 'err' ? '⚠️ ' : '✓ ') + msg;
  a.style.display = 'flex';
  setTimeout(function () { a.style.display = 'none'; }, 4000);
}

/* ─── PAGE INDEX : Club sélectionné → localStorage ─── */
function selectClub(key) {
  localStorage.setItem('fst_club', key);
  window.location.href = 'inscription.html';
}

/* ─── PAGE INSCRIPTION : Lire le club depuis localStorage ─── */
function loadClubFromStorage() {
  var key     = localStorage.getItem('fst_club') || '';
  var banner  = document.getElementById('clubBanner');
  var hidClub = document.getElementById('hiddenClub');

  if (!key || !banner) {
    // Aucun club choisi → rediriger vers l'accueil
    if (window.location.pathname.indexOf('inscription') !== -1) {
      document.getElementById('noClubMsg') && (document.getElementById('noClubMsg').style.display = 'block');
    }
    return;
  }

  if (hidClub) hidClub.value = key;

  var clubs = {
    ieee:          { label: 'IEEE FST Tunis',          emoji: '⚡', prix: 40,  couleur: '#185FA5' },
    enactus:       { label: 'Enactus FST',             emoji: '🚀', prix: 30,  couleur: '#3B6D11' },
    chess:         { label: 'Club Échecs FST',         emoji: '♟', prix: 15,  couleur: '#6B4E0A' },
    astro:         { label: 'Club Astronomie FST',     emoji: '🔭', prix: 20,  couleur: '#4C1D95' },
    securinettes:  { label: 'Securinettes FST',        emoji: '🛡️', prix: 25,  couleur: '#B91C1C' },
    robotique:     { label: 'Club Robotique FST',      emoji: '🤖', prix: 35,  couleur: '#0E7490' },
    environnement: { label: 'Club Environnement FST',  emoji: '🌱', prix: 15,  couleur: '#166534' },
    journalisme:   { label: 'Club Journalisme FST',    emoji: '📰', prix: 20,  couleur: '#C2410C' }
  };

  var c = clubs[key];
  if (!c) return;

  banner.style.borderColor = c.couleur;
  banner.style.backgroundColor = c.couleur + '10';
  banner.querySelector('.club-banner-emoji').textContent = c.emoji;
  banner.querySelector('.club-banner-name').textContent  = c.label;
  banner.querySelector('.club-banner-cat').textContent   = c.prix + ' DT / an · Cotisation';
  banner.querySelector('.club-banner-price').textContent = c.prix + ' DT';
  banner.querySelector('.club-banner-price').style.color = c.couleur;

  recalcTotal();
}

/* ─── PAIEMENT : Afficher infos selon mode ─── */
function onPmChange(radio) {
  var details = {
    carte:    '<strong>Carte bancaire</strong> — Paiement sécurisé en ligne. Visa / Mastercard acceptées.',
    virement: '<strong>Virement bancaire</strong> — RIB : 07 100 0000012345 67 · Banque : STB<br>Objet : votre référence d\'inscription.',
    especes:  '<strong>Paiement en espèces</strong> — Rendez-vous à la scolarité, bureau 104, muni(e) de ce récapitulatif entre 8h et 15h.'
  };
  var box = document.getElementById('pmInfo');
  if (box) {
    box.style.display = 'block';
    box.innerHTML     = details[radio.value] || '';
  }
}

/* ─── CALENDRIER : Vérification inscription ─── */
function checkCalendrierAccess() {
  var ref  = localStorage.getItem('fst_ins_ref');
  var lock = document.getElementById('calLock');
  var cal  = document.getElementById('calContent');
  if (!lock || !cal) return;

  if (ref) {
    lock.style.display = 'none';
    cal.style.display  = 'block';
  } else {
    lock.style.display = 'block';
    cal.style.display  = 'none';
  }
}

/* ─── ADMIN : Changer statut (AJAX basique) ─── */
function changerStatut(id, statut, pwd) {
  if (!confirm('Confirmer le changement de statut en "' + statut + '" ?')) return;
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'admin.php');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function () { location.reload(); };
  xhr.send('action=statut&id=' + id + '&statut=' + encodeURIComponent(statut) + '&pwd=' + encodeURIComponent(pwd));
}

/* ─── INIT ─── */
document.addEventListener('DOMContentLoaded', function () {
  // Inscription page
  if (document.getElementById('inscForm')) {
    loadClubFromStorage();
    showStep(1);
    document.querySelectorAll('input[name="formations[]"]').forEach(function (cb) {
      cb.addEventListener('change', recalcTotal);
    });
  }

  // Calendrier page
  if (document.getElementById('calLock')) {
    checkCalendrierAccess();
  }

  // Retire les bordures rouges au focus
  document.querySelectorAll('input, select, textarea').forEach(function (el) {
    el.addEventListener('focus', function () { el.classList.remove('invalid'); });
  });
});
