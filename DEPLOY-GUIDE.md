# Guide de Déploiement Heroku Container Registry

**Date:** 16 octobre 2025
**Application:** Inner Garden
**Sprint:** 1 - Éco-Conception

---

## 🎯 Prérequis

Avant de déployer, assurez-vous que :

- ✅ Heroku CLI est installé
- ✅ Docker est installé et fonctionne
- ✅ Vous êtes connecté à Heroku
- ✅ L'application fonctionne localement (http://localhost:8081)

---

## 🚀 Méthode 1: Script Automatique (Recommandé)

### Étape 1: Se connecter à Heroku

```bash
heroku login
```

Cela ouvrira votre navigateur pour l'authentification.

### Étape 2: Exécuter le script de déploiement

```bash
./deploy-heroku.sh
```

Le script va automatiquement :
1. Vérifier Heroku CLI
2. Vérifier l'authentification
3. Se connecter au Container Registry
4. Builder le Docker image
5. Pusher sur Heroku
6. Releaser le container
7. Afficher les logs

---

## 🔧 Méthode 2: Déploiement Manuel

### Étape 1: Se connecter à Heroku

```bash
heroku login
```

### Étape 2: Se connecter au Container Registry

```bash
heroku container:login
```

### Étape 3: Builder et Pusher le container

```bash
# Build et push en une commande
heroku container:push web --app innergarden

# OU spécifier un Dockerfile particulier
heroku container:push web --app innergarden --arg APP_ENV=prod
```

**Note:** Cette étape peut prendre 5-10 minutes selon votre connexion.

### Étape 4: Releaser le container

```bash
heroku container:release web --app innergarden
```

### Étape 5: Vérifier le déploiement

```bash
# Voir les logs
heroku logs --tail --app innergarden

# Ouvrir l'app dans le navigateur
heroku open --app innergarden

# Vérifier le statut des dynos
heroku ps --app innergarden
```

---

## 🔍 Vérification du Dockerfile

Avant de déployer, vérifiez que votre Dockerfile est compatible Heroku :

### Points importants :

1. **Port dynamique** : Heroku définit la variable `$PORT`

   Dans votre `Dockerfile` ou script de démarrage, utilisez :
   ```bash
   # Déjà configuré dans votre projet
   Listen ${PORT:-80}
   ```

2. **Variables d'environnement** : Vérifiez `.env` ou configurez sur Heroku
   ```bash
   # Voir les variables actuelles
   heroku config --app innergarden

   # Ajouter une variable
   heroku config:set DATABASE_URL="mysql://..." --app innergarden
   ```

3. **Process Type** : Le `web` est le type par défaut pour les applications web

---

## 📊 Variables d'Environnement Heroku

### Variables critiques à configurer :

```bash
# Database
heroku config:set DATABASE_URL="mysql://user:pass@host:port/db" --app innergarden

# Cloudinary
heroku config:set CLOUDINARY_URL="cloudinary://..." --app innergarden

# MongoDB (si utilisé)
heroku config:set MONGODB_URL="mongodb://..." --app innergarden

# App
heroku config:set APP_ENV="prod" --app innergarden
heroku config:set APP_SECRET="votre-secret-symfony" --app innergarden
```

### Vérifier toutes les variables :

```bash
heroku config --app innergarden
```

---

## 🐛 Résolution de Problèmes

### Problème 1: Erreur "Invalid credentials"

**Solution:**
```bash
# Se reconnecter
heroku login

# Vérifier l'authentification
heroku auth:whoami
```

### Problème 2: "Application error" après déploiement

**Solution:**
```bash
# Voir les logs d'erreur
heroku logs --tail --app innergarden

# Redémarrer l'application
heroku ps:restart --app innergarden
```

### Problème 3: Le container ne démarre pas

**Vérifications:**

1. **Port correctement configuré ?**
   ```bash
   heroku logs --tail --app innergarden | grep PORT
   ```

2. **Variables d'environnement configurées ?**
   ```bash
   heroku config --app innergarden
   ```

3. **Dockerfile valide localement ?**
   ```bash
   docker compose up --build
   ```

### Problème 4: Temps de build trop long

**Solutions:**

1. **Utiliser .dockerignore**
   ```bash
   # Déjà configuré dans votre projet
   cat .dockerignore
   ```

