
-- Insérer un nouvel utilisateur
INSERT INTO user (email, roles, password, pseudo, created_at, is_verified)
VALUES 
('tenten@email.com', '["ROLE_ADMIN"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'tenten', NOW()),
('alice@email.com', '["ROLE_USER"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'alice', NOW()),
('bob@email.com', '["ROLE_USER"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'bob', NOW()),
('charlie@email.com', '["ROLE_USER"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'charlie', NOW()),
('diana@email.com', '["ROLE_USER"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'diana', NOW()),
('eve@email.com', '["ROLE_ADMIN"]', '$2y$13$vK3AeR.1rvi1tVTsnK.gceDw73EbHbHir.gyxJ9YvzlUzt4/.Ykwq', 'eve', NOW());

-- Insérer les catégories (si elles n'existent pas déjà)
INSERT INTO category (id, name, slug, description) VALUES 
(1, 'Jardinage', 'jardinage', 'Conseils et astuces pour votre jardin'),
(2, 'Voyage Nature', 'voyage-nature', 'Découvertes et explorations'),
(3, 'Bien-être', 'bien-etre', 'Santé et équilibre de vie'),
(4, 'Développement Personnel', 'developpement-personnel', 'Croissance et amélioration de soi')
ON DUPLICATE KEY UPDATE name=name;

-- Insérer un article (lié à la catégorie 'Jardinage' id=1)
INSERT INTO article (title, slug, content, created_at, published_at, is_published, author_id, category_id)
VALUES ('Mon Super Article', 'mon-super-article', 'Contenu de l''article...', NOW(), NOW(), 1, 1, 1);
-- Note: author_id=1 (tenten) et category_id=1 (Jardinage)


-- Modifier le titre d'un article
UPDATE article
SET title = 'Titre Modifié', slug = 'titre-modifie', updated_at = NOW()
WHERE id = 1;

-- Publier tous les articles d'une catégorie
UPDATE article
SET is_published = 1, published_at = NOW()
WHERE category_id = 2;

-- Changer le rôle d'un utilisateur
UPDATE user
SET roles = '["ROLE_ADMIN"]'
WHERE email = 'admin@innergarden.com';


-- Supprimer un commentaire spécifique
DELETE FROM comment WHERE id = 123;

-- Supprimer tous les articles brouillons (non publiés)
DELETE FROM article WHERE is_published = 0;

-- Attention : Pour supprimer une catégorie, il faut d'abord gérer les articles liés
-- (soit les supprimer, soit changer leur catégorie, soit utiliser ON DELETE CASCADE)
DELETE FROM category WHERE id = 5;


-- ========================================================================
-- B. REQUÊTES DE LECTURE (SELECT)
-- ========================================================================
-- Chaque section contient :
--   1. Le code PHP QueryBuilder original
--   2. L'équivalent SQL commenté
--   3. La requête SQL exécutable
-- ========================================================================

-- ========================================================================
-- 1. ArticleController::list() - Liste des articles avec filtrage par catégorie
-- ========================================================================

-- QueryBuilder PHP (ArticleController.php:308-320):
-- $queryBuilder = $articleRepository->createQueryBuilder('a')
--     ->where('a.isPublished = :published')
--     ->setParameter('published', true);
--
-- if ($categorySlug) {
--     $queryBuilder
--         ->leftJoin('a.category', 'c')
--         ->andWhere('c.slug = :categorySlug')
--         ->setParameter('categorySlug', $categorySlug);
-- }
--
-- $queryBuilder->orderBy('a.publishedAt', 'DESC');

-- SQL équivalent SANS filtre de catégorie:
SELECT a.*
FROM article a
WHERE a.is_published = 1
ORDER BY a.published_at DESC;

-- SQL équivalent AVEC filtre de catégorie (exemple: 'jardinage'):
SELECT a.*
FROM article a
LEFT JOIN category c ON a.category_id = c.id
WHERE a.is_published = 1
  AND c.slug = 'jardinage'
ORDER BY a.published_at DESC;


-- ========================================================================
-- 2. AdminController::dashboard() - Articles les plus vus
-- ========================================================================

-- QueryBuilder PHP (AdminController.php:48-53):
-- $mostViewedArticles = $articleRepository->createQueryBuilder('a')
--     ->where('a.viewCount > 0')
--     ->orderBy('a.viewCount', 'DESC')
--     ->setMaxResults(5)
--     ->getQuery()
--     ->getResult();

-- SQL équivalent:
SELECT a.*
FROM article a
WHERE a.view_count > 0
ORDER BY a.view_count DESC
LIMIT 5;


-- ========================================================================
-- 3. AdminController::dashboard() - Tous les articles pour l'admin
-- ========================================================================

-- QueryBuilder PHP (AdminController.php:56-57):
-- $query = $articleRepository->createQueryBuilder('a')
--     ->orderBy('a.createdAt', 'DESC');

-- SQL équivalent:
SELECT a.*
FROM article a
ORDER BY a.created_at DESC;


-- ========================================================================
-- 4. PublishScheduledArticlesCommand - Lister tous les articles avec leurs statuts
-- ========================================================================

-- QueryBuilder PHP (PublishScheduledArticlesCommand.php:36-39):
-- $allArticles = $this->articleRepository->createQueryBuilder('a')
--     ->select('a.id', 'a.title', 'a.publishedAt', 'a.isPublished')
--     ->getQuery()
--     ->getArrayResult();

-- SQL équivalent:
SELECT a.id, a.title, a.published_at, a.is_published
FROM article a;


-- ========================================================================
-- 5. PublishScheduledArticlesCommand - Articles programmés à publier
-- ========================================================================

-- QueryBuilder PHP (PublishScheduledArticlesCommand.php:56-61):
-- $articlesToPublish = $this->articleRepository->createQueryBuilder('a')
--     ->where('a.publishedAt <= :now')
--     ->andWhere('a.isPublished = false OR a.isPublished IS NULL')
--     ->setParameter('now', $now)
--     ->getQuery()
--     ->getResult();

-- SQL équivalent (exemple avec date actuelle):
SELECT a.*
FROM article a
WHERE a.published_at <= NOW()
  AND (a.is_published = 0 OR a.is_published IS NULL);

-- Ou avec une date spécifique:
SELECT a.*
FROM article a
WHERE a.published_at <= '2025-11-20 12:00:00'
  AND (a.is_published = 0 OR a.is_published IS NULL);


-- ========================================================================
-- 6. ContactRepository::findUnprocessed() - Messages non traités
-- ========================================================================

-- QueryBuilder PHP (ContactRepository.php:24-29):
-- return $this->createQueryBuilder('c')
--     ->andWhere('c.isProcessed = :processed')
--     ->setParameter('processed', false)
--     ->orderBy('c.createdAt', 'DESC')
--     ->getQuery()
--     ->getResult();

-- SQL équivalent:
SELECT c.*
FROM contact c
WHERE c.is_processed = 0
ORDER BY c.created_at DESC;


-- ========================================================================
-- 7. ContactRepository::countUnprocessed() - Compter les messages non traités
-- ========================================================================

-- QueryBuilder PHP (ContactRepository.php:37-42):
-- return $this->createQueryBuilder('c')
--     ->select('COUNT(c.id)')
--     ->andWhere('c.isProcessed = :processed')
--     ->setParameter('processed', false)
--     ->getQuery()
--     ->getSingleScalarResult();

-- SQL équivalent:
SELECT COUNT(c.id)
FROM contact c
WHERE c.is_processed = 0;


-- ========================================================================
-- 8. REQUÊTES count() UTILISÉES DANS LES CONTRÔLEURS
-- ========================================================================

-- AdminController.php utilise plusieurs count() qui deviennent:

-- Total des articles:
-- $totalArticles = $articleRepository->count([]);
SELECT COUNT(*) FROM article;

-- Articles publiés:
-- $publishedArticles = $articleRepository->count(['isPublished' => true]);
SELECT COUNT(*) FROM article WHERE is_published = 1;

-- Articles en brouillon:
-- $draftArticles = $articleRepository->count(['isPublished' => false]);
SELECT COUNT(*) FROM article WHERE is_published = 0;

-- Articles par auteur (utilisé dans AdminController::usersList):
-- $articleRepository->count(['author' => $user]);
SELECT COUNT(*) FROM article WHERE author_id = 1; -- exemple avec user id=1

-- Commentaires par auteur:
-- $commentRepository->count(['author' => $user]);
SELECT COUNT(*) FROM comment WHERE author_id = 1; -- exemple avec user id=1


-- ========================================================================
-- 9. REQUÊTES AVEC JOINTURES COMPLEXES
-- ========================================================================

-- Pour récupérer les articles avec leurs catégories et auteurs:
SELECT
    a.id,
    a.title,
    a.slug,
    a.content,
    a.excerpt,
    a.published_at,
    a.is_published,
    a.view_count,
    c.name AS category_name,
    c.slug AS category_slug,
    u.pseudo AS author_pseudo,
    u.email AS author_email
FROM article a
LEFT JOIN category c ON a.category_id = c.id
LEFT JOIN user u ON a.author_id = u.id
WHERE a.is_published = 1
ORDER BY a.published_at DESC;


-- ========================================================================
-- 10. STATISTIQUES AVANCÉES
-- ========================================================================

-- Nombre d'articles par catégorie (publiés seulement):
SELECT
    c.name AS category_name,
    c.slug AS category_slug,
    COUNT(a.id) AS article_count
FROM category c
LEFT JOIN article a ON a.category_id = c.id AND a.is_published = 1
GROUP BY c.id, c.name, c.slug
ORDER BY c.name;

-- Articles les plus commentés:
SELECT
    a.id,
    a.title,
    a.slug,
    COUNT(co.id) AS comment_count
FROM article a
LEFT JOIN comment co ON co.article_id = a.id
WHERE a.is_published = 1
GROUP BY a.id, a.title, a.slug
ORDER BY comment_count DESC
LIMIT 10;

-- Auteurs les plus actifs (nombre d'articles publiés):
SELECT
    u.id,
    u.pseudo,
    u.email,
    COUNT(a.id) AS article_count
FROM user u
LEFT JOIN article a ON a.author_id = u.id AND a.is_published = 1
GROUP BY u.id, u.pseudo, u.email
ORDER BY article_count DESC;


-- ========================================================================
-- 11. REQUÊTES DE RECHERCHE ET FILTRAGE
-- ========================================================================

-- Recherche d'articles par mot-clé dans le titre ou le contenu:
SELECT a.*
FROM article a
WHERE a.is_published = 1
  AND (a.title LIKE '%jardinage%' OR a.content LIKE '%jardinage%')
ORDER BY a.published_at DESC;

-- Articles publiés entre deux dates:
SELECT a.*
FROM article a
WHERE a.is_published = 1
  AND a.published_at BETWEEN '2025-01-01' AND '2025-12-31'
ORDER BY a.published_at DESC;

-- Articles sans image:
SELECT a.*
FROM article a
WHERE a.is_published = 1
  AND (a.image_public_id IS NULL OR a.image_public_id = '')
ORDER BY a.published_at DESC;


-- ========================================================================
-- 12. REQUÊTES D'AGRÉGATION
-- ========================================================================

-- Moyenne des vues par catégorie:
SELECT
    c.name AS category_name,
    AVG(a.view_count) AS average_views,
    MIN(a.view_count) AS min_views,
    MAX(a.view_count) AS max_views,
    SUM(a.view_count) AS total_views
FROM category c
LEFT JOIN article a ON a.category_id = c.id AND a.is_published = 1
GROUP BY c.id, c.name;

-- Articles publiés par mois:
SELECT
    DATE_FORMAT(a.published_at, '%Y-%m') AS month,
    COUNT(*) AS article_count
FROM article a
WHERE a.is_published = 1
GROUP BY DATE_FORMAT(a.published_at, '%Y-%m')
ORDER BY month DESC;


-- ========================================================================
-- 13. REQUÊTES AVEC SOUS-REQUÊTES
-- ========================================================================

-- Articles avec le nombre de commentaires:
SELECT
    a.*,
    (SELECT COUNT(*) FROM comment c WHERE c.article_id = a.id) AS comment_count
FROM article a
WHERE a.is_published = 1
ORDER BY a.published_at DESC;

-- Catégories avec au moins 5 articles:
SELECT c.*
FROM category c
WHERE (
    SELECT COUNT(*)
    FROM article a
    WHERE a.category_id = c.id AND a.is_published = 1
) >= 5;


-- ========================================================================
-- 14. REQUÊTES DE MISE À JOUR
-- ========================================================================

-- Incrémenter le compteur de vues d'un article (ArticleController::show):
UPDATE article
SET view_count = view_count + 1
WHERE id = 1; -- exemple avec article id=1

-- Publier un article programmé:
UPDATE article
SET is_published = 1
WHERE id = 1; -- exemple avec article id=1

-- Marquer un message de contact comme traité:
UPDATE contact
SET is_processed = 1, processed_at = NOW()
WHERE id = 1; -- exemple avec contact id=1


-- ========================================================================
-- FIN DU FICHIER
-- ========================================================================
