<?php

namespace App\Service;

use Cloudinary\Cloudinary; 
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Cloudinary\Transformation\Resize; 

class CloudinaryUploader
{
    private Cloudinary $cloudinary;

    public function __construct(
        Cloudinary $cloudinary
    ) {
        // Assign the injected Cloudinary object
        $this->cloudinary = $cloudinary;
    }

    /**
     * Uploads a file to Cloudinary.
     *
     * @param UploadedFile $file The file to upload.
     * @param array $options Additional upload options (e.g., 'folder', 'public_id').
     * @return array The upload result from Cloudinary.
     * @throws \Exception If the upload fails.
     */
    public function upload(UploadedFile $file, array $options = []): array
    {
        $defaultOptions = [
            'resource_type' => 'auto',
            // Upload directly to root - no folder structure
        ];

        // Merge user-provided options with defaults
        $options = array_merge($defaultOptions, $options);

        try {
            // Upload to Cloudinary - let it generate its own public_id
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $options);
            
            // Convert ApiResponse to array
            if (is_array($result)) {
                $resultArray = $result;
            } else {
                // For Cloudinary ApiResponse, convert to array
                $resultArray = (array) $result;
                // If that doesn't work, try accessing as object properties
                if (empty($resultArray) && isset($result->public_id)) {
                    $resultArray = [
                        'public_id' => $result->public_id,
                        'secure_url' => $result->secure_url ?? $result->url,
                        'url' => $result->url,
                        'width' => $result->width ?? null,
                        'height' => $result->height ?? null,
                        'format' => $result->format ?? null,
                        'resource_type' => $result->resource_type ?? 'image'
                    ];
                }
            }
            
            // Log what Cloudinary actually returned
            error_log("Cloudinary upload successful. Public ID: " . ($resultArray['public_id'] ?? 'unknown'));
            error_log("Cloudinary URL: " . ($resultArray['secure_url'] ?? $resultArray['url'] ?? 'unknown'));
            
            return $resultArray;
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload file to Cloudinary: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Deletes a file from Cloudinary.
     *
     * @param string $publicId The public ID of the image to delete.
     * @return array The deletion result from Cloudinary.
     * @throws \Exception If the deletion fails.
     */
    public function delete(string $publicId): array
    {
        try {
            // Use the uploadApi() method from the injected Cloudinary object
            return $this->cloudinary->uploadApi()->destroy($publicId);
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete file from Cloudinary: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Constructs the full Cloudinary URL for an image, with optional transformations.
     *
     * @param string $publicId The full public_id as returned by Cloudinary (e.g., 'my_folder/my_image').
     * @param array $transformations An associative array of transformation options (e.g., ['width' => 300, 'height' => 200, 'crop' => 'fill']).
     * @return string The generated Cloudinary URL.
     */
    public function getUrl(string $publicId, array $transformations = []): string
    {
        // se the Cloudinary object's image() method for robust URL generation.
        // This method automatically handles the cloud name and can apply complex transformations.
        $image = $this->cloudinary->image($publicId);

        // Apply transformations if provided
        if (!empty($transformations)) {
            // This is a basic way to apply transformations.
            // For more advanced transformations, you might use Cloudinary's Transformation classes
            // (e.g., Resize::fill(), Effect::sepia(), etc.)
            // Example:
            // $image->resize(Resize::fill($transformations['width'], $transformations['height']));
            // For simplicity, we'll pass them as direct options to the toUrl() method,
            // or you can build a more sophisticated loop here.

            // A more robust way to apply common transformations (requires Cloudinary\Transformation namespace imports)
            foreach ($transformations as $key => $value) {
                switch ($key) {
                    case 'width':
                        $image->width($value);
                        break;
                    case 'height':
                        $image->height($value);
                        break;
                    case 'crop':
                        $image->crop($value);
                        break;
                    // Add more cases for other common transformations like quality, gravity, etc.
                    // case 'quality':
                    //     $image->quality($value);
                    //     break;
                    // case 'gravity':
                    //     $image->gravity($value);
                    //     break;
                    // You can also use specific Transformation classes:
                    // case 'resize':
                    //     if (isset($value['type']) && $value['type'] === 'fill') {
                    //         $image->resize(Resize::fill($value['width'] ?? null, $value['height'] ?? null));
                    //     }
                    //     break;
                }
            }
        }

        return $image->toUrl();
    }
}