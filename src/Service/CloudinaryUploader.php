<?php

namespace App\Service;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryUploader
{
    private UploadApi $uploadApi;
    private string $cloudName; // Store cloud name to use in getUrl

    public function __construct(
        string $cloudName,
        string $apiKey,
        string $apiSecret
    ) {
        $this->cloudName = $cloudName; // Store it
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
            'folder' => 'innergarden/articles', // Keep this, it's correct for setting the folder
            'resource_type' => 'auto',
            'overwrite' => true,
        ];

        // Merge user-provided options with defaults.
        // If a 'public_id' is provided in $options, it will be used.
        // If it includes a path, that path will be used.
        // If it doesn't include a path, the 'folder' option will determine the folder.
        $options = array_merge($defaultOptions, $options);

        // Debugging: Uncomment to see options before upload
        // var_dump($options);

        $result = $this->uploadApi->upload($file->getPathname(), $options);
        
        // Return the result with the full public_id that includes the folder if set
        return $result;
    }

    public function delete(string $publicId): array
    {
        return $this->uploadApi->destroy($publicId);
    }

    /**
     * Constructs the full Cloudinary URL for an image.
     * $publicId should be the full public_id as returned by Cloudinary (e.g., 'innergarden/articles/my-slug_timestamp').
     */
    public function getUrl(string $publicId): string
    {
        // Cloudinary URLs automatically handle folder paths embedded in the public_id.
        // No need to manually add 'innergarden/articles/' here if publicId already contains it.
        return sprintf(
            'https://res.cloudinary.com/%s/image/upload/%s',
            $this->cloudName, // Use the stored cloud name
            $publicId
        );
    }
}