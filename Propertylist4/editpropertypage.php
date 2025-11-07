<?php
session_start();
include("connect.php");
require 'vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure Cloudinary
Configuration::instance([
    'cloud' => [
        'cloud_name' => $_ENV['CLOUD_NAME'],
        'api_key' => $_ENV['API_KEY'],
        'api_secret' => $_ENV['SECRET_API_KEY'],
    ],
    'url' => [
        'secure' => true
    ]
]);

// Check if user is logged in
if (!isset($_SESSION['realtor_id'])) {
    header("location: realtorregistration.php");
    exit();
}

// Check if property_id is provided
if (!isset($_GET['property_id'])) {
    header("location: realtorpage.php");
    exit();
}

$property_id = $_GET['property_id'];

// Fetch property details
$property_query = "SELECT * FROM property WHERE property_id = ? AND user_id = ?";
$stmt = $pdo->prepare($property_query);
$stmt->execute([$property_id, $_SESSION['realtor_id']]);
$property = $stmt->fetch();

// Check if property exists and belongs to the user
if (!$property) {
    header("location: realtorpage.php");
    exit();
}

// Fetch property images
$images_query = "SELECT * FROM propery_image WHERE propery_id = ?";
$stmt = $pdo->prepare($images_query);
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateproperty'])) {
    // Validate and sanitize input
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $property_type = filter_input(INPUT_POST, 'property_type', FILTER_SANITIZE_STRING);
    $area = filter_input(INPUT_POST, 'area', FILTER_SANITIZE_NUMBER_INT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_INT);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $bedrooms = filter_input(INPUT_POST, 'bedrooms', FILTER_SANITIZE_NUMBER_INT);
    $bathrooms = filter_input(INPUT_POST, 'bathrooms', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Update property in database
    $update_query = "UPDATE property SET
                    title = ?,
                    property_type = ?,
                    Area = ?,
                    description = ?,
                    price = ?,
                    location = ?,
                    bedrooms = ?,
                    bathrooms = ?,
                    year = ?,
                    status = ?
                    WHERE property_id = ? AND user_id = ?";

    $stmt = $pdo->prepare($update_query);
    $success = $stmt->execute([
        $title, $property_type, $area, $description, $price,
        $location, $bedrooms, $bathrooms, $year, $status,
        $property_id, $_SESSION['realtor_id']
    ]);

    // Handle image uploads if any
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_type = $_FILES['images']['type'][$key];

            // Validate file (size, type, etc.)
            if ($file_size > 5000000) { // 5MB max
                continue;
            }

            // Upload to Cloudinary
            $upload_result = (new UploadApi())->upload($file_tmp, [
                'folder' => 'real_estate/properties/',
                'public_id' => 'property_' . $property_id . '_' . time() . '_' . $key
            ]);

            if ($upload_result && isset($upload_result['secure_url'])) {
                // Save to database
                $image_insert = "INSERT INTO propery_image (propery_id, image_url) VALUES (?, ?)";
                $stmt = $pdo->prepare($image_insert);
                $stmt->execute([$property_id, $upload_result['secure_url']]);
            }
        }
    }

    // Handle image deletions if any
    if (!empty($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_id) {
            // First get the image URL to delete from Cloudinary
            $get_image_query = "SELECT image_url FROM propery_image WHERE image_id = ? AND propery_id = ?";
            $stmt = $pdo->prepare($get_image_query);
            $stmt->execute([$image_id, $property_id]);
            $image = $stmt->fetch();

            if ($image) {
                // Extract public_id from Cloudinary URL
                $url_parts = explode('/', $image['image_url']);
                $public_id = end($url_parts);
                $public_id = explode('.', $public_id)[0];

                // Delete from Cloudinary
                try {
                    (new UploadApi())->destroy($public_id);
                } catch (Exception $e) {
                    error_log("Cloudinary delete error: " . $e->getMessage());
                }

                // Delete from database
                $delete_query = "DELETE FROM propery_image WHERE image_id = ? AND propery_id = ?";
                $stmt = $pdo->prepare($delete_query);
                $stmt->execute([$image_id, $property_id]);
            }
        }
    }

    // Redirect back with success message
    $_SESSION['success_message'] = 'Property updated successfully!';
    header("Location: editpropertypage.php?property_id=" . $property_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - RealEstate Hub</title>
    <link rel="stylesheet" href="styles/editproperty.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Property</h1>
            <a href="realtorpage.php" class="back-link">← Back to Dashboard</a>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h2><i class="fas fa-home"></i> Basic Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Property Title</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($property['title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="property_type">Property Type</label>
                            <select id="property_type" name="property_type" required>
                                <option value="house" <?= $property['property_type'] == 'house' ? 'selected' : '' ?>>House</option>
                                <option value="apartment" <?= $property['property_type'] == 'apartment' ? 'selected' : '' ?>>Apartment</option>
                                <option value="condo" <?= $property['property_type'] == 'condo' ? 'selected' : '' ?>>Condo</option>
                                <option value="townhouse" <?= $property['property_type'] == 'townhouse' ? 'selected' : '' ?>>Townhouse</option>
                                <option value="land" <?= $property['property_type'] == 'land' ? 'selected' : '' ?>>Land</option>
                                <option value="commercial" <?= $property['property_type'] == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" value="<?= htmlspecialchars($property['price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="area">Area (sq ft)</label>
                            <input type="number" id="area" name="area" value="<?= htmlspecialchars($property['Area']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bedrooms">Bedrooms</label>
                            <input type="number" id="bedrooms" name="bedrooms" min="0" value="<?= htmlspecialchars($property['bedrooms']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="bathrooms">Bathrooms</label>
                            <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?= htmlspecialchars($property['bathrooms']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="year">Year Built</label>
                            <input type="number" id="year" name="year" min="1800" value="<?= htmlspecialchars($property['year']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Location</h2>
                    <div class="form-group">
                        <label for="location">Address</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($property['location']) ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-align-left"></i> Description</h2>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required><?= htmlspecialchars($property['description']) ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-images"></i> Property Images</h2>
                    <div class="image-gallery">
                        <?php foreach ($images as $image): ?>
                            <div class="image-item">
                                <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Property image">
                                <div class="image-actions">
                                    <input type="checkbox" name="delete_images[]" value="<?= $image['image_id'] ?>" id="delete_<?= $image['image_id'] ?>">
                                    <label for="delete_<?= $image['image_id'] ?>" class="delete-btn" title="Delete image">
                                        <i class="fas fa-trash"></i>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="image-upload">
                            <label for="images">Add More Images</label>
                            <input type="file" id="images" name="images[]" multiple accept="image/*">
                            <div class="upload-prompt">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <p class="small">(Max 10 images, 5MB each)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-tag"></i> Status</h2>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="for-sale" <?= $property['status'] == 'for-sale' ? 'selected' : '' ?>>For Sale</option>
                            <option value="for-rent" <?= $property['status'] == 'for-rent' ? 'selected' : '' ?>>For Rent</option>
                            <option value="sold" <?= $property['status'] == 'sold' ? 'selected' : '' ?>>Sold</option>
                            <option value="rented" <?= $property['status'] == 'rented' ? 'selected' : '' ?>>Rented</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='realtorpage.php'">Cancel</button>
                    <button type="submit" name="updateproperty" class="btn btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image upload preview
        document.getElementById('images').addEventListener('change', function(e) {
            const files = e.target.files;
            const gallery = document.querySelector('.image-gallery');

            // Remove the upload box temporarily
            const uploadBox = document.querySelector('.image-upload');
            uploadBox.remove();

            for (let i = 0; i < files.length; i++) {
                if (files[i].type.match('image.*')) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imageItem = document.createElement('div');
                        imageItem.className = 'image-item';

                        const img = document.createElement('img');
                        img.src = e.target.result;

                        const actions = document.createElement('div');
                        actions.className = 'image-actions';
                        actions.innerHTML = `
                            <button type="button" class="delete-btn" onclick="this.parentElement.parentElement.remove()">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;

                        imageItem.appendChild(img);
                        imageItem.appendChild(actions);
                        gallery.appendChild(imageItem);
                    }

                    reader.readAsDataURL(files[i]);
                }
            }

            // Add the upload box back
            gallery.appendChild(uploadBox);
        });

        // Drag and drop functionality
        const uploadBox = document.querySelector('.image-upload');

        uploadBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadBox.classList.add('dragover');
        });

        uploadBox.addEventListener('dragleave', () => {
            uploadBox.classList.remove('dragover');
        });

        uploadBox.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadBox.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('images').files = files;
                // Trigger the change event
                const event = new Event('change');
                document.getElementById('images').dispatchEvent(event);
            }
        });
    </script>
</body>
</html>
