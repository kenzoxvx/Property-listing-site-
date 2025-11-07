<?php 
    session_start();
    include("connect.php");

    if ( !isset($_SESSION['realtor_id']) ) {
        header("location: realtorregistration.php");
        exit();
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenzo Homes - Realtor Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .logo-section {
            padding: 0 30px 40px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .tagline {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }
        .nav-menu {
            list-style: none;
            padding: 0 20px;
        }
        .nav-item {
            margin-bottom: 8px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            color: #374151;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        .nav-link:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }
        .nav-link:hover:before,
        .nav-link.active:before {
            left: 0;
        }
        .nav-link:hover,
        .nav-link.active {
            color: white;
            transform: translateX(8px);
        }
        .nav-icon {
            margin-right: 12px;
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 0;
        }
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-left h1 {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .header-subtitle {
            color: #6b7280;
            font-size: 16px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        .user-details h3 {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .user-details p {
            color: #6b7280;
            font-size: 14px;
        }
        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.6);
        }
        /* Content Area */
        .content-area {
            padding: 40px;
            min-height: calc(100vh - 100px);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Card Styles */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px 40px;
            position: relative;
        }
        .card-header:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        .card-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .card-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .card-body {
            padding: 40px;
        }
        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        /* File Upload */
        .file-upload {
            border: 3px dashed #d1d5db;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        .file-upload:hover {
            border-color: #667eea;
            background: #f3f4f6;
        }
        .file-upload-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .file-upload-text {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .file-upload-info {
            color: #6b7280;
            font-size: 14px;
        }
        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        /* Alert Styles */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        /* Button Styles */
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        .btn-outline {
            background: transparent;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        .btn-outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .form-actions {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        /* Properties Grid */
        .house-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        .house-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .house-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        .house-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        .house-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .house-card:hover .house-image img {
            transform: scale(1.1);
        }
        .house-price {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 18px;
            color: #1f2937;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .house-content {
            padding: 25px;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        .badge-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .house-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .house-address {
            color: #6b7280;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .house-features {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .house-feature {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        .house-desc {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .house-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
            .dashboard-container {
                flex-direction: column;
            }
            .header {
                padding: 15px 20px;
            }
            .content-area {
                padding: 20px;
            }
            .input-group {
                grid-template-columns: 1fr;
            }
            .house-grid {
                grid-template-columns: 1fr;
            }
            .user-info {
                display: none;
            }
        }
        /* No properties message */
        .no-properties {
            text-align: center;
            padding: 60px 40px;
            color: #6b7280;
        }
        .no-properties-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .no-properties h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #374151;
        }
        .no-properties p {
            font-size: 16px;
            line-height: 1.6;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .stat-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-title {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .stat-change {
            font-size: 14px;
            font-weight: 600;
        }
        .positive {
            color: #10b981;
        }
        .negative {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php
        
        if (!isset($_SESSION['realtor_id'])) {
            header("location: realtorregistration.php");
            exit();
        }
    ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-section">
                <h1 class="logo">Kenzo Homes</h1>
                <p class="tagline">Manage and showcase your property listings with ease</p>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" onclick="switchTab('upload', event)">
                            <span class="nav-icon">📤</span>
                            Upload Property
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" onclick="switchTab('properties', event)">
                            <span class="nav-icon">🏠</span>
                            View Properties
                        </a>
                    </li>
                    
                </ul>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <p class="header-subtitle">Welcome back! Manage your property listings</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">JD</div>
                        <div class="user-details">
                            <h3>John Doe</h3>
                            <p>Real Estate Agent</p>
                        </div>
                    </div>
                    <button class="logout-btn" onclick="logout()">Logout</button>
                </div>
            </header>
            <!-- Content Area -->
            <div class="content-area">
                <!-- Upload Property Tab -->
                <div id="upload" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2>Add New Property</h2>
                            <p>Fill in the details below to add a new property to your listings</p>
                        </div>
                        <div class="card-body">
                            <form id="property-form" method="POST" enctype="multipart/form-data" action="uploadproperty.php">
                                <div class="alert alert-success" style="display: none;">
                                    <span>✓</span>
                                    <div>Property has been added successfully!</div>
                                </div>
                                <div class="alert alert-error" style="display: none;">
                                    <span>⚠️</span>
                                    <div>Error uploading property!</div>
                                </div>
                                <div class="form-group">
                                    <label for="property-title">Property Title</label>
                                    <input name="title" type="text" id="property-title" placeholder="e.g. Modern Beachfront Villa" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact">Contact</label>
                                    <input name="contact" type="text" id="contact" placeholder="+234xxxxxxx" required>
                                </div>
                                <div class="input-group">
                                    <div class="form-group">
                                        <label for="property-type">Property Type</label>
                                        <select id="property-type" name="property_type" required>
                                            <option value="">Select property type</option>
                                            <option value="house">House</option>
                                            <option value="apartment">Apartment</option>
                                            <option value="condo">Air-BNB</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="property-status">Status</label>
                                        <select id="property-status" name="property_status" required>
                                            <option value="">Select status</option>
                                            <option value="for-sale">For Sale</option>
                                            <option value="for-rent">For Accommodation</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <div class="form-group">
                                        <label for="property-price">Price (₦)</label>
                                        <input type="number" id="property-price" placeholder="e.g. 250000" required name="price">
                                    </div>
                                    <div class="form-group">
                                        <label for="property-area">Area (sq ft)</label>
                                        <input type="number" id="property-area" placeholder="e.g. 1500" required name="area">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <div class="form-group">
                                        <label for="property-bedrooms">Bedrooms</label>
                                        <input type="number" id="property-bedrooms" placeholder="e.g. 3" min="0" name="bedrooms">
                                    </div>
                                    <div class="form-group">
                                        <label for="property-bathrooms">Bathrooms</label>
                                        <input type="number" id="property-bathrooms" placeholder="e.g. 2" min="0" step="0.5" name="bathrooms">
                                    </div>
                                    <div class="form-group">
                                        <label for="property-year">Year Built</label>
                                        <input type="number" id="property-year" placeholder="e.g. 2020" min="1800" name="year">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="property-address">Address</label>
                                    <input type="text" id="property-address" placeholder="Enter full property address" required name="location" autocomplete="off">
                                    <div id="address-suggestions" class="address-suggestions"></div>
                                </div>
                                <div class="form-group">
                                    <label for="property-description">Description</label>
                                    <textarea id="property-description" placeholder="Enter a detailed description of the property" required name="description"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Property Images</label>
                                    <div class="file-upload" id="file-upload-area">
                                        <div class="file-upload-icon">📷</div>
                                        <div class="file-upload-text">Drag and drop files here or click to browse</div>
                                        <div class="file-upload-info">Upload up to 10 images (Max size: 5MB each)</div>
                                        <input type="file" id="property-images" accept="image/*" multiple name="images[]">
                                    </div>
                                    <div class="image-preview-container" id="image-preview"></div>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-outline" onclick="resetForm()">Reset</button>
                                    <button type="submit" class="btn btn-primary" name="addproperty">Add Property</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- View Properties Tab -->
                <div id="properties" class="tab-content">
                    <div class="house-grid" id="properties-container">
                        <?php
                            $select_houses = "SELECT p.*, GROUP_CONCAT(pi.image_url) as image_urls
                                             FROM property p
                                             LEFT JOIN propery_image pi ON p.property_id = pi.propery_id
                                             WHERE p.user_id = ?
                                             GROUP BY p.property_id
                                             ORDER BY p.cretedAt DESC";
                            $stmt = $pdo->prepare($select_houses);
                            $stmt->execute([$_SESSION['realtor_id']]);
                            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($properties)) {
                                echo '<div class="no-properties">';
                                echo '<div class="no-properties-icon">🏠</div>';
                                echo '<h3>No Properties Found</h3>';
                                echo '<p>You have not added any properties yet.</p>';
                                echo '</div>';
                            } else {
                                foreach ($properties as $property) {
                                    $images = explode(',', $property['image_urls']);
                                    $main_image = !empty($images[0]) ? $images[0] : 'img/placeholder.jpg';
                        ?>
                        <div class="house-card">
                            <div class="house-image">
                                <img src="<?= htmlspecialchars($main_image) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                                <div class="house-price">₦<?= number_format($property['price']) ?></div>
                                <?php if (count($images) > 1): ?>
                                    <div class="image-count">+<?= count($images) - 1 ?> more</div>
                                <?php endif; ?>
                            </div>
                            <div class="house-content">
                                <span class="badge badge-<?= $property['status'] == 'for-sale' ? 'primary' : 'success' ?>">
                                    <?= ucwords(str_replace('-', ' ', $property['status'])) ?>
                                </span>
                                <h3 class="house-title"><?= htmlspecialchars($property['title']) ?></h3>
                                <p class="house-address"><?= htmlspecialchars($property['location']) ?></p>
                                <div class="house-features">
                                    <div class="house-feature">
                                        <span>🛏️</span> <?= htmlspecialchars($property['bedrooms']) ?> Beds
                                    </div>
                                    <div class="house-feature">
                                        <span>🚿</span> <?= htmlspecialchars($property['bathrooms']) ?> Baths
                                    </div>
                                    <div class="house-feature">
                                        <span>📏</span> <?= htmlspecialchars($property['area']) ?> sq ft
                                    </div>
                                </div>
                                <p class="house-desc"><?= htmlspecialchars(substr($property['description'], 0, 100)) ?><?= strlen($property['description']) > 100 ? '...' : '' ?></p>
                                <div class="house-actions">
                                    <button class="btn btn-sm btn-outline" onclick="openReportModal(<?= $property['property_id'] ?>)">Reports</button>
                                    <div>
                                        <a class="btn btn-sm btn-warning" href="editpropertypage.php?property_id=<?= $property['property_id'] ?>">Edit</a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteProperty(<?= $property['property_id'] ?>)">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Report Modal -->
    <div class="modal" id="report-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Property Performance Report</h3>
                <button class="modal-close" onclick="closeReportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h4 class="house-title" id="report-property-title">Modern Beach House</h4>
                <p class="house-address" id="report-property-address">123 Oceanview Dr, Lagos, Nigeria</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-title">Page Views</div>
                        <div class="stat-value">2,487</div>
                        <div class="stat-change positive">+12.5% ↑</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Inquiries</div>
                        <div class="stat-value">37</div>
                        <div class="stat-change positive">+8.3% ↑</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Avg. Time on Page</div>
                        <div class="stat-value">3:42</div>
                        <div class="stat-change positive">+0:18 ↑</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Favorited</div>
                        <div class="stat-value">10</div>
                        <div class="stat-change positive">+0:18 ↑</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>

    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // --- Tab Switching ---
    function switchTab(tabId, event) {
        if (event) event.preventDefault();
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
        if (event) event.currentTarget.classList.add('active');
    }
    window.switchTab = switchTab;
    // Initialize the first tab as active
    switchTab('upload');

    // --- Image Preview & Upload ---
    const imageInput = document.getElementById('property-images');
    const previewContainer = document.getElementById('image-preview');

    imageInput.addEventListener('change', function(e) {
        previewContainer.innerHTML = '';
        const files = e.target.files;
        const maxFiles = 10;

        if (files.length > maxFiles) {
            alert(`You can only upload up to ${maxFiles} images.`);
            this.value = '';
            return;
        }

        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 5 * 1024 * 1024) { // 5MB
                alert(`File "${files[i].name}" is too large (max 5MB).`);
                this.value = '';
                previewContainer.innerHTML = '';
                return;
            }
            if (!files[i].type.match('image.*')) continue;

            const reader = new FileReader();
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'image-preview';
                previewDiv.style.cssText = 'display: inline-block; position: relative; margin: 5px;';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px;';

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-image';
                removeBtn.innerHTML = '×';
                removeBtn.style.cssText = 'position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;';
                removeBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    previewDiv.remove();
                };

                previewDiv.appendChild(img);
                previewDiv.appendChild(removeBtn);
                previewContainer.appendChild(previewDiv);
            };
            reader.readAsDataURL(files[i]);
        }
    });

    // --- Fixed Drag & Drop and Click for File Upload ---
    const fileUploadArea = document.querySelector('.file-upload');
    
    // Handle click events more specifically
    fileUploadArea.addEventListener('click', function(e) {
        // Only trigger file input if clicking on the upload area itself, not buttons
        if (e.target.tagName !== 'BUTTON' && 
            !e.target.closest('button') && 
            !e.target.closest('.btn')) {
            e.preventDefault();
            imageInput.click();
        }
    });

    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = '#667eea';
        fileUploadArea.style.background = '#f3f4f6';
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = '#d1d5db';
        fileUploadArea.style.background = '#f9fafb';
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.style.borderColor = '#d1d5db';
        fileUploadArea.style.background = '#f9fafb';
        
        if (e.dataTransfer.files.length) {
            imageInput.files = e.dataTransfer.files;
            const changeEvent = new Event('change', { bubbles: true });
            imageInput.dispatchEvent(changeEvent);
        }
    });

    // --- Form Submission Handler ---
    const propertyForm = document.getElementById('property-form');
    propertyForm.addEventListener('submit', function(e) {
        // Allow form to submit normally - don't prevent default
        console.log('Form is being submitted to uploadproperty.php');
        
        // Optional: Show loading state
        const submitButton = propertyForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Adding Property...';
        submitButton.disabled = true;
        
        // Re-enable button after a delay (in case of errors)
        setTimeout(function() {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }, 10000);
    });

    // --- Address Autocomplete ---
    const addressInput = document.getElementById('property-address');
    const suggestionsContainer = document.getElementById('address-suggestions');
    let debounceTimer;

    // Add CSS for suggestions container
    if (suggestionsContainer) {
        suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;
    }

    addressInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();
        if (query.length < 3) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        debounceTimer = setTimeout(() => fetchAddressSuggestions(query), 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!addressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });

    function fetchAddressSuggestions(query) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Nigeria')}&addressdetails=1&limit=5`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    suggestionsContainer.innerHTML = '';
                    data.forEach(item => {
                        const suggestion = document.createElement('div');
                        suggestion.style.cssText = 'padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6;';
                        suggestion.textContent = item.display_name;
                        
                        suggestion.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = '#f9fafb';
                        });
                        suggestion.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = 'white';
                        });
                        suggestion.addEventListener('click', function() {
                            addressInput.value = item.display_name;
                            suggestionsContainer.style.display = 'none';
                        });
                        
                        suggestionsContainer.appendChild(suggestion);
                    });
                    suggestionsContainer.style.display = 'block';
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching address suggestions:', error);
                suggestionsContainer.style.display = 'none';
            });
    }

    // --- Form Reset ---
    window.resetForm = function() {
        propertyForm.reset();
        previewContainer.innerHTML = '';
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
        suggestionsContainer.style.display = 'none';
    };

    // --- Logout ---
    window.logout = function() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    };

    // --- Modal Functions ---
    window.openReportModal = function(propertyId) {
        const modal = document.getElementById('report-modal');
        if (modal) {
            modal.style.display = 'flex';
            // Fetch real data here if needed
            console.log('Opening report for property ID:', propertyId);
        }
    };

    window.closeReportModal = function() {
        const modal = document.getElementById('report-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Close modal when clicking outside
    document.getElementById('report-modal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeReportModal();
        }
    });

    // --- Delete Property ---
    window.deleteProperty = function(propertyId) {
        if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
            window.location.href = `deleteproperty.php?property_id=${propertyId}`;
        }
    };

    // --- Form Validation Enhancement ---
    function validateForm() {
        const requiredFields = propertyForm.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                field.style.borderColor = '#e5e7eb';
            }
        });

        return isValid;
    }

    // Add real-time validation
    propertyForm.addEventListener('input', function(e) {
        if (e.target.hasAttribute('required')) {
            if (e.target.value.trim()) {
                e.target.style.borderColor = '#10b981';
            } else {
                e.target.style.borderColor = '#ef4444';
            }
        }
    });
});
    </script>
</body>
</html>
