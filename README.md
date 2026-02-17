# Gestion de dons pour sinistres (FlightPHP + MySQL)

## Fonctionnalites
- Distribution de dons selon ville, type et sous-type.
- Verification du stock avant distribution.
- Mise a jour automatique du besoin (`ouvert`, `partiel`, `satisfait`).
- Achat de nature/materiaux avec le stock argent.
- Vente locale de produits quand aucun besoin ouvert n'existe sur le sous-type.
- Recapitulatif global des montants avec actualisation Ajax.
- Tableau de bord:
  - besoins par ville (statut et type)
  - historique des distributions par ville
  - historique et montant des achats par ville

## Structure principale
- `database/schema.sql` : schema MySQL + donnees fictives
- `app/controllers/DistributionController.php`
- `app/controllers/DashboardController.php`
- `app/controllers/AchatController.php`
- `app/controllers/RecapController.php`
- `app/controllers/VenteController.php`
- `app/repositories/DonationRepository.php`
- `app/views/distribution.php`
- `app/views/achat.php`
- `app/views/dashboard.php`
- `app/views/recapitulatif.php`
- `app/views/vente.php`

## Installation
1. Importer la base:
   - executer `database/schema.sql` dans MySQL (phpMyAdmin ou CLI)
2. Verifier la config DB:
   - fichier `app/config.php`
3. Lancer via XAMPP:
   - URL: `http://localhost/exam_S3/public/distribution`
4. Ou lancer avec serveur PHP integre:
   - `php -S localhost:8000 -t public public/router.php`
   - URL: `http://localhost:8000/distribution`

## Routes
- `GET /distribution` : formulaire de distribution
- `POST /distribution` : traitement de la distribution
- `GET /achat` : page achat (saisie + liste filtrable)
- `POST /achat` : traitement achat
- `GET /vente` : page vente locale (saisie + historique filtrable)
- `POST /vente` : traitement vente locale
- `GET /dashboard` : tableau de bord
- `GET /recapitulatif` : page recapitulatif
- `GET /api/recapitulatif` : donnees recapitulatif (Ajax)
