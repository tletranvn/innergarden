# Inner Garden

Une plateforme de blog sur le jardinage, le bien-etre, le developpement personnel et les voyages nature.

## Description

Inner Garden est une application web construite avec Symfony 7.4 qui permet de publier et consulter des articles organises par categories. L'application propose un systeme de gestion d'articles avec support d'images, commentaires, et un tableau de bord d'administration.

## Fonctionnalites

- Gestion d'articles avec editeur de contenu
- Systeme de categories (Jardinage, Voyage Nature, Bien-etre, Developpement Personnel)
- Upload d'images via Cloudinary
- Systeme de commentaires
- Pagination des articles
- Filtrage par categorie
- Tableau de bord administrateur
- Publication programmee d'articles
- Logs d'activite avec MongoDB
- Formulaire de contact

## Technologies

- PHP 8.3
- Symfony 7.4 (RC)
- MySQL 9.4
- MongoDB 7.0
- Docker et Docker Compose
- Cloudinary (gestion d'images)
- KnpPaginatorBundle (pagination)
- Doctrine ORM et ODM

## Pre-requis

- Docker et Docker Compose
- PHP 8.3 ou superieur (pour developpement local sans Docker)
- Composer
- Node.js (optionnel, pour les assets)

## Installation

### Avec Docker (recommande)

1. Cloner le depot

```bash
git clone https://github.com/tletranvn/innergarden.git
cd innergarden
```

2. Copier le fichier d'environnement

```bash
cp .env.example .env.docker.local
```

3. Configurer les variables d'environnement dans `.env.docker.local`

4. Demarrer les conteneurs Docker

```bash
docker compose up -d
```

5. Installer les dependances

```bash
docker compose exec www composer install
```

6. Creer la base de donnees et executer les migrations

```bash
docker compose exec www php bin/console doctrine:database:create
docker compose exec www php bin/console doctrine:migrations:migrate
```

7. Charger les donnees de test (optionnel)

```bash
docker compose exec www php bin/console doctrine:fixtures:load
```

8. Acceder a l'application

```
http://localhost:8085
```

### Accès aux Bases de Données (Docker)

- **MySQL**: `localhost:3309` (User: `root`, Password: (vide), Database: `innergarden`)
- **MongoDB**: `localhost:27020` (User: `root`, Password: `rootpassword`, Database: `innergarden_mongodb`)
- **Mailpit**: `http://localhost:8025`

### Sans Docker

1. Installer les dependances

```bash
composer install
```

2. Configurer la base de donnees dans `.env.local`

3. Creer la base de donnees

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. Lancer le serveur Symfony

```bash
symfony serve
```

## Configuration

### Variables d'environnement

Creer un fichier `.env.local` avec les variables suivantes :

```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/innergarden"
MONGODB_URL="mongodb://username:password@127.0.0.1:27017"
MONGODB_DB="innergarden_mongodb"
CLOUDINARY_URL="cloudinary://api_key:api_secret@cloud_name"
```

### Base de donnees

Le schema de base de donnees se trouve dans `public/docs/MCD.sql`

## Utilisation

### Creer un compte administrateur

```bash
php bin/console app:create-admin
```

### Publier les articles programmes

```bash
php bin/console app:publish-scheduled-articles
```

### Sauvegardes

Des scripts de sauvegarde sont disponibles dans le dossier `scripts/` :

```bash
# Sauvegarde complete (MySQL + MongoDB + uploads)
./scripts/backup_complete.sh

# Sauvegarde MySQL uniquement
./scripts/backup_mysql.sh

# Sauvegarde MongoDB uniquement
./scripts/backup_mongodb.sh
```

## Deploiement

L'application est configuree pour etre deployee sur Heroku via Container Registry.

1. Se connecter a Heroku

```bash
heroku login
heroku container:login
```

2. Deployer

Deployer avec Container Registry :

container:push
container:release

## Tests

```bash
php bin/phpunit
```

## Contribution

Les contributions sont les bienvenues. Veuillez ouvrir une issue ou soumettre une pull request.

## Licence

Ce projet est sous licence MIT.

## Auteur

Developpe avec Symfony et deploye sur Heroku.

## Support

Pour toute question ou probleme, veuillez ouvrir une issue sur GitHub.
