<?php

namespace App\Service;

use App\Document\ActivityLog;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    public function __construct(
        private DocumentManager $documentManager,
        private RequestStack $requestStack
    ) {
    }

    public function logArticleView(Article $article, ?User $user = null): void
    {
        $this->log('view', $article, $user);
    }

    public function logArticleCreate(Article $article, User $user): void
    {
        $this->log('create', $article, $user);
    }

    public function logArticleEdit(Article $article, User $user): void
    {
        $this->log('edit', $article, $user);
    }

    public function logArticleDelete(Article $article, User $user): void
    {
        $this->log('delete', $article, $user);
    }

    public function logCommentCreate(Comment $comment, ?User $user = null): void
    {
        $this->logComment('comment_create', $comment, $user);
    }

    public function logCommentApprove(Comment $comment, User $user): void
    {
        $this->logComment('comment_approve', $comment, $user);
    }

    public function logCommentDelete(Comment $comment, User $user): void
    {
        $this->logComment('comment_delete', $comment, $user);
    }

    private function log(string $action, Article $article, ?User $user = null): void
    {
        try {
            $activityLog = new ActivityLog();
            $activityLog->setAction($action);
            $activityLog->setArticleId($article->getId());
            $activityLog->setArticleTitle($article->getTitle());

            if ($user) {
                $activityLog->setUserId((string)$user->getId());
                $activityLog->setUserEmail($user->getEmail());
                $activityLog->setUserRoles($user->getRoles());
            } else {
                $activityLog->setUserEmail('Visiteur');
                $activityLog->setUserRoles(['ROLE_VISITOR']);
            }

            // Ajouter des métadonnées depuis la requête (seulement IP)
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $activityLog->addMetadata('ip', $request->getClientIp());
            }

            $this->documentManager->persist($activityLog);
            $this->documentManager->flush();
            $this->documentManager->clear(); // Clear to avoid transaction conflicts

        } catch (\Exception $e) {
            // Log silencieusement les erreurs MongoDB pour ne pas casser l'app
            error_log("ActivityLogger error: " . $e->getMessage());
        }
    }

    private function logComment(string $action, Comment $comment, ?User $user = null): void
    {
        try {
            $activityLog = new ActivityLog();
            $activityLog->setAction($action);

            // Stocker l'ID du commentaire dans metadata
            $activityLog->addMetadata('commentId', $comment->getId());
            $activityLog->addMetadata('commentContent', mb_substr($comment->getContent(), 0, 100)); // Premiers 100 caractères

            // Lier à l'article associé
            if ($comment->getArticle()) {
                $activityLog->setArticleId($comment->getArticle()->getId());
                $activityLog->setArticleTitle($comment->getArticle()->getTitle());
            }

            if ($user) {
                $activityLog->setUserId((string)$user->getId());
                $activityLog->setUserEmail($user->getEmail());
                $activityLog->setUserRoles($user->getRoles());
            } else {
                // Commentaire visiteur
                $activityLog->setUserEmail($comment->getAuthorName() ?? 'Visiteur');
                $activityLog->setUserRoles(['ROLE_VISITOR']);
            }

            // Ajouter des métadonnées depuis la requête (seulement IP)
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $activityLog->addMetadata('ip', $request->getClientIp());
            }

            $this->documentManager->persist($activityLog);
            $this->documentManager->flush();

        } catch (\Exception $e) {
            // Log silencieusement les erreurs MongoDB pour ne pas casser l'app
            error_log("ActivityLogger comment error: " . $e->getMessage());
        }
    }
}
