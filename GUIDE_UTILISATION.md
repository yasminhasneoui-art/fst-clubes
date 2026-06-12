# Guide d'utilisation — FST Clubs

Ce guide explique comment **installer**, **configurer** et **utiliser** la plateforme FST Clubs en local (XAMPP / WAMP / MAMP / LAMP).

---

## 1. Prérequis

- Serveur web avec **PHP 8.0+** (Apache ou Nginx)
- **MySQL / MariaDB**
- Un environnement tout-en-un recommandé : **XAMPP**, **WAMP** ou **MAMP**

---

## 2. Installation

### Étape 1 — Copier les fichiers
Placez tous les fichiers du projet dans le dossier racine de votre serveur web, par exemple :

```
C:\xampp\htdocs\fst-clubs\        (Windows - XAMPP)
/Applications/MAMP/htdocs/fst-clubs/   (Mac - MAMP)
/var/www/html/fst-clubs/          (Linux)
```

### Étape 2 — Créer la base de données
1. Démarrez Apache et MySQL depuis le panneau de contrôle (XAMPP/WAMP/MAMP).
2. Ouvrez **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Créez une nouvelle base nommée `fst_clubs` (encodage `utf8mb4_unicode_ci`).
4. Importez le fichier **`database.sql`** :
   - Onglet *Importer* → sélectionner `database.sql` → *Exécuter*.
   - Cela crée les tables `inscriptions`, `creneaux_recrutement`, `activite_log` et insère les créneaux de recrutement par défaut.

Ou via terminal :
```bash
mysql -u root -p fst_clubs < database.sql
```

### Étape 3 — Configurer la connexion (`config.php`)

Ouvrez `config.php` et adaptez les constantes selon votre environnement :

```php
define('DB_HOST',    'localhost');
define('DB_NAME',    'fst_clubs');
define('DB_USER',    'root');
define('DB_PASS',    'admin1234');   // mot de passe MySQL
define('DB_PORT',    '3307');        // port MySQL (souvent 3306)
```

⚠️ **Important** :
- Le port par défaut de MySQL est **3306**. Si vous utilisez XAMPP/WAMP par défaut, changez `DB_PORT` en `3306` (sauf configuration spécifique).
- Adaptez `DB_USER` / `DB_PASS` selon votre installation (souvent `root` sans mot de passe sur XAMPP).

Vous pouvez aussi modifier dans `config.php` :
- `ADMIN_LOGIN` / `ADMIN_PASSWORD` → identifiants d'accès au back-office
- `CLUB_PRICES`, `FORMATION_PRICES` → tarifs des clubs et formations
- `APP_URL` → URL de base de votre site

### Étape 4 — Lancer le site

Dans le navigateur, accédez à :
```
http://localhost/fst-clubs/index.html
```

---

## 3. Parcours utilisateur (étudiant)

1. **Page d'accueil (`index.html`)**
   - Cliquer sur un club pour ouvrir sa fiche détaillée (modal).
   - Cliquer sur *« Rejoindre [Club] »* pour aller au formulaire d'inscription pré-rempli avec le club choisi.

2. **Formulaire d'inscription (`inscription.html`)**
   - **Étape 1** : informations personnelles, type d'adhésion, compétences, formations souhaitées.
   - **Étape 2** : choix d'un créneau de recrutement (chargé dynamiquement via `get_creneaux.php`).
   - **Étape 3** : récapitulatif et choix du mode de paiement → soumission vers `inscription.php`.

3. **Traitement (`inscription.php`)**
   - Valide les données, calcule le montant total, vérifie les doublons (matricule + club).
   - Enregistre le dossier en base et réserve le créneau choisi.
   - Redirige vers `paiement.php`.

4. **Paiement (`paiement.php`)**
   - Affiche le récapitulatif du montant et permet de choisir/confirmer le mode de paiement.
   - Une fois confirmé → redirection vers `succes.php`.

5. **Confirmation (`succes.php`)**
   - Affiche la référence du dossier, le récapitulatif complet et les détails du rendez-vous de recrutement.
   - La référence est sauvegardée pour débloquer l'accès au calendrier (`calendrier.html`).

---

## 4. Accès administrateur

1. Aller sur :
```
http://localhost/fst-clubs/admin.php
```
2. Se connecter avec les identifiants définis dans `config.php` :
   - **Login** : valeur de `ADMIN_LOGIN` (par défaut `admin`)
   - **Mot de passe** : valeur de `ADMIN_PASSWORD` (par défaut `fst@2024`)

3. Depuis le tableau de bord :
   - Consulter la liste de tous les dossiers d'inscription.
   - Cliquer sur un dossier pour ouvrir son détail (`admin_detail.php` via AJAX).
   - Changer le statut (**Valider**, **Refuser**, **Liste d'attente**).
   - Confirmer un paiement manuellement.
   - Ajouter une note interne au dossier.

> 🔒 **Recommandation** : changez `ADMIN_PASSWORD` dans `config.php` avant toute mise en production, et envisagez un hachage plus robuste (le système actuel compare un hash SHA-256 stocké dans un cookie).

---

## 5. Gestion des créneaux de recrutement

- Chaque club dispose de créneaux définis dans `database.sql` (table `creneaux_recrutement`).
- Un créneau ne peut être réservé que par **un seul étudiant**.
- Pour ajouter/modifier des créneaux, éditez directement la table via phpMyAdmin ou ajoutez des lignes `INSERT INTO creneaux_recrutement (...) VALUES (...)`.

---

## 6. Personnalisation rapide

| Élément à modifier            | Fichier(s) concerné(s)                              |
|----------------------------------|--------------------------------------------------------|
| Tarifs des clubs / formations       | `config.php` (`CLUB_PRICES`, `FORMATION_PRICES`)          |
| Libellés des clubs / formations       | `config.php` (`CLUB_LABELS`, `FORMATION_LABELS`)            |
| Bureau, descriptions, activités des clubs | `script_index.js` (objet `clubData`)                       |
| Couleurs, polices, mise en page          | `style.css`                                                  |
| Logo                                       | `FST.png`                                                      |
| Identifiants admin                          | `config.php` (`ADMIN_LOGIN`, `ADMIN_PASSWORD`)                   |

---

## 7. Dépannage (FAQ)

**❓ Erreur "Connexion DB impossible"**
→ Vérifiez `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` dans `config.php` et que le service MySQL est démarré.

**❓ Les créneaux ne s'affichent pas à l'étape 2 du formulaire**
→ Vérifiez que `get_creneaux.php` est accessible et que la base contient bien des créneaux pour le club sélectionné (table `creneaux_recrutement`).

**❓ Impossible de se connecter au back-office**
→ Vérifiez `ADMIN_LOGIN` / `ADMIN_PASSWORD` dans `config.php`, et que les cookies sont activés dans le navigateur.

**❓ "Vous êtes déjà inscrit à ce club"**
→ Le système empêche un même matricule de s'inscrire deux fois au même club. Supprimez la ligne correspondante dans la table `inscriptions` pour réinitialiser (en local/test uniquement).

---

## 8. Bonnes pratiques avant mise en ligne

- Changer tous les mots de passe par défaut (`config.php`).
- Activer HTTPS.
- Limiter les accès à `config.php` et au dossier de la base de données.
- Sauvegarder régulièrement la base de données.
- Désactiver l'affichage des erreurs PHP (`display_errors = Off`) en production.
