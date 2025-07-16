<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\Component\HttpFoundation\File\File; // Importez File
use Vich\UploaderBundle\Mapping\Annotation as Vich; // Importez VichUploaderBundle
use Symfony\Component\Validator\Constraints as Assert; // Import pour les validations

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[Vich\Uploadable] //ajouter cette annotation à la classe pour activer VichUploader
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private ?bool $isPublished = false;

    // ANCIEN : #[ORM\Column(length: 255, nullable: true)]
    // ANCIEN : private ?string $imageUrl = null;

    // NOUVEAU : Cette propriété stocke le nom du fichier image généré par VichUploader
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    // NOUVEAU : Propriété pour stocker la taille du fichier (ajouté pour les métadonnées)
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $imageSize = null;

    // NOUVEAU : Propriété pour stocker le type MIME du fichier (ajouté pour les métadonnées)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageMimeType = null;

    // NOUVEAU : Propriété pour stocker le nom original du fichier (ajouté pour les métadonnées)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageOriginalName = null;

    // NOUVEAU : Cette propriété n'est pas persistée en BDD.
    // Elle est utilisée par VichUploader pour le téléchargement depuis le formulaire.
    // MISE À JOUR : Ajout des attributs 'size', 'mimeType' et 'originalName' pour que VichUploader
    // remplisse automatiquement les propriétés correspondantes après l'upload.
    #[Vich\UploadableField(mapping: 'article_image', fileNameProperty: 'imageName', size: 'imageSize', mimeType: 'imageMimeType', originalName: 'imageOriginalName')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP)',
        maxSizeMessage: 'La taille du fichier ne doit pas dépasser 5 MB'
    )]
    private ?File $imageFile = null;

    #[ORM\Column]
    private ?int $viewCount = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->viewCount = 0;
        $this->isPublished = false;
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static

    {
        $this->content = $content;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    // NOUVEAU : Getter et Setter pour imageName
    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    // NOUVEAU : Getter et Setter pour imageSize
    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): static
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    // NOUVEAU : Getter et Setter pour imageMimeType
    public function getImageMimeType(): ?string
    {
        return $this->imageMimeType;
    }

    public function setImageMimeType(?string $imageMimeType): static
    {
        $this->imageMimeType = $imageMimeType;

        return $this;
    }

    // NOUVEAU : Getter et Setter pour imageOriginalName
    public function getImageOriginalName(): ?string
    {
        return $this->imageOriginalName;
    }

    public function setImageOriginalName(?string $imageOriginalName): static
    {
        $this->imageOriginalName = $imageOriginalName;

        return $this;
    }

    /**
     * NOUVEAU : Getter et Setter pour imageFile.
     * Cette méthode est appelée par VichUploader lorsque on soumet un fichier.
     * Si télécharger manuellement un fichier (par exemple, depuis une requête API)
     * assurez-vous qu'une instance de 'UploadedFile' est injectée dans ce setter pour déclencher la mise à jour.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // C'est nécessaire pour déclencher les événements de Doctrine,
            // sinon les listeners de VichUploader ne seront pas appelés et le fichier est perdu.
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    // NOUVEAU : Méthode pour obtenir l'URL complète de l'image (comme on a imageUrl avant)
    // Elle construira l'URL à partir de imageName.
    public function getImageUrl(): ?string
    {
        if ($this->imageName) {
            // Attention : '/uploads/images/articles/' doit correspondre à l'uri_prefix configuré dans vich_uploader.yaml
            return '/uploads/images/articles/' . $this->imageName;
        }
        return null;
    }

    // ANCIEN : Setter pour imageUrl (laisser au cas où d'autres parties du code s'y fient,
    // mais il ne sera plus utilisé directement pour l'upload par VichUploader)
    // public function setImageUrl(?string $imageUrl): static
    // {
    //     $this->imageUrl = $imageUrl;
    //     return $this;
    // }


    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }

        return $this;
    }
}