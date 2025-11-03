<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;

#[Document(collection: "photos")]
class Photo
{
    #[Id]
    protected ?string $id = null; // L'ID MongoDB est généralement une chaîne

    #[Field(type: "string")]
    protected ?string $filename = null; // Nom du fichier tel qu'il est stocké sur le serveur (après renommage par Vich)

    #[Field(type: "string")]
    protected ?string $originalFilename = null; // Nom du fichier tel qu'il a été téléchargé par l'utilisateur

    #[Field(type: "string")]
    protected ?string $mimeType = null; // Type MIME du fichier (ex: image/jpeg)

    #[Field(type: "int")]
    protected ?int $size = null; // Taille du fichier en octets

    #[Field(type: "string", name: "related_article_id")] // ID de l'article MySQL lié, stocké comme string
    protected ?string $relatedArticleId = null;

    #[Field(type: "string")] // Store base64 encoded image data (optional, for old system)
    protected ?string $imageData = null;

    #[Field(type: "string")] // Cloudinary secure URL
    protected ?string $url = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getRelatedArticleId(): ?string
    {
        return $this->relatedArticleId;
    }

    public function setRelatedArticleId(string $relatedArticleId): self
    {
        $this->relatedArticleId = $relatedArticleId;
        return $this;
    }

    public function getImageData(): ?string
    {
        return $this->imageData;
    }

    public function setImageData(?string $imageData): self
    {
        $this->imageData = $imageData;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
}