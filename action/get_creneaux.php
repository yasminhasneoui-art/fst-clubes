<?php
/* FST Clubs — get_creneaux.php
   Retourne les créneaux de recrutement d'un club en JSON */
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
// Vérifier que GET existe
$club = isset($_GET['club']) ? sanitize($_GET['club']) : '';
// Vérification du club
if (empty($club) || !array_key_exists($club, CLUB_LABELS)) {
    echo json_encode(['error' => 'Club invalide']);
    exit;
}
try {
    $pdo = getDB();
    $st = $pdo->prepare("
        SELECT id, date_cr, heure_debut, heure_fin, lieu,
               (inscription_id IS NOT NULL) AS pris
        FROM creneaux_recrutement
        WHERE club = :club
        ORDER BY date_cr, heure_debut
    ");
    $st->execute([':club' => $club]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    // Initialisation
    $date_label = '';
    $lieu = '';

    if (!empty($rows)) {
        // ⚠️ Correction : gérer les erreurs de date
        $timestamp = strtotime($rows[0]['date_cr']);
        if ($timestamp !== false) {
            $date_label = date('l d F Y', $timestamp);

            // Traduction des jours
            $jours = [
                'Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi',
                'Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche'
            ];
            $date_label = strtr($date_label, $jours);

            // Traduction des mois
            $mois = [
                'January'=>'Janvier','February'=>'Février','March'=>'Mars','April'=>'Avril',
                'May'=>'Mai','June'=>'Juin','July'=>'Juillet','August'=>'Août',
                'September'=>'Septembre','October'=>'Octobre','November'=>'Novembre','December'=>'Décembre'
            ];
            $date_label = strtr($date_label, $mois);
        }

        $lieu = $rows[0]['lieu'] ?? '';
    }

    echo json_encode([
        'club'       => $club,
        'date_label' => $date_label,
        'lieu'       => $lieu,
        'creneaux'   => $rows,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur']);
}