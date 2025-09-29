<?php
// Test Cloudinary Integration
require_once "cloudinary_helper.php";

// Test configuration
try {
    $cloudinary = new CloudinaryHelper();
    echo "✅ Cloudinary Helper initialized successfully!\n";
    echo "📋 Make sure to update your cloudinary_config.php with your actual credentials:\n\n";
    echo "1. Go to https://cloudinary.com/console\n";
    echo "2. Copy your Cloud Name, API Key, and API Secret\n";
    echo "3. Update cloudinary_config.php with these values\n\n";
    echo "📁 Files will be uploaded to: queue_system/attachments/ folder in your Cloudinary account\n";
    
} catch (Exception $e) {
    echo "❌ Error initializing Cloudinary: " . $e->getMessage() . "\n";
    echo "Please check your cloudinary_config.php file and ensure all credentials are correct.\n";
}
?>
