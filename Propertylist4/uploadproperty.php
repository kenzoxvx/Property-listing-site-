<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('display_errors', 0);

// Validate session
if (!isset($_SESSION['realtor_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

// Load database connection
include("connect.php");
if (!$pdo) {
    error_log("Database connection failed");
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Initialize Cloudinary
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Load environment variables
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
    
    // Get credentials from environment or use hardcoded values
    $cloudName = $_ENV['CLOUD_NAME'] ?? "your_cloud_name";
    $apiKey = $_ENV['API_KEY'] ?? "your_api_key";
    $apiSecret = $_ENV['SECRET_API_KEY'] ?? "your_secret_api_key";
    
    $cloudinary = new Cloudinary\Cloudinary([
        'cloud' => [
            "cloud_name" => $cloudName,
            "api_key"    => $apiKey,
            "api_secret" => $apiSecret,
        ],
    ]);
    
} catch (Exception $e) {
    error_log("Cloudinary initialization error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Server configuration error']));
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    try {
        // Validate required fields
        $required = ['title', 'property_type', 'property_status', 'price', 'area',
             'bedrooms', 'bathrooms', 'year', 'location', 'description'];
// Add 'c_of_o_document' if mandatory


        $missing = [];
        foreach ($required as $field) {
            if (empty(trim($_POST[$field]))) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit;
        }

        // Validate file uploads
        if (empty($_FILES['images']) || empty($_FILES['images']['tmp_name'][0])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please upload at least one image']);
            exit;
        }

        $uploadurls = [];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpname) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                error_log("Upload error for file $index: " . $_FILES['images']['error'][$index]);
                continue;
            }
            
            if (!is_uploaded_file($tmpname)) {
                error_log("Possible file upload attack: " . $_FILES['images']['name'][$index]);
                continue;
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmpname);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                error_log("Invalid file type: " . $mime_type);
                continue;
            }

            // Validate file size
            if ($_FILES['images']['size'][$index] > 5 * 1024 * 1024) {
                error_log("File too large: " . $_FILES['images']['name'][$index]);
                continue;
            }

            try {
                $uploadResult = $cloudinary->uploadApi()->upload($tmpname);
                $uploadurls[] = $uploadResult['secure_url'];
            } catch (Exception $e) {
                error_log("Cloudinary upload error: " . $e->getMessage());
                continue;
            }
        }

        if (empty($uploadurls)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid images were uploaded']);
            exit;
        }

        // Handle C of O/Deed document upload
$cOfODocumentUrl = null;
if (!empty($_FILES['c_of_o_document']['tmp_name'])) {
    $tmpname = $_FILES['c_of_o_document']['tmp_name'];
    if ($_FILES['c_of_o_document']['error'] === UPLOAD_ERR_OK && is_uploaded_file($tmpname)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmpname);
        finfo_close($finfo);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (in_array($mime_type, $allowed_types) && $_FILES['c_of_o_document']['size'] <= 5 * 1024 * 1024) {
            try {
                $uploadResult = $cloudinary->uploadApi()->upload($tmpname, ['resource_type' => 'auto']);
                $cOfODocumentUrl = $uploadResult['secure_url'];
            } catch (Exception $e) {
                error_log("Cloudinary upload error for C of O: " . $e->getMessage());
            }
        }
    }
}


        $insert_property = "INSERT INTO `property`
    (`user_id`, `title`, `contact`, `property_type`, `Area`, `description`, `price`, `location`,
     `bedrooms`, `bathrooms`, `year`, `status`, `confirmation_status`, `c_of_o_url`, `cretedAt`)
    VALUES
    (:user_id, :title, :contact, :property_type, :area, :description, :price, :location,
     :bedrooms, :bathrooms, :year, :status, :confirmation_status, :c_of_o_url, :createdAt)";

$stmt = $pdo->prepare($insert_property);
$result = $stmt->execute([
    "user_id"            => $_SESSION['realtor_id'],
    "title"              => trim($_POST['title']),
    "contact"            => trim($_POST['contact']),
    "property_type"      => $_POST['property_type'],
    "area"               => $_POST['area'],
    "description"        => trim($_POST['description']),
    "price"              => $_POST['price'],
    "location"           => trim($_POST['location']),
    "bedrooms"           => $_POST['bedrooms'],
    "bathrooms"          => $_POST['bathrooms'],
    "year"               => $_POST['year'],
    "status"             => $_POST['property_status'],
    "confirmation_status"=> 'pending',
    "c_of_o_url"         => $cOfODocumentUrl,
    "createdAt"          => date("Y-m-d H:i:s")
]);




        if (!$result) {
            throw new Exception("Database error: Failed to insert property");
        }

        $property_id = $pdo->lastInsertId();

        // Insert images - one record per image
        $insert_image = "INSERT INTO `propery_image` 
            (`propery_id`, `image_url`, `uploaded_at`) 
            VALUES 
            (:property_id, :image_url, :uploaded_at)";
        $imgStmt = $pdo->prepare($insert_image);
        $uploaded_at = date("Y-m-d H:i:s");

        foreach ($uploadurls as $url) {
            $imgResult = $imgStmt->execute([
                'property_id' => $property_id,
                'image_url'  => $url,
                'uploaded_at' => $uploaded_at
            ]);
            
            if (!$imgResult) {
                throw new Exception("Database error: Failed to insert image");
            }
        }

        echo json_encode([
            "success" => true, 
            "message" => 'Property added successfully', 
            "property_id" => $property_id
        ]);
        
    } catch (Exception $e) {
        error_log("Property upload error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error processing property. Please try again."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => 'Invalid request method']);
}
?>