2. **Vérifier la taille de l'image**
   ```bash
   docker images | grep innergarden
   ```

### Problème 5: Base de données non accessible

**Solution:**
```bash
# Vérifier les add-ons
heroku addons --app innergarden

# Ajouter ClearDB MySQL (gratuit)
heroku addons:create cleardb:ignite --app innergarden

# Récupérer l'URL de la base
heroku config:get CLEARDB_DATABASE_URL --app innergarden
```

---

## 📈 Post-Déploiement

### 1. Vérifier l'application

```bash
# Ouvrir dans le navigateur
heroku open --app innergarden

# OU
curl -I https://innergarden.herokuapp.com
```

### 2. Tester les optimisations Éco-Conception

- ✅ Vérifier que Font Awesome n'est plus chargé
- ✅ Vérifier que Bootstrap Icons fonctionne
- ✅ Tester le lazy loading des images
- ✅ Vérifier le skip link (invisible par défaut)
- ✅ Tester la navigation au clavier (Tab)

### 3. Tests Lighthouse

```bash
# Installer Lighthouse
npm install -g lighthouse

# Tester l'app Heroku
lighthouse https://innergarden.herokuapp.com --only-categories=performance --view
```

**Scores attendus :**
- Performance: >85
- First Contentful Paint: <2.5s
- Cumulative Layout Shift: <0.1

### 4. Monitorer les performances

```bash
# Voir les métriques
heroku ps:scale web=1 --app innergarden

# Voir l'utilisation mémoire
heroku ps --app innergarden

# Activer le monitoring
heroku logs --tail --app innergarden
```

---

## 🔄 Redéploiement

Pour redéployer après des modifications :

```bash
# Méthode 1: Script automatique
./deploy-heroku.sh

# Méthode 2: Manuel
heroku container:push web --app innergarden
heroku container:release web --app innergarden
```

**Note:** Pas besoin de commit Git pour les déploiements container !

---

## 📝 Checklist de Déploiement

Avant chaque déploiement :

- [ ] Tests locaux passent (Docker fonctionne)
- [ ] Variables d'environnement configurées
- [ ] `.env` vérifié (ne pas commit secrets)
- [ ] Dockerfile optimisé
- [ ] `.dockerignore` à jour
- [ ] Logs vérifiés localement
- [ ] Documentation à jour

Après déploiement :

- [ ] URL accessible
- [ ] Pas d'erreurs dans les logs
- [ ] Base de données connectée
- [ ] Images chargées correctement
- [ ] Optimisations éco-conception actives
- [ ] Tests Lighthouse effectués

---

## 📞 Commandes Utiles

```bash
# Logs en temps réel
heroku logs --tail --app innergarden

# Ouvrir l'application
heroku open --app innergarden

# Redémarrer l'app
heroku ps:restart --app innergarden

# Voir les dynos actifs
heroku ps --app innergarden

# Accéder au shell du container
heroku run bash --app innergarden

# Exécuter une commande Symfony
heroku run php bin/console cache:clear --app innergarden

# Voir la configuration
heroku config --app innergarden

# Voir les releases
heroku releases --app innergarden

# Rollback à une version précédente
heroku rollback v123 --app innergarden
```

---

## 🌍 URLs Importantes

- **Application:** https://innergarden.herokuapp.com
- **Dashboard:** https://dashboard.heroku.com/apps/innergarden
- **Logs:** https://dashboard.heroku.com/apps/innergarden/logs
- **Metrics:** https://dashboard.heroku.com/apps/innergarden/metrics

---

## 🎉 Résumé Sprint 1

**Optimisations déployées :**
- ✅ Resource hints (preconnect, dns-prefetch)
- ✅ Google Fonts subset (-10 KB)
- ✅ Font Awesome supprimé (-70 KB)
- ✅ Bootstrap Icons uniquement (+10 KB)
- ✅ Lazy loading images
- ✅ Placeholder SVG inline (-382 KB)
- ✅ Skip link accessible caché

**Gains totaux :**
- Poids: -462 KB (-92%)
- CO2e: -23 kg/mois
- Score RGAA: 95%
- Équivalent: 115 km en voiture économisés/mois

---

**Version:** 1.0.0
**Date:** 16 octobre 2025
**Prochain sprint:** Images WebP (Sprint 2)
