#  FST Clubs — Plateforme d'inscription aux clubs étudiants

Plateforme web permettant aux étudiants de la **Faculté des Sciences de Tunis (FST)** de découvrir les clubs étudiants, s'inscrire en ligne, choisir un créneau d'entretien de recrutement, payer leur cotisation et suivre leur dossier. Inclut un back-office d'administration pour la gestion des inscriptions.

---

##  Structure du projet
## fst-clubs/

## index.html              # Page d'accueil — présentation des clubs, formations,navbar 
# inscription.html     # Formulaire d'inscription (étapes : infos / créneau / paiement)
# calendrier.html        # Calendrier des événements de l'année universitaire

#inscription.php          # Traitement du formulaire d'inscription (POST) + insertion BDD
# paiement.php               # Page de paiement (après inscription) + confirmation
# succes.php                 # Page de confirmation finale (récapitulatif du dossier)
# get_creneaux.php             # Endpoint AJAX (JSON) — liste des créneaux disponibles

# admin.php                     # Back-office : tableau de bord, liste des dossiers, login admin
# admin_detail.php               # Vue détaillée (AJAX) d'un dossier d'inscription
# config.php                      # Configuration (BDD, constantes, fonctions utilitaires)
 # database.sql                   # Script SQL de création de la base de données

# style.css                        # Feuille de style globale du site
# script.js                          # Script JS commun (navbar, étapes, paiement, calendrier)
# script_index.js                   # Script JS de la page d'accueil (modals des clubs)
# script_inscription.js            # Script JS du formulaire d'inscription (étapes, créneaux)

##  Technologies utilisées

| Couche          | Technologie                          |
|------------------|----------------------------------------|
| Frontend          | HTML5, CSS3, JavaScript (vanilla)        |
| Backend            | PHP 8+ (PDO)                              |
| Base de données     | MySQL / MariaDB (utf8mb4)                  |
| Polices              | Google Fonts (Playfair Display, DM Sans)    |


## 🗄️ Base de données

Trois tables principales (voir `database.sql`) :

- **`inscriptions`** — toutes les données des candidatures (informations personnelles, club, paiement, statut du dossier…)
- **`creneaux_recrutement`** — créneaux d'entretien par club (un créneau = un seul étudiant)
- **`activite_log`** — journal des actions (optionnel / traçabilité)

---

##  Fonctionnalités principales

### Côté étudiant
- Découverte des 8 clubs (IEEE, Enactus, Échecs, Astronomie, Securinettes, Robotique, Média, Green Campus)
- Présentation des formations certifiées proposées par chaque club
- Inscription en ligne en plusieurs étapes (infos personnelles → créneau d'entretien → paiement)
- Réservation d'un créneau de recrutement (1 créneau = 1 étudiant)
- Choix du mode de paiement (carte, virement, espèces)
- Page de confirmation avec récapitulatif et référence de dossier
- Calendrier des événements de l'année

### Côté administration (`admin.php`)
- Connexion sécurisée (login + mot de passe)
- Liste et filtrage des dossiers d'inscription
- Détail complet de chaque dossier (`admin_detail.php`)
- Changement de statut : Validé / Refusé / Liste d'attente / En attente
- Confirmation manuelle des paiements
- Ajout de notes internes par dossier

---

## 👉 Pour le mode d'emploi détaillé (installation, configuration, lancement)

Voir le fichier **`GUIDE_UTILISATION.md`**.

---

## © Crédits

Projet réalisé pour la **Faculté des Sciences de Tunis (FST) — Université de Tunis El Manar**, dans le cadre de la gestion numérique des clubs étudiants (année 2025/2026).
