<?php
require 'vendor/autoload.php'; // Assuming you've installed Cloudinary PHP SDK via Composer

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// Configure Cloudinary
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'your_cloud_name',
        'api_key' => 'your_api_key',
        'api_secret' => 'your_api_secret'
    ],
    'url' => [
        'secure' => true
    ]
]);
?>