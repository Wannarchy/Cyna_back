# Guide d'installation — CYNA API (backend local)


---

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Récupérer le code](#2-récupérer-le-code)
3. [Installer les dépendances PHP](#3-installer-les-dépendances-php)
4. [Configurer le fichier `.env`](#4-configurer-le-fichier-env)
5. [Base de données](#5-base-de-données)
6. [Clé d'application et migrations](#6-clé-dapplication-et-migrations)
7. [Démarrer l'API](#7-démarrer-lapi)
8. [Worker de file d'attente (obligatoire pour les e-mails)](#8-worker-de-file-dattente-obligatoire-pour-les-e-mails)
9. [Vérifications](#9-vérifications)
10. [Données de test : utilisateur admin et produits](#10-données-de-test--utilisateur-admin-et-produits)
11. [Stripe en local (optionnel)](#11-stripe-en-local-optionnel)
12. [Cloudinary en local (optionnel)](#12-cloudinary-en-local-optionnel)
13. [Connexion avec le front et l'app mobile](#13-connexion-avec-le-front-et-lapp-mobile)
14. [Commandes utiles](#14-commandes-utiles)
15. [Dépannage](#15-dépannage)

---

## 1. Prérequis

### Logiciels obligatoires

| Outil | Version minimale | Vérification |
|-------|------------------|--------------|
| **PHP** | **8.4** | `php -v` |
| **Composer** | 2.x | `composer -V` |
| **Git** | 2.x | `git -v` |

### Extensions PHP requises

Vérifiez avec `php -m` que ces extensions sont activées :

- `bcmath` — calculs Stripe / commandes
- `ctype`, `mbstring`, `tokenizer`, `xml`, `fileinfo` — Laravel
- `curl`, `openssl` — HTTP / HTTPS
- `pdo_sqlite` — mode développement rapide (SQLite)
- `pdo_pgsql` — optionnel, si vous utilisez Supabase en local

**Exemple WAMP (Windows)** : PHP 8.4 dans `C:\wamp64\bin\php\php8.4.x\`, extensions décommentées dans `php.ini`.

### Logiciels optionnels

| Outil | Usage |
|-------|--------|
| **Node.js 20+** | Assets Vite (`npm run dev`) — **non requis** pour l'API JSON seule |
| **Stripe CLI** | Webhooks Stripe en local |
| **PostgreSQL / compte Supabase** | Reproduire l'environnement de production |

---

## 2. Récupérer le code

```bash
git clone <URL_DU_DEPOT_GITHUB> cyna-api
cd cyna-api
```

Si vous travaillez déjà dans le dossier du projet, placez-vous à la racine où se trouvent `artisan`, `composer.json` et `routes/api.php`.

---

## 3. Installer les dépendances PHP

```bash
composer install
```

Cette commande télécharge Laravel 13, Sanctum, Cashier (Stripe), Cloudinary, etc. dans le dossier `vendor/`.

**En cas d'erreur mémoire :**

```bash
php -d memory_limit=-1 $(which composer) install
```

Sur Windows PowerShell :

```powershell
php -d memory_limit=-1 composer.phar install
```

---

## 4. Configurer le fichier `.env`

### 4.1 Créer le fichier

```bash
cp .env.example .env
```

Windows :

```powershell
copy .env.example .env
```

### 4.2 Variables essentielles (minimum pour démarrer)

Éditez `.env` et adaptez au minimum ces lignes :

```env
APP_NAME="Cyna API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Base SQLite (voir section 5)
DB_CONNECTION=sqlite

# File d'attente en base (nécessite un worker — section 8)
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

# E-mails : écriture dans storage/logs (pas de SMTP requis en dev)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=dev@cyna.local
MAIL_FROM_NAME="${APP_NAME}"

# URL du front pour les liens dans les e-mails (adapter si Cyna_front en local)
FRONTEND_URL=http://127.0.0.1/Cyna_front/public
```

### 4.3 Variables optionnelles (fonctionnalités avancées)

| Variable | Quand la renseigner |
|----------|---------------------|
| `STRIPE_KEY` / `STRIPE_SECRET` | Paiements et abonnements (mode **test** Stripe) |
| `STRIPE_WEBHOOK_SECRET` | Webhooks Stripe locaux (Stripe CLI — section 11) |
| `CLOUDINARY_*` | Upload d'images dans le backoffice admin |
| `MAIL_MAILER=smtp` + `MAIL_HOST=...` | Envoi d'e-mails réels (Gmail, etc.) |
| `DB_*` PostgreSQL | Connexion Supabase au lieu de SQLite (section 5.2) |

> **Ne commitez jamais** le fichier `.env` (il est dans `.gitignore`).

---

## 5. Base de données

Deux modes possibles. Pour un premier démarrage, utilisez **SQLite**.

### 5.1 Mode SQLite (recommandé en local)

1. Dans `.env` :

```env
DB_CONNECTION=sqlite
# Ne pas définir DB_DATABASE : Laravel utilisera database/database.sqlite par défaut
```

2. Créez le fichier de base :

```bash
# Linux / macOS / Git Bash
touch database/database.sqlite
```

Windows PowerShell :

```powershell
New-Item -Path database\database.sqlite -ItemType File -Force
```

3. Passez à la [section 6](#6-clé-dapplication-et-migrations).

**Avantages :** aucun serveur BDD à installer, migrations rapides, identique aux tests PHPUnit.

### 5.2 Mode Supabase / PostgreSQL (proche production)

1. Créez un projet sur [Supabase](https://supabase.com) ou utilisez une instance PostgreSQL locale.

2. Dans `.env`, commentez SQLite et renseignez :

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<votre-ref-projet>
DB_PASSWORD=<votre-mot-de-passe>
DB_SSLMODE=require
```

3. Utilisez les identifiants du **pooler** Supabase (onglet *Settings → Database*).

---

## 6. Clé d'application et migrations

### 6.1 Générer la clé Laravel

```bash
php artisan key:generate
```

Cela remplit `APP_KEY` dans `.env` (chiffrement sessions, cookies).

### 6.2 Exécuter les migrations

```bash
php artisan migrate
```

Cette commande crée toutes les tables : utilisateurs, produits, commandes, abonnements, `jobs` (file d'attente), `cache`, logs d'audit, tables Cashier Stripe, etc.

**En cas d'erreur** « database file does not exist » : vérifiez que `database/database.sqlite` existe (section 5.1).



Sous Windows avec WAMP, ce n'est en général pas nécessaire.

### 6.4 Seeder

Le fichier `database/seeders/DatabaseSeeder.php` n'est **pas à jour** avec le modèle `User` (champs `prenom` / `nom`). **N'utilisez pas** `php artisan db:seed` tel quel.

Créez plutôt les comptes manuellement (section 10).

---

## 7. Démarrer l'API

### 7.1 Serveur de développement intégré

```bash
php artisan serve
```

Par défaut l'API écoute sur **http://127.0.0.1:8000**.

- Health check : http://127.0.0.1:8000/up → doit répondre `200`
- Catalogue public : http://127.0.0.1:8000/api/products

### 7.2 Tout-en-un (serveur + queue + logs)

Le projet inclut un script Composer qui lance plusieurs processus :

```bash
composer run dev
```

Cela démarre en parallèle :

- `php artisan serve` — API
- `php artisan queue:listen` — worker e-mails
- `php artisan pail` — logs en direct
- `npm run dev` — Vite (assets front Laravel, peu utilisé pour l'API seule)

**Recommandé** si vous testez inscription, OTP admin ou commandes.

---

## 8. Worker de file d'attente (obligatoire pour les e-mails)

Les notifications CYNA (`EmailVerificationNotification`, `AdminLoginOtpNotification`, confirmation de commande, etc.) implémentent `ShouldQueue`. Elles sont stockées dans la table `jobs` et **ne partent pas** tant qu'un worker ne les traite pas.

Si vous n'utilisez pas `composer run dev`, ouvrez un **second terminal** :

```bash
cd cyna-api
php artisan queue:work --sleep=3 --tries=3
```

| `MAIL_MAILER` | Comportement attendu |
|---------------|----------------------|
| `log` | Contenu des e-mails visible dans `storage/logs/laravel.log` |
| `smtp` | Envoi réel via Gmail ou autre |

Sans worker actif : inscription OK, mais **pas d'e-mail** de confirmation ni d'OTP admin.

---

## 9. Vérifications

### 9.1 Tests automatisés

```bash
php artisan test
```

Résultat attendu : **67 tests passants**, 175 assertions (~9 secondes avec SQLite mémoire).

### 9.2 Vérification manuelle rapide (curl ou Postman)

**Health check :**

```bash
curl http://127.0.0.1:8000/up
```

**Liste des produits (public) :**

```bash
curl http://127.0.0.1:8000/api/products
```

**Inscription :**

```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d "{\"prenom\":\"Jean\",\"nom\":\"Dupont\",\"email\":\"jean@test.local\",\"password\":\"Password1!\",\"password_confirmation\":\"Password1!\"}"
```

Réponse attendue : `201` + token Sanctum dans `data.token`.

**Mot de passe :** minimum 8 caractères, majuscule, minuscule, chiffre et symbole (`PasswordRules`).

---

## 10. Données de test : utilisateur admin et produits

### 10.1 Créer un administrateur

```bash
php artisan tinker
```

```php
use App\Models\User;

$admin = User::factory()->admin()->create([
    'prenom' => 'Admin',
    'nom' => 'CYNA',
    'email' => 'admin@cyna.local',
    'est_confirme' => true,
]);
// Mot de passe par défaut factory : "password"
```

Connexion admin :

1. `POST /api/auth/login` avec `admin@cyna.local` / `password`
2. Réponse `requires_otp: true` + `challenge_token`
3. Lire le code OTP dans `storage/logs/laravel.log` (si `MAIL_MAILER=log` et worker actif)
4. `POST /api/auth/verify-admin-otp` avec `challenge_token` et `code`

### 10.2 Créer des produits

Il n'y a **pas de seeder produit** dans le dépôt. Deux options :

**A. Via l'API admin** (après connexion admin + token Bearer) :

```http
POST /api/admin/products
Authorization: Bearer <token_admin>
Content-Type: application/json

{
  "name": "SOC Essentiel",
  "description": "Surveillance SOC 24/7",
  "price_monthly": 599,
  "price_yearly": 5990,
  "category_id": 1,
  "is_physical": false,
  "stock": 0
}
```

(Créez d'abord une catégorie via `POST /api/admin/categories` si la table est vide.)

**B. Synchronisation Stripe** (si clés Stripe configurées et produits en base) :

```bash
php artisan stripe:sync-products
```

Crée les produits/prix récurrents côté Stripe et enregistre les IDs en base.

---

## 11. Stripe en local (optionnel)

### 11.1 Clés de test

1. Créez un compte [Stripe](https://dashboard.stripe.com).
2. Mode **Test** activé.
3. Dans `.env` :

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
CASHIER_CURRENCY=eur
```

4. Redémarrez `php artisan serve`.

### 11.2 Webhooks locaux

L'API écoute les webhooks sur :

```
POST http://127.0.0.1:8000/stripe/webhook
```

Avec [Stripe CLI](https://stripe.com/docs/stripe-cli) :

```bash
stripe login
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

Copiez le secret affiché (`whsec_...`) dans `.env` :

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Sans webhook, les commandes initiées depuis le front peuvent rester en attente de confirmation Stripe.

### 11.3 Cartes de test Stripe

| Numéro | Résultat |
|--------|----------|
| `4242 4242 4242 4242` | Paiement réussi |
| `4000 0000 0000 0002` | Carte refusée |

Date future quelconque, CVC 3 chiffres.

---

## 12. Cloudinary en local (optionnel)

Requis uniquement pour **l'upload d'images admin** (`POST /api/admin/uploads/image`).

```env
CLOUDINARY_CLOUD_NAME=votre_cloud
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
CLOUDINARY_FOLDER=cyna
```

Sans Cloudinary : le catalogue et l'API fonctionnent ; seuls les uploads backoffice échoueront.

---

## 13. Connexion avec le front et l'app mobile

### Cyna_front (PHP)

Dans la configuration du front (fichier config ou `.env` du dépôt `Cyna_front`), pointez l'URL de l'API vers :

```
http://127.0.0.1:8000/api
```

Alignez `FRONTEND_URL` dans `.env` de l'API sur l'URL réelle du front local pour que les liens e-mail de confirmation fonctionnent.

### cyna-mobile (Expo)

Dans `src/services/api.ts`, adaptez temporairement :

```typescript
const API_BASE_URL = 'http://127.0.0.1:8000/api';
```

**Émulateur Android :** utilisez `http://10.0.2.2:8000/api` au lieu de `127.0.0.1`.

**Appareil physique :** utilisez l'IP LAN de votre PC (ex. `http://192.168.1.10:8000/api`).

---

## 14. Commandes utiles

| Commande | Description |
|----------|-------------|
| `php artisan serve` | Démarre l'API sur le port 8000 |
| `php artisan queue:work` | Traite les jobs (e-mails, etc.) |
| `php artisan migrate` | Applique les migrations |
| `php artisan migrate:fresh` | **Reset complet** de la BDD (efface toutes les données) |
| `php artisan test` | Lance les 67 tests PHPUnit |
| `php artisan route:list --path=api` | Liste toutes les routes API |
| `php artisan stripe:sync-products` | Sync produits → Stripe |
| `php artisan schedule:run` | Exécute les tâches planifiées (1 fois ; cron en prod) |
| `./vendor/bin/pint` | Formate le code PHP |
| `composer audit` | Vérifie les vulnérabilités des dépendances |

---

## 15. Dépannage

### `SQLSTATE[HY000]: General error: 1 no such table`

→ Lancez `php artisan migrate`.

### `Please provide a valid cache path` ou erreurs `storage/`

→ Vérifiez que `storage/framework/cache`, `storage/logs`, `bootstrap/cache` existent et sont inscriptibles.

### E-mails / OTP jamais reçus

1. Worker lancé ? (`php artisan queue:work` ou `composer run dev`)
2. `MAIL_MAILER=log` → consultez `storage/logs/laravel.log`
3. Table `jobs` qui grossit sans se vider → worker absent ou en erreur

### `401 Unauthenticated` sur les routes protégées

→ Ajoutez le header :

```
Authorization: Bearer <votre_token_sanctum>
```

### `403 email_verification_required`

→ Confirmez l'e-mail : `POST /api/auth/verify-email` avec `id` + `token`, ou marquez `est_confirme = 1` en tinker pour les tests.

### Erreur Stripe « No API key provided »

→ Renseignez `STRIPE_SECRET` dans `.env` et redémarrez le serveur.

### `composer install` échoue sur PHP 8.3

→ Le projet exige **PHP ^8.4** (`composer.json`). Mettez à jour PHP.

### Tests CI GitHub échouent alors qu'ils passent en local

→ Vérifiez que le dossier `tests/` n'est **pas** dans `.gitignore` et qu'il est bien poussé sur GitHub.

---

## Récapitulatif express (5 minutes)

```bash
git clone <repo> cyna-api && cd cyna-api
composer install
copy .env.example .env          # Windows
# Éditer .env : APP_URL=http://127.0.0.1:8000, DB_CONNECTION=sqlite
New-Item database\database.sqlite -ItemType File -Force
php artisan key:generate
php artisan migrate
php artisan serve               # terminal 1
php artisan queue:work          # terminal 2
curl http://127.0.0.1:8000/up
php artisan test
```

