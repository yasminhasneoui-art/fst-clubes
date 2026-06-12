-- ═══════════════════════════════════════════════════════════
--  FST CLUBS — DATABASE.SQL  v3.0
--  Créer : CREATE DATABASE fst_clubs CHARACTER SET utf8mb4;
--  Importer : mysql -u root -p fst_clubs < database.sql
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `fst_clubs`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fst_clubs`;

-- ─── TABLE : inscriptions ───────────────────────────────────
DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE `inscriptions` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ref`                 VARCHAR(30)  NOT NULL UNIQUE,
  `club`                VARCHAR(30)  NOT NULL,
  `adhesion`            VARCHAR(20)  NOT NULL DEFAULT 'membre',
  `creneau_id`          INT UNSIGNED DEFAULT NULL,        -- FK vers creneaux
  `prenom`              VARCHAR(80)  NOT NULL,
  `nom`                 VARCHAR(80)  NOT NULL,
  `matricule`           VARCHAR(10)  NOT NULL,
  `cin`                 VARCHAR(10),
  `email`               VARCHAR(150) NOT NULL,
  `telephone`           VARCHAR(20),
  `filiere`             VARCHAR(100),
  `niveau`              VARCHAR(50),
  `disponibilite`       VARCHAR(80),
  `experience`          VARCHAR(255),
  `motivation`          TEXT,
  `competences`         VARCHAR(255),
  `formations`          VARCHAR(255),
  `mode_paiement`       VARCHAR(20),
  `cotisation`          DECIMAL(8,2) DEFAULT 0,
  `montant_formations`  DECIMAL(8,2) DEFAULT 0,
  `frais_admin`         DECIMAL(8,2) DEFAULT 2,
  `montant_total`       DECIMAL(8,2) DEFAULT 0,
  `paiement_confirme`   TINYINT(1)   DEFAULT 0,
  `date_paiement`       DATE         DEFAULT NULL,
  `newsletter`          TINYINT(1)   DEFAULT 0,
  `statut`              ENUM('en_attente','valide','refuse','liste_attente') DEFAULT 'en_attente',
  `commentaire_admin`   TEXT,
  `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLE : creneaux_recrutement ────────────────────────────
-- Chaque créneau ne peut être réservé que par UN seul étudiant par club
DROP TABLE IF EXISTS `creneaux_recrutement`;
CREATE TABLE `creneaux_recrutement` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club`          VARCHAR(30)  NOT NULL,
  `date_cr`       DATE         NOT NULL,
  `heure_debut`   VARCHAR(10)  NOT NULL,
  `heure_fin`     VARCHAR(10)  NOT NULL,
  `lieu`          VARCHAR(100),
  `inscription_id` INT UNSIGNED DEFAULT NULL,   -- NULL = libre
  UNIQUE KEY `uk_club_heure` (`club`, `date_cr`, `heure_debut`),
  UNIQUE KEY `uk_inscription` (`inscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLE : activite_log ────────────────────────────────────
DROP TABLE IF EXISTS `activite_log`;
CREATE TABLE `activite_log` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `inscription_id`  INT UNSIGNED,
  `action`          VARCHAR(50),
  `detail`          VARCHAR(500),
  `ip`              VARCHAR(45),
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ─── CRÉNEAUX DE RECRUTEMENT PAR CLUB ───────────────────────
-- Chaque créneau = 30 min, 1 étudiant max par créneau

-- IEEE (Mardi 5 Nov 2024, 14h-17h)
INSERT INTO `creneaux_recrutement` (`club`,`date_cr`,`heure_debut`,`heure_fin`,`lieu`) VALUES
('ieee','2024-11-05','14:00','14:30','Labo B-204'),
('ieee','2024-11-05','14:30','15:00','Labo B-204'),
('ieee','2024-11-05','15:00','15:30','Labo B-204'),
('ieee','2024-11-05','15:30','16:00','Labo B-204'),
('ieee','2024-11-05','16:00','16:30','Labo B-204'),
('ieee','2024-11-05','16:30','17:00','Labo B-204'),

-- Enactus (Mercredi 6 Nov 2024, 10h-12h)
('enactus','2024-11-06','10:00','10:30','Salle R-01'),
('enactus','2024-11-06','10:30','11:00','Salle R-01'),
('enactus','2024-11-06','11:00','11:30','Salle R-01'),
('enactus','2024-11-06','11:30','12:00','Salle R-01'),

-- Chess (Lundi 4 Nov 2024, 16h-18h)
('chess','2024-11-04','16:00','16:30','Salle polyvalente C-05'),
('chess','2024-11-04','16:30','17:00','Salle polyvalente C-05'),
('chess','2024-11-04','17:00','17:30','Salle polyvalente C-05'),
('chess','2024-11-04','17:30','18:00','Salle polyvalente C-05'),

-- Astro (Mercredi 6 Nov 2024, 17h-19h)
('astro','2024-11-06','17:00','17:30','Amphithéâtre 2'),
('astro','2024-11-06','17:30','18:00','Amphithéâtre 2'),
('astro','2024-11-06','18:00','18:30','Amphithéâtre 2'),
('astro','2024-11-06','18:30','19:00','Amphithéâtre 2'),

-- Securinettes (Jeudi 7 Nov 2024, 14h-17h)
('securinettes','2024-11-07','14:00','14:30','Labo B-204'),
('securinettes','2024-11-07','14:30','15:00','Labo B-204'),
('securinettes','2024-11-07','15:00','15:30','Labo B-204'),
('securinettes','2024-11-07','15:30','16:00','Labo B-204'),
('securinettes','2024-11-07','16:00','16:30','Labo B-204'),
('securinettes','2024-11-07','16:30','17:00','Labo B-204'),

-- Robotique (Mardi 5 Nov 2024, 09h-12h)
('robotique','2024-11-05','09:00','09:30','Atelier Mécatronique A-08'),
('robotique','2024-11-05','09:30','10:00','Atelier Mécatronique A-08'),
('robotique','2024-11-05','10:00','10:30','Atelier Mécatronique A-08'),
('robotique','2024-11-05','10:30','11:00','Atelier Mécatronique A-08'),
('robotique','2024-11-05','11:00','11:30','Atelier Mécatronique A-08'),
('robotique','2024-11-05','11:30','12:00','Atelier Mécatronique A-08'),

-- Media (Jeudi 7 Nov 2024, 15h-17h)
('media','2024-11-07','15:00','15:30','Studio Médias M-01'),
('media','2024-11-07','15:30','16:00','Studio Médias M-01'),
('media','2024-11-07','16:00','16:30','Studio Médias M-01'),
('media','2024-11-07','16:30','17:00','Studio Médias M-01'),

-- Green (Vendredi 8 Nov 2024, 10h-12h)
('green','2024-11-08','10:00','10:30','Espace Vert FST'),
('green','2024-11-08','10:30','11:00','Espace Vert FST'),
('green','2024-11-08','11:00','11:30','Espace Vert FST'),
('green','2024-11-08','11:30','12:00','Espace Vert FST');
