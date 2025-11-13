# Protection XSS Multi-Niveaux pour les Commentaires

## 🔒 Implémentations de Sécurité

### ✅ Niveau 1 : Échappement JavaScript (ACTIF)
**Fichier** : `public/js/comment.js` lignes 26-34

La fonction `escapeHtml()` convertit tous les caractères HTML dangereux :
```javascript
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
```

**Protection** : Empêche l'exécution de scripts côté client lors de l'insertion AJAX.

---

### ✅ Niveau 2 : strip_tags PHP (ACTIF)
**Fichier** : `src/Entity/Comment.php` ligne 67

```php
public function setComment(string $comment): static
{
    // SÉCURITÉ XSS : Supprimer toutes les balises HTML
    $this->comment = strip_tags($comment);
    return $this;
}
```

**Protection** : Supprime TOUTES les balises HTML avant l'enregistrement en base de données.

---

### ✅ Niveau 3 : HTMLPurifier (DISPONIBLE)
**Fichier** : `src/Service/HtmlSanitizer.php`

Permet du HTML sûr : **gras**, *italique*, liens, listes, etc.

#### Option A : Utiliser dans l'entité Comment

Modifiez `src/Entity/Comment.php` :

```php
use App\Service\HtmlSanitizer;

// Dans setComment() :
public function setComment(string $comment): static
{
    // OPTION 3 : HTMLPurifier (à activer si vous voulez du HTML sûr)
    // $sanitizer = new HtmlSanitizer();
    // $this->comment = $sanitizer->sanitize($comment);

    // OPTION 2 : strip_tags (actuellement actif)
    $this->comment = strip_tags($comment);

    return $this;
}
```

⚠️ **Problème** : L'injection de service dans une entité Doctrine n'est pas recommandée.

#### Option B : Utiliser dans le contrôleur (RECOMMANDÉ)

Modifiez `src/Controller/CommentController.php` :

```php
use App\Service\HtmlSanitizer;

class CommentController extends AbstractController
{
    public function __construct(
        private HtmlSanitizer $htmlSanitizer
    ) {}

    #[Route('/articles/{id}/comment', name: 'app_comment_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Article $article, EntityManagerInterface $em): JsonResponse
    {
        // ... code existant ...

        if ($form->isSubmitted() && $form->isValid()) {
            // Sanitiser le contenu avec HTMLPurifier
            $cleanContent = $this->htmlSanitizer->sanitize($comment->getComment());
            $comment->setComment($cleanContent);

            $em->persist($comment);
            $em->flush();

            // ... reste du code ...
        }
    }
}
```

Et **supprimez** le strip_tags de `src/Entity/Comment.php` :

```php
public function setComment(string $comment): static
{
    $this->comment = $comment; // Pas de strip_tags ici
    return $this;
}
```

---

## 🧪 Test de Sécurité

### Payload de test :
```
Super article ! <script>alert('Piraté')</script><img src=x onerror=alert('XSS')>
```

### Résultats attendus :

| Niveau | Affichage | HTML stocké |
|--------|-----------|-------------|
| **Niveau 1 seul** | `Super article ! &lt;script&gt;...` | `<script>alert('Piraté')</script>` ⚠️ |
| **Niveau 2 (strip_tags)** | `Super article !` | `Super article !` ✅ |
| **Niveau 3 (HTMLPurifier)** | `Super article !` | `Super article !` ✅ |

### Avec HTMLPurifier, ce HTML est autorisé :
```
<strong>Texte gras</strong>
<em>Texte italique</em>
<a href="https://example.com">Lien sûr</a>
<ul><li>Liste</li></ul>
```

---

## 🎯 Recommandation

**Configuration actuelle (Niveaux 1 + 2)** : Protection maximale, pas de HTML.

**Si vous voulez permettre du formatage** :
1. Remplacez `strip_tags` par `HtmlSanitizer` dans le contrôleur
2. Gardez l'échappement JavaScript en Niveau 1
3. Balises autorisées : `<strong>`, `<em>`, `<a>`, `<ul>`, `<ol>`, `<li>`, `<blockquote>`

---

## 📝 Fichiers modifiés

- ✅ `public/js/comment.js` (Niveau 1)
- ✅ `src/Entity/Comment.php` (Niveau 2)
- ✅ `src/Service/HtmlSanitizer.php` (Niveau 3 - créé)
