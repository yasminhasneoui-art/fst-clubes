<?php
/* ════════════════════════════════════════
   FST CLUBS — CONFIG.PHP  v3.0
════════════════════════════════════════ */

define('DB_HOST',    'localhost');
define('DB_NAME',    'fst_clubs');
define('DB_USER',    'root');
define('DB_PASS',    'admin1234');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    '3307');

define('ADMIN_EMAIL',    'clubs@fst.utm.tn');
define('FROM_EMAIL',     'noreply@fst.utm.tn');
define('FROM_NAME',      'FST Clubs Tunis');
define('ADMIN_LOGIN',    'admin');
define('ADMIN_PASSWORD', 'fst@2024');
define('APP_NAME',       'FST Clubs');
define('APP_URL',        'http://localhost/fst-clubs');
define('ANNEE',          '2024/2025');

define('CLUB_PRICES', [
    'ieee'=>40,'enactus'=>30,'chess'=>15,'astro'=>20,
    'securinettes'=>25,'robotique'=>35,'media'=>20,'green'=>15,
]);

define('CLUB_LABELS', [
    'ieee'=>'IEEE FST Tunis','enactus'=>'Enactus FST','chess'=>'Club Échecs FST',
    'astro'=>'Club Astronomie FST','securinettes'=>'Securinettes FST',
    'robotique'=>'Club Robotique FST','media'=>'Club Média & Créativité','green'=>'Green Campus FST',
]);

define('FORMATION_PRICES', [
    'iot'=>80,'ml'=>120,'leadership'=>60,'ctf'=>100,'forensics'=>90,'astrophysique'=>40,
]);

define('FORMATION_LABELS', [
    'iot'=>"Introduction à l'IoT",'ml'=>'Machine Learning avec Python',
    'leadership'=>'Leadership & Gestion de projet','ctf'=>'Ethical Hacking & CTF',
    'forensics'=>'Forensics & Défense numérique','astrophysique'=>'Astrophysique pour débutants',
]);

define('ADHESION_LABELS', ['membre'=>'Membre actif','bureau'=>'Candidature au bureau','formation'=>'Formation uniquement']);
define('PM_LABELS', ['carte'=>'Carte bancaire','virement'=>'Virement bancaire','especes'=>'Paiement en espèces']);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
    } catch (PDOException $e) {
        die('<p style="color:red;padding:2rem">Connexion DB impossible.</p>');
    }
    return $pdo;
}

function sanitize(string $v): string {
    return htmlspecialchars(trim(strip_tags($v)), ENT_QUOTES, 'UTF-8');
}
function isValidEmail(string $e): bool { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function isValidPhone(string $p): bool {
    return (bool) preg_match('/^(\+216)?[2-9]\d{7}$/', preg_replace('/[\s\-()]/','',$p));
}
function isValidMatricule(string $m): bool { return (bool) preg_match('/^\d{8}$/', $m); }
function isValidCin(string $c): bool       { return (bool) preg_match('/^\d{8}$/', $c); }
function generateRef(): string { return 'INS-'.date('Y').'-'.strtoupper(substr(uniqid('',true),-8)); }

/* Créneaux libres pour un club */
function getCreneauxLibres(string $club): array {
    $st = getDB()->prepare(
        "SELECT id, date_cr, heure_debut, heure_fin, lieu
         FROM creneaux_recrutement
         WHERE club=:club AND inscription_id IS NULL
         ORDER BY date_cr, heure_debut"
    );
    $st->execute([':club'=>$club]);
    return $st->fetchAll();
}

/* Réserver un créneau - retourne false si déjà pris */
function reserverCreneau(int $creneauId, int $inscriptionId): bool {
    $pdo = getDB();
    $check = $pdo->prepare("SELECT id FROM creneaux_recrutement WHERE id=:id AND inscription_id IS NULL");
    $check->execute([':id'=>$creneauId]);
    if (!$check->fetch()) return false;
    $pdo->prepare("UPDATE creneaux_recrutement SET inscription_id=:iid WHERE id=:id AND inscription_id IS NULL")
        ->execute([':iid'=>$inscriptionId,':id'=>$creneauId]);
    return true;
}
