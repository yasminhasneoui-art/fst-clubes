/* FST Clubs — script_index.js */

/* ── Navbar scroll ── */
window.addEventListener('scroll', function () {
  var nav = document.getElementById('navbar');
  if (nav) nav.style.boxShadow = window.scrollY > 30 ? '0 4px 24px rgba(0,0,0,0.2)' : 'none';
});

function toggleMenu() {
  var m = document.getElementById('mobileMenu');
  if (m) m.classList.toggle('open');
}

/* ── Smooth scroll ── */
document.querySelectorAll('a[href^="#"]').forEach(function (link) {
  link.addEventListener('click', function (e) {
    var id = this.getAttribute('href');
    if (id === '#') return;
    var target = document.querySelector(id);
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

/* ── Données des clubs (président, VP, trésorier, secrétaire) ── */
var clubData = {
  ieee: {
    name: 'IEEE FST Tunis', color: '#003366', icon: '💻', founded: '2008',
    fee: '40 DT / an', meeting: 'Mardi 15h – 17h · Labo B-204',
    email: 'ieee@fst.utm.tn',
    desc: "Section estudiantine de l'IEEE à la FST. Projets IoT, IA, robotique, hackathons et conférences avec des ingénieurs du monde entier.",
    bureau: [
      { role: 'Président',      nom: 'Khaled Mansour',    couleur: '#003366' },
      { role: 'Vice-Président', nom: 'Sarra Belhaj',       couleur: '#1a5298' },
      { role: 'Trésorier',      nom: 'Mehdi Chaabane',     couleur: '#2d7dd2' },
      { role: 'Secrétaire',     nom: 'Amira Trabelsi',     couleur: '#4a90d9' }
    ],
    activities: ['Hackathons IEEE nationaux', 'Ateliers Arduino, Raspberry Pi, IoT', 'Conférences ingénieurs & chercheurs', 'Formations certifiées IEEE', 'Projets open source', 'Participation IEEE Xtreme'],
    formations: ["Introduction à l'IoT (80 DT)", 'Machine Learning avec Python (120 DT)'],
    recrutement: '5 Nov 2024, 14h00 – 17h00 · Labo B-204'
  },
  enactus: {
    name: 'Enactus FST', color: '#004B28', icon: '🚀', founded: '2012',
    fee: '30 DT / an', meeting: 'Jeudi 14h – 16h · Salle R-01',
    email: 'enactus@fst.utm.tn',
    desc: "Club d'entrepreneuriat à impact social affilié à Enactus International. Nos membres créent des projets qui améliorent des vies dans les communautés locales.",
    bureau: [
      { role: 'Présidente',     nom: 'Lina Chaabane',      couleur: '#004B28' },
      { role: 'Vice-Président', nom: 'Adem Saidi',          couleur: '#006b3a' },
      { role: 'Trésorière',     nom: 'Yosra Hamdi',         couleur: '#008f4e' },
      { role: 'Secrétaire',     nom: 'Rania Boughanmi',     couleur: '#00a85a' }
    ],
    activities: ['Projets à impact social', 'Compétition nationale Enactus Tunisia', 'Ateliers design thinking', 'Pitch & storytelling', 'Partenariats ONG & entreprises', 'Programme de mentorat'],
    formations: ['Leadership & Gestion de projet (60 DT)'],
    recrutement: '6 Nov 2024, 10h00 – 12h00 · Salle R-01'
  },
  chess: {
    name: 'Club Échecs FST', color: '#1a1a1a', icon: '♟', founded: '2015',
    fee: '15 DT / an', meeting: 'Lundi 16h – 18h · Salle polyvalente C-05',
    email: 'echecs@fst.utm.tn',
    desc: "Club ouvert à tous les niveaux. Parties hebdomadaires, tournois inter-universités et séances d'analyse stratégique.",
    bureau: [
      { role: 'Président',      nom: 'Firas Hamdi',         couleur: '#1a1a1a' },
      { role: 'Vice-Président', nom: 'Tarek Jebali',         couleur: '#333' },
      { role: 'Trésorier',      nom: 'Haythem Saidani',     couleur: '#555' },
      { role: 'Secrétaire',     nom: 'Nour Mrad',            couleur: '#777' }
    ],
    activities: ['Parties libres hebdomadaires', 'Cours débutants & intermédiaires', 'Tournois mensuels internes', 'Championnat inter-universités', 'Analyse de parties célèbres', 'Olympiades universitaires'],
    formations: [],
    recrutement: '4 Nov 2024, 16h00 – 18h00 · Salle C-05'
  },
  astro: {
    name: 'Club Astronomie FST', color: '#0a0a2e', icon: '🔭', founded: '2010',
    fee: '20 DT / an', meeting: 'Mercredi 17h – 19h · Salle D-108',
    email: 'astronomie@fst.utm.tn',
    desc: "Espace pour les passionnés du ciel. Soirées d'observation, conférences et collaboration avec l'Observatoire de Tunis.",
    bureau: [
      { role: 'Présidente',     nom: 'Mariem Riahi',        couleur: '#0a0a2e' },
      { role: 'Vice-Président', nom: 'Nabil Dridi',          couleur: '#0f1050' },
      { role: 'Trésorière',     nom: 'Ines Rekik',           couleur: '#1a1a70' },
      { role: 'Secrétaire',     nom: 'Seif Mbarki',          couleur: '#252590' }
    ],
    activities: ["Soirées d'observation au télescope", 'Conférences astrophysique', 'Nuit des Étoiles annuelle', 'Ateliers astrophotographie', "Collaboration Observatoire de Tunis", "Suivi d'astéroïdes"],
    formations: ['Astrophysique pour débutants (40 DT)'],
    recrutement: '6 Nov 2024, 17h00 – 19h00 · Amphithéâtre 2'
  },
  securinettes: {
    name: 'Securinettes FST', color: '#3a0a0a', icon: '🛡️', founded: '2011',
    fee: '25 DT / an', meeting: 'Vendredi 14h – 17h · Labo B-204',
    email: 'securinettes@fst.utm.tn',
    desc: "Club de cybersécurité affilié au réseau Securinettes Tunisia. CTF, pentesting, forensics et cryptographie.",
    bureau: [
      { role: 'Président',      nom: 'Oussama Bouali',      couleur: '#3a0a0a' },
      { role: 'Vice-Présidente',nom: 'Rim Bannour',          couleur: '#5a1515' },
      { role: 'Trésorier',      nom: 'Yassine Turki',        couleur: '#7a2020' },
      { role: 'Secrétaire',     nom: 'Sirine Gharbi',        couleur: '#9a2b2b' }
    ],
    activities: ['Compétitions CTF hebdomadaires', 'Ateliers pentesting web & réseau', 'Forensics & analyse malware', 'Veille cybersécurité', 'Compétitions nationales', 'SecurFST CTF annuel'],
    formations: ['Ethical Hacking & CTF (100 DT)', 'Forensics & Défense numérique (90 DT)'],
    recrutement: '7 Nov 2024, 14h00 – 17h00 · Labo B-204'
  },
  robotique: {
    name: 'Club Robotique FST', color: '#1a3a5c', icon: '🤖', founded: '2016',
    fee: '35 DT / an', meeting: 'Mardi 09h – 12h · Atelier Mécatronique A-08',
    email: 'robotique@fst.utm.tn',
    desc: "Conception et programmation de robots autonomes. Compétitions nationales de robotique et ateliers mécatronique.",
    bureau: [
      { role: 'Président',      nom: 'Aziz Ouertani',       couleur: '#1a3a5c' },
      { role: 'Vice-Présidente',nom: 'Leila Kacem',          couleur: '#1e4d7b' },
      { role: 'Trésorier',      nom: 'Iheb Sassi',           couleur: '#2260a0' },
      { role: 'Secrétaire',     nom: 'Chaima Ayari',         couleur: '#2673c0' }
    ],
    activities: ['Conception robots autonomes', 'Programmation Arduino & ROS', 'Compétitions nationales', 'Ateliers mécatronique', 'Projets IoT embarqué', 'Partenariats industrie'],
    formations: [],
    recrutement: '5 Nov 2024, 09h00 – 12h00 · Atelier A-08'
  },
  media: {
    name: 'Club Média & Créativité', color: '#5b21b6', icon: '🎬', founded: '2017',
    fee: '20 DT / an', meeting: 'Jeudi 16h – 18h · Studio Médias M-01',
    email: 'media@fst.utm.tn',
    desc: "Photographie, montage vidéo, journalisme scientifique et communication digitale au service de la FST.",
    bureau: [
      { role: 'Présidente',     nom: 'Salma Ezzine',        couleur: '#5b21b6' },
      { role: 'Vice-Président', nom: 'Walid Bouaziz',        couleur: '#6d28d9' },
      { role: 'Trésorier',      nom: 'Malek Chebbi',         couleur: '#7c3aed' },
      { role: 'Secrétaire',     nom: 'Dorra Mejri',          couleur: '#8b5cf6' }
    ],
    activities: ['Reportages photo & vidéo FST', 'Podcast scientifique mensuel', 'Formation Lightroom & Premiere', 'Création contenu réseaux sociaux', 'Couverture événements clubs', 'Expo photo annuelle'],
    formations: [],
    recrutement: '7 Nov 2024, 15h00 – 17h00 · Studio M-01'
  },
  green: {
    name: 'Green Campus FST', color: '#14532d', icon: '🌱', founded: '2018',
    fee: '15 DT / an', meeting: 'Mercredi 14h – 16h · Espace Vert FST',
    email: 'green@fst.utm.tn',
    desc: "Actions écologiques concrètes sur le campus : tri sélectif, plantations, sensibilisation et projets de développement durable.",
    bureau: [
      { role: 'Présidente',     nom: 'Nadia Khlif',         couleur: '#14532d' },
      { role: 'Vice-Président', nom: 'Sami Gargouri',        couleur: '#166534' },
      { role: 'Trésorière',     nom: 'Emna Baccouche',       couleur: '#15803d' },
      { role: 'Secrétaire',     nom: 'Ayoub Lahmar',         couleur: '#16a34a' }
    ],
    activities: ['Campagnes de nettoyage campus', 'Plantation arbres & jardinage', 'Sensibilisation tri sélectif', 'Ateliers compostage', 'Partenariats communes', 'Journées mondiales environnement'],
    formations: [],
    recrutement: '8 Nov 2024, 10h00 – 12h00 · Espace Vert FST'
  }
};

/* ── Ouvrir modal club ── */
function openModal(id) {
  var c = clubData[id];
  if (!c) return;

  /* Bureau HTML */
  var bureauHtml = c.bureau.map(function (m) {
    var initiales = m.nom.split(' ').map(function (w) { return w[0]; }).join('').slice(0,2);
    return '<div class="team-card">' +
      '<div class="team-avatar" style="background:' + m.couleur + '">' + initiales + '</div>' +
      '<div class="team-card-info"><small>' + m.role + '</small><strong>' + m.nom + '</strong></div>' +
      '</div>';
  }).join('');

  /* Activités HTML */
  var activitesHtml = c.activities.map(function (a) {
    return '<li style="font-size:13px;color:#4a4a5a;padding:4px 0;display:flex;gap:8px;align-items:flex-start"><span style="color:#d4a017;flex-shrink:0">&#9658;</span>' + a + '</li>';
  }).join('');

  /* Formations HTML */
  var formationsHtml = '';
  if (c.formations.length > 0) {
    formationsHtml = '<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Formations certifiees</p><div style="margin-bottom:1.25rem">' +
      c.formations.map(function (f) { return '<span style="display:inline-block;background:#FAEEDA;color:#633806;font-size:12px;padding:4px 10px;border-radius:20px;margin:3px">' + f + '</span>'; }).join('') + '</div>';
  }

  document.getElementById('modal-content').innerHTML =
    '<div style="background:linear-gradient(135deg,' + c.color + ',' + c.color + 'cc);padding:2rem;border-radius:16px 16px 0 0;display:flex;align-items:center;gap:1rem">' +
      '<div style="font-size:42px">' + c.icon + '</div>' +
      '<div><h2 style="color:#fff;font-size:1.4rem">' + c.name + '</h2>' +
      '<p style="color:rgba(255,255,255,0.65);font-size:13px">Fonde en ' + c.founded + ' &nbsp;·&nbsp; <a href="mailto:' + c.email + '" style="color:#d4a017">' + c.email + '</a></p></div>' +
    '</div>' +
    '<div style="padding:1.5rem">' +
      '<p style="font-size:14px;color:#4a4a5a;line-height:1.7;margin-bottom:1.5rem">' + c.desc + '</p>' +

      '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:1.5rem">' +
        '<div style="background:#f5f5f0;border-radius:8px;padding:10px 12px"><p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em">Reunions</p><p style="font-size:13px;font-weight:500">' + c.meeting + '</p></div>' +
        '<div style="background:#f5f5f0;border-radius:8px;padding:10px 12px"><p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em">Cotisation</p><p style="font-size:13px;font-weight:500;color:#d4a017">' + c.fee + '</p></div>' +
      '</div>' +

      '<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Bureau du club</p>' +
      '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:1.5rem">' + bureauHtml + '</div>' +

      '<p style="font-size:11px;color:#9a9a90;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Activites</p>' +
      '<ul style="list-style:none;margin-bottom:1.25rem">' + activitesHtml + '</ul>' +

      formationsHtml +

      '<div style="background:#fff9ec;border:1px solid rgba(212,160,23,0.3);border-radius:10px;padding:10px 14px;font-size:13px;color:#633806;margin-bottom:1.5rem">' +
        '&#128197; <strong>Recrutement :</strong> ' + c.recrutement +
      '</div>' +

      '<a href="inscription.html?club=' + id + '" style="display:block;text-align:center;padding:13px;background:#d4a017;color:#0a1628;border-radius:10px;font-weight:600;font-size:14px;text-decoration:none">' +
        'Rejoindre ' + c.name + ' &#8594;' +
      '</a>' +
    '</div>';

  document.getElementById('modal').classList.add('open');
}

function closeModal(e) {
  if (e.target === document.getElementById('modal')) closeModalBtn();
}

function closeModalBtn() {
  document.getElementById('modal').classList.remove('open');
}

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeModalBtn();
});
