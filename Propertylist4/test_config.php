<?php
echo "Testing server configuration...<br>";

// Check if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    echo ".env file: FOUND<br>";
    
    // Read .env file contents (without exposing secrets)
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !empty(trim($line))) {
            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);
            echo "Env variable: $key = [SET]<br>";
        }
    }
} else {
    echo ".env file: NOT FOUND<br>";
}

// Check vendor directory
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "Composer vendor: FOUND<br>";
} else {
    echo "Composer vendor: NOT FOUND - Run: composer install<br>";
}

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "<br>";

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "Extension $ext: LOADED<br>";
    } else {
        echo "Extension $ext: MISSING<br>";
    }
}
?>