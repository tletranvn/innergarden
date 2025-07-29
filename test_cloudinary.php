<?php
// Test script to verify Cloudinary integration works

require_once 'vendor/autoload.php';

echo "Testing Cloudinary integration...\n";

try {
    // Test 1: Check if environment variables are loaded
    echo "1. Checking environment variables:\n";
    echo "   CLOUDINARY_URL: " . (getenv('CLOUDINARY_URL') ?: 'NOT SET') . "\n";
    echo "   CLOUDINARY_CLOUD_NAME: " . (getenv('CLOUDINARY_CLOUD_NAME') ?: 'NOT SET') . "\n";
    echo "   CLOUDINARY_API_KEY: " . (getenv('CLOUDINARY_API_KEY') ?: 'NOT SET') . "\n";
    echo "   CLOUDINARY_API_SECRET: " . (getenv('CLOUDINARY_API_SECRET') ?: 'NOT SET') . "\n\n";

    // Test 2: Try to instantiate Cloudinary
    echo "2. Creating Cloudinary instance...\n";
    $cloudinary = new \Cloudinary\Cloudinary([
        'cloud' => [
            'cloud_name' => 'dunb0wzvm',
            'api_key' => '188426328885564',
            'api_secret' => 'HJZt-utBB8RdmKl4xObnS7fqWfw'
        ]
    ]);
    echo "   ✓ Cloudinary instance created successfully\n\n";

    // Test 3: Try to create a temporary test file and upload it
    echo "3. Testing file upload...\n";
    $testContent = "This is a test image content";
    $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
    file_put_contents($tempFile, $testContent);
    
    echo "   - Temporary file created: $tempFile\n";
    echo "   - File size: " . filesize($tempFile) . " bytes\n";
    
    // Try to upload
    $result = $cloudinary->uploadApi()->upload($tempFile, [
        'resource_type' => 'auto',
        'public_id' => 'test_upload_' . time()
    ]);
    
    echo "   ✓ Upload successful!\n";
    echo "   - Public ID: " . $result['public_id'] . "\n";
    echo "   - Secure URL: " . $result['secure_url'] . "\n";
    
    // Clean up
    unlink($tempFile);
    echo "   - Temporary file cleaned up\n\n";
    
    echo "✓ All tests passed! Cloudinary integration is working.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
