<?php
// Test if Cloudinary classes are available
require_once __DIR__ . '/../vendor/autoload.php';

try {
    echo "Testing Cloudinary PHP SDK...\n";
    
    // Test if Cloudinary class can be loaded
    if (class_exists('Cloudinary\Cloudinary')) {
        echo "✓ Cloudinary\Cloudinary class is available\n";
    } else {
        echo "✗ Cloudinary\Cloudinary class NOT available\n";
    }
    
    // Test if we can create a Cloudinary instance
    $cloudinary = new \Cloudinary\Cloudinary();
    echo "✓ Cloudinary instance created successfully\n";
    
    echo "\nPHP Version: " . phpversion() . "\n";
    echo "Loaded extensions: \n";
    
    $required_extensions = ['json', 'curl', 'mbstring'];
    foreach ($required_extensions as $ext) {
        echo "- $ext: " . (extension_loaded($ext) ? "✓" : "✗") . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
