<?php
session_start();
include("connect.php");
$realtorId = $_SESSION['realtor_id'];
if (!isset($realtorId)) {
    header("location: realtorregistration.php");
    exit();
}

$sql_user = "SELECT `full_name` FROM `users` WHERE id=? LIMIT 1";
    $stmt = $pdo->prepare($sql_user);
    $stmt->execute([$realtorId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the property details
if (isset($_GET['id'])) {
    $property_id = $_GET['id'];
    $query = "SELECT * FROM `property` WHERE `property_id` = :property_id AND `user_id` = :user_id LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['property_id' => $property_id, 'user_id' => $realtorId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header("location: realtorpage.php");
        exit();
    }
} else {
    header("location: realtorpage.php");
    exit();
}

// Fetch property images
$imageQuery = "SELECT * FROM `propery_image` WHERE `propery_id` = :property_id";
$imageStmt = $pdo->prepare($imageQuery);
$imageStmt->execute(['property_id' => $property_id]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for updating property
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateproperty'])) {
    // Update property details in the database
    $updateQuery = "UPDATE `property` SET
        `title` = :title,
        `property_type` = :property_type,
        `status` = :status,
        `price` = :price,
        `Area` = :area,
        `bedrooms` = :bedrooms,
        `bathrooms` = :bathrooms,
        `year` = :year,
        `location` = :location,
        `description` = :description,
        `contact` = :contact
        WHERE `property_id` = :property_id AND `user_id` = :user_id";

    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([
        'title' => $_POST['title'],
        'property_type' => $_POST['property_type'],
        'status' => $_POST['property_status'],
        'price' => $_POST['price'],
        'area' => $_POST['area'],
        'bedrooms' => $_POST['bedrooms'],
        'bathrooms' => $_POST['bathrooms'],
        'year' => $_POST['year'],
        'location' => $_POST['location'],
        'description' => $_POST['description'],
        'contact' => $_POST['contact'],
        'property_id' => $property_id,
        'user_id' => $realtorId
    ]);

    // Handle image uploads (if any)
    if (!empty($_FILES['images']['name'][0])) {
        // Delete old images (optional)
        // Upload new images and update the database
        // (You can reuse your existing image upload logic here)
    }

    header("location: realtorpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Kenzo Homes</title>
    <link rel="stylesheet" href="styles/realtorpage.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <h1 class="logo">Kenzo Homes</h1>
                <p class="tagline">Manage and showcase your property listings with ease</p>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="realtorpage.php" class="nav-link">
                            <span class="nav-icon">📤</span>
                            Upload Property
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="realtorpage.php" class="nav-link">
                            <span class="nav-icon">🏠</span>
                            View Properties
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Edit Property</h1>
                    <p class="header-subtitle">Update your property listing</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">JD</div>
                        <div class="user-details">
                            <h3><?= $user['full_name'] ?></h3>
                            <p>Real Estate Agent</p>
                        </div>
                    </div>
                    <button class="logout-btn" onclick="logout()">Logout</button>
                </div>
            </header>
            <div class="content-area">
                <div class="card">
                    <div class="card-header">
                        <h2>Edit Property</h2>
                        <p>Update the details of your property listing</p>
                    </div>
                    <div class="card-body">
                        <form id="edit-property-form" method="POST" enctype="multipart/form-data">
                            <div id="success-alert" class="alert alert-success" style="display: none;">
                                <span>✓</span>
                                <div>Property has been updated successfully!</div>
                            </div>
                            <div id="error-alert" class="alert alert-error" style="display: none;">
                                <span>⚠️</span>
                                <div id="error-message">Error updating property!</div>
                            </div>

                            <div class="form-group">
                                <label for="property-title">Property Title</label>
                                <input name="title" type="text" id="property-title" value="<?= htmlspecialchars($property['title']) ?>" placeholder="e.g. Modern Beachfront Villa" required>
                            </div>

                            <div class="form-group">
                                <label for="contact">Contact</label>
                                <input name="contact" type="text" id="contact" value="<?= htmlspecialchars($property['contact']) ?>" placeholder="+234xxxxxxx" required>
                            </div>

                            <div class="input-group">
                                <div class="form-group">
                                    <label for="property-type">Property Type</label>
                                    <select id="property-type" name="property_type" required>
                                        <option value="house" <?= $property['property_type'] === 'house' ? 'selected' : '' ?>>House</option>
                                        <option value="apartment" <?= $property['property_type'] === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                                        <option value="condo" <?= $property['property_type'] === 'condo' ? 'selected' : '' ?>>Air-BNB</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="property-status">Status</label>
                                    <select id="property-status" name="property_status" required>
                                        <option value="for-sale" <?= $property['status'] === 'for-sale' ? 'selected' : '' ?>>For Sale</option>
                                        <option value="for-rent" <?= $property['status'] === 'for-rent' ? 'selected' : '' ?>>For Accommodation</option>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group">
                                <div class="form-group">
                                    <label for="property-price">Price (₦)</label>
                                    <input type="number" id="property-price" value="<?= htmlspecialchars($property['price']) ?>" placeholder="e.g. 250000" required name="price">
                                </div>
                                <div class="form-group">
                                    <label for="property-area">Area (sq ft)</label>
                                    <input type="number" id="property-area" value="<?= htmlspecialchars($property['Area']) ?>" placeholder="e.g. 1500" required name="area">
                                </div>
                            </div>

                            <div class="input-group">
                                <div class="form-group">
                                    <label for="property-bedrooms">Bedrooms</label>
                                    <input type="number" id="property-bedrooms" value="<?= htmlspecialchars($property['bedrooms']) ?>" placeholder="e.g. 3" min="0" name="bedrooms">
                                </div>
                                <div class="form-group">
                                    <label for="property-bathrooms">Bathrooms</label>
                                    <input type="number" id="property-bathrooms" value="<?= htmlspecialchars($property['bathrooms']) ?>" placeholder="e.g. 2" min="0" step="0.5" name="bathrooms">
                                </div>
                                <div class="form-group">
                                    <label for="property-year">Year Built</label>
                                    <input type="number" id="property-year" value="<?= htmlspecialchars($property['year']) ?>" placeholder="e.g. 2020" min="1800" name="year">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="property-address">Address</label>
                                <input type="text" id="property-address" value="<?= htmlspecialchars($property['location']) ?>" placeholder="Enter full property address" required name="location" autocomplete="off">
                                <div id="address-suggestions" class="address-suggestions"></div>
                            </div>

                            <div class="form-group">
                                <label for="property-description">Description</label>
                                <textarea id="property-description" placeholder="Enter a detailed description of the property" required name="description"><?= htmlspecialchars($property['description']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Property Images</label>
                                <div class="image-preview-container" id="image-preview">
                                    <?php foreach ($images as $image): ?>
                                        <div class="image-preview">
                                            <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Property Image">
                                            <button type="button" class="remove-image" onclick="removeImage(<?= $image['image_id'] ?>)">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="file-upload" id="file-upload-area">
                                    <div class="file-upload-icon">📷</div>
                                    <div class="file-upload-text">Click to select images or drag and drop</div>
                                    <div class="file-upload-info">Upload up to 10 images (Max size: 5MB each)</div>
                                    <input type="file" id="property-images" accept="image/*" multiple name="images[]">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" onclick="window.location.href='realtorpage.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary" name="updateproperty">Update Property</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Reuse your existing JavaScript for image preview, address autocomplete, etc.
        // Add a function to handle image removal if needed
        function removeImage(imageId) {
            if (confirm('Are you sure you want to remove this image?')) {
                // Send an AJAX request to remove the image from the server
                fetch('remove-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `image_id=${imageId}&property_id=<?= $property_id ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the image preview from the DOM
                        event.target.closest('.image-preview').remove();
                    } else {
                        alert('Failed to remove image.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
            }
        }
    </script>
</body>
</html>
