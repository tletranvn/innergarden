# Configuration MongoDB Atlas pour Heroku

## Vue d'ensemble

MongoDB Atlas est le service cloud gratuit de MongoDB. Il offre 512MB de stockage gratuit, parfait pour le développement et les petites applications.

## Étape 1 : Créer un compte MongoDB Atlas

1. **Rendez-vous sur** : [https://www.mongodb.com/cloud/atlas](https://www.mongodb.com/cloud/atlas)
2. **Cliquez sur** "Try Free"
3. **Créez un compte** avec votre email
4. **Vérifiez votre email** et connectez-vous

## Étape 2 : Créer un cluster gratuit

1. **Choisissez** "M0 Sandbox" (gratuit - 512MB)
2. **Sélectionnez** un fournisseur cloud :
   - AWS recommandé pour Heroku
   - Région : `us-east-1` (Virginia) pour une latence optimale
3. **Nommez** votre cluster : `innergarden-cluster`
4. **Cliquez** sur "Create Cluster"

**Attendre** 3-5 minutes pour la création du cluster

## Étape 3 : Créer un utilisateur de base de données

1. **Cliquez** sur "Database Access" dans le menu de gauche
2. **Cliquez** sur "Add New Database User"
3. **Configurez** :
   - Authentication Method : `Password`
   - Username : `innergarden_user`
   - Password : Générer un mot de passe fort (bouton "Autogenerate")
   - Database User Privileges : `Read and write to any database`
4. **Sauvegardez** le mot de passe quelque part de sûr
5. **Cliquez** sur "Add User"

## Étape 4 : Configurer l'accès réseau

1. **Cliquez** sur "Network Access" dans le menu de gauche
2. **Cliquez** sur "Add IP Address"
3. **Choisissez** "Allow Access from Anywhere" (0.0.0.0/0)
   - Pour la production, restreindre aux IPs Heroku
4. **Cliquez** sur "Confirm"

## Étape 5 : Obtenir l'URI de connexion

1. **Retournez** sur "Clusters"
2. **Cliquez** sur "Connect" sur votre cluster
3. **Choisissez** "Connect your application"
4. **Sélectionnez** :
   - Driver : `PHP`
   - Version : `1.4.0 or later`
5. **Copiez** l'URI de connexion

### Format de l'URI :
```
mongodb+srv://innergarden_user:PASSWORD@innergarden-cluster.xxxxx.mongodb.net/innergarden?retryWrites=true&w=majority
```

**Remplacez** `PASSWORD` par votre mot de passe réel

## Étape 6 : Configuration sur Heroku

### Méthode 1 : Via le script automatique
```bash
./scripts/setup-mongodb-atlas.sh
```

### Méthode 2 : Manuelle
```bash
# Configurer l'URI MongoDB
heroku config:set MONGODB_URL="mongodb+srv://innergarden_user:VOTRE_PASSWORD@innergarden-cluster.xxxxx.mongodb.net/innergarden?retryWrites=true&w=majority"

# Vérifier la configuration
heroku config:get MONGODB_URL
```

## Étape 7 : Redéploiement

```bash
# Committer les changements
git add .
git commit -m "Configuration MongoDB Atlas"

# Redéployer sur Heroku
git push heroku main
```

## Étape 8 : Vérification

```bash
# Vérifier les logs
heroku logs --tail

# Tester la connexion
heroku run php bin/console doctrine:mongodb:schema:create
```

## Monitoring MongoDB Atlas

### Dashboard Atlas
1. **Connectez-vous** à [https://cloud.mongodb.com](https://cloud.mongodb.com)
2. **Sélectionnez** votre cluster
3. **Surveillez** :
   - Connexions actives
   - Utilisation du stockage
   - Opérations par seconde

### Métriques importantes
- **Stockage** : 512MB maximum (plan gratuit)
- **Connexions** : 100 connexions simultanées maximum
- **Opérations** : Pas de limite sur le plan gratuit

## Sécurité

### Bonnes pratiques
1. **Utilisateur dédié** : Créer un utilisateur par environnement
2. **Mots de passe forts** : Utiliser des mots de passe complexes
3. **Restriction IP** : Limiter l'accès aux IPs Heroku en production
4. **Chiffrement** : Activé par défaut sur Atlas

### IPs Heroku (pour la production)
```
50.19.85.132
50.19.85.154
50.19.85.156
```

## Dépannage

### Erreur de connexion
```bash
# Vérifier l'URI
heroku config:get MONGODB_URL

# Tester la connexion
heroku run php -r "
try {
    \$client = new MongoDB\Client(getenv('MONGODB_URL'));
    \$db = \$client->innergarden;
    echo 'Connexion réussie!';
} catch (Exception \$e) {
    echo 'Erreur: ' . \$e->getMessage();
}
"
```

### Problèmes courants
1. **Mot de passe incorrect** : Vérifier le mot de passe dans l'URI
2. **Accès réseau** : Vérifier que 0.0.0.0/0 est autorisé
3. **Nom de base** : Vérifier le nom de la base dans l'URI
4. **Caractères spéciaux** : Encoder les caractères spéciaux dans le mot de passe

## Limites du plan gratuit

- **Stockage** : 512MB
- **RAM** : Partagée
- **Connexions** : 100 simultanées
- **Sauvegarde** : Pas de sauvegarde automatique

## Migration vers un plan payant

Pour une application en production :
- **M2** : 2GB - $9/mois
- **M5** : 5GB - $25/mois
- **Sauvegardes** : Incluses dans les plans payants
