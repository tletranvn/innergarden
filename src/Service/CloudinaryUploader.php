<?php

namespace App\Service;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryUploader
{
    private UploadApi $uploadApi;

    public function __construct(
        string $cloudName,
        string $apiKey,
        string $apiSecret
    ) {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
        ]);
        
        $this->uploadApi = new UploadApi();
    }

    public function upload(UploadedFile $file, array $options = []): array
    {
        $defaultOptions = [
            'folder' => 'innergarden/articles',
            'resource_type' => 'auto',
            'overwrite' => true,
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->uploadApi->upload($file->getPathname(), $options);
    }

    public function delete(string $publicId): array
    {
        return $this->uploadApi->destroy($publicId);
    }

    public function getUrl(string $filename): string
    {
        return sprintf(
            'https://res.cloudinary.com/%s/image/upload/innergarden/articles/%s',
            $_ENV['CLOUDINARY_CLOUD_NAME'],
            $filename
        );
    }
}
