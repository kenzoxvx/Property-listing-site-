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
        
    // Fetch properties for the logged-in user
    $user_id = $_SESSION['realtor_id'];
    $query = "SELECT * FROM `property` WHERE `user_id` = :user_id ORDER BY `cretedAt` DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenzo Homes - Realtor Dashboard</title>
    <link rel="stylesheet" href="styles/realtorpage.css">
</head>
<body>
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

                    <li class="nav-item">
                        <a href="realtor_messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'realtor_messages.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">💬</span>
                            Messages
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <!-- Main Content -->
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
                        <div class="user-avatar">KZ</div>
                        <div class="user-details">
                            <h3><?= $user['full_name'] ?></h3>
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
                            <form id="property-form" method="POST" enctype="multipart/form-data">
                                <div id="success-alert" class="alert alert-success" style="display: none;">
                                    <span>✓</span>
                                    <div>Property has been added successfully!</div>
                                </div>
                                <div id="error-alert" class="alert alert-error" style="display: none;">
                                    <span>⚠️</span>
                                    <div id="error-message">Error uploading property!</div>
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
                                    <label>Certificate of Occupancy / Deed of Property (PDF or Image)</label>
                                    <div class="file-upload" id="c-of-o-upload-area">
                                        <div class="file-upload-icon">📄</div>
                                        <div class="file-upload-text">Click to select document or drag and drop</div>
                                        <div class="file-upload-info">Upload PDF or Image (Max size: 5MB)</div>
                                        <input type="file" id="c-of-o-document" accept="image/*,.pdf" name="c_of_o_document">
                                    </div>
                                    <div class="image-preview-container" id="c-of-o-preview"></div>
                                </div>

                                
                                <div class="form-group">
                                    <label>Property Images</label>
                                    <div class="file-upload" id="file-upload-area">
                                        <div class="file-upload-icon">📷</div>
                                        <div class="file-upload-text">Click to select images or drag and drop</div>
                                        <div class="file-upload-info">Upload up to 10 images (Max size: 5MB each)</div>
                                        <input type="file" id="property-images" accept="image/*" multiple name="images[]">
                                    </div>
                                    <div class="image-preview-container" id="image-preview"></div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-outline" onclick="resetForm()">Reset</button>
                                    <button type="submit" class="btn btn-primary" name="addproperty">Add Property</button>
                                </div>

                                <input type="hidden" name="confirmation_status" value="pending">

                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- View Properties Tab -->
                <div id="properties" class="tab-content">
    <?php if (empty($properties)): ?>
        <div class="no-properties">
            <div class="no-properties-icon">🏠</div>
            <h3>No Properties Uploaded Yet</h3>
            <p>You haven't uploaded any properties. Start by adding your first property!</p>
        </div>
    <?php else: ?>
        <div class="house-grid">
            <?php foreach ($properties as $property): ?>
                <div class="house-card">
                    <div class="house-image">
                        <?php
                        // Fetch the first image for the property
                        $imageQuery = "SELECT * FROM `propery_image` WHERE `propery_id` = :property_id LIMIT 1";
                        $imageStmt = $pdo->prepare($imageQuery);
                        $imageStmt->execute(['property_id' => $property['property_id']]);
                        $image = $imageStmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <img src="<?php echo htmlspecialchars($image['image_url'] ?? 'placeholder-image-url'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <div class="house-price">₦<?php echo number_format($property['price']); ?></div>
                    </div>
                    <div class="house-content">
                        <div class="badge <?php echo ($property['confirmation_status'] == 'confirmed') ? 'badge-success' : 'badge-primary'; ?>">
                            <?php echo ucfirst($property['confirmation_status']); ?>
                        </div>
                        <h3 class="house-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                        <p class="house-address"><?php echo htmlspecialchars($property['location']); ?></p>
                        <p class="house-desc"><?php echo htmlspecialchars(substr($property['description'], 0, 100)) . (strlen($property['description']) > 100 ? '...' : ''); ?></p>
                        <div class="house-features">
                            <div class="house-feature">🛏️ <?php echo $property['bedrooms']; ?> Bedrooms</div>
                            <div class="house-feature">🚿 <?php echo $property['bathrooms']; ?> Bathrooms</div>
                            <div class="house-feature">📐 <?php echo $property['Area']; ?> sq ft</div>
                        </div>
                        <div class="house-actions">
                            <span class="badge badge-primary"><?php echo ucfirst($property['status']); ?></span>
                            <a href="edit-property.php?id=<?php echo $property['property_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
                /**
         * Shows a snackbar notification
         * @param {string} message - The message to display
         * @param {string} type - 'success' or 'error'
         */
        function showSnackbar(message, type = 'success') {
            const snackbar = document.getElementById('snackbar');
            snackbar.textContent = message;
            snackbar.className = type;
            snackbar.classList.add('show');
            setTimeout(() => {
                snackbar.classList.remove('show');
            }, 3000);
        }


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

        
        const cOfOInput = document.getElementById('c-of-o-document');
const cOfOPreviewContainer = document.getElementById('c-of-o-preview');
const cOfOUploadArea = document.getElementById('c-of-o-upload-area');

// Make the entire upload area clickable
cOfOUploadArea.addEventListener('click', function(e) {
    if (e.target.tagName !== 'BUTTON' && !e.target.closest('.remove-image')) {
        cOfOInput.click();
    }
});

// Handle file selection
cOfOInput.addEventListener('change', function(e) {
    handleCOfOFile(this.files);
});

// Drag and drop handling
cOfOUploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.add('dragover');
});
cOfOUploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('dragover');
});
cOfOUploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        handleCOfOFile(e.dataTransfer.files);
    }
});

// Function to handle the selected file
function handleCOfOFile(files) {
    cOfOPreviewContainer.innerHTML = '';
    const maxSize = 5 * 1024 * 1024; // 5MB
    const file = files[0];
    if (!file) return;

    if (file.size > maxSize) {
        alert(`File "${file.name}" is too large (max 5MB).`);
        return;
    }

    if (!file.type.match('image.*') && file.type !== 'application/pdf') {
        alert(`File "${file.name}" is not an image or PDF.`);
        return;
    }

    const previewDiv = document.createElement('div');
    previewDiv.className = 'image-preview';

    if (file.type.match('image.*')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            previewDiv.appendChild(img);
        };
        reader.readAsDataURL(file);
    } else {
        const icon = document.createElement('div');
        icon.style.fontSize = '48px';
        icon.style.textAlign = 'center';
        icon.textContent = '📄';
        previewDiv.appendChild(icon);
    }

    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-image';
    removeBtn.innerHTML = '×';
    removeBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        previewDiv.remove();
        cOfOInput.value = '';
    };

    previewDiv.appendChild(removeBtn);
    cOfOPreviewContainer.appendChild(previewDiv);
}

        
        
        // --- Image Upload Improvements ---
        const imageInput = document.getElementById('property-images');
        const previewContainer = document.getElementById('image-preview');
        const fileUploadArea = document.getElementById('file-upload-area');
        // Make the entire upload area clickable to trigger file input
        fileUploadArea.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON' && !e.target.closest('.remove-image')) {
                imageInput.click();
            }
        });
        // Handle file selection
        imageInput.addEventListener('change', function(e) {
            handleFiles(this.files);
        });
        // Improved drag and drop handling
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFiles(e.dataTransfer.files);
            }
        });
        // Function to handle the selected files
        function handleFiles(files) {
            previewContainer.innerHTML = '';
            const maxFiles = 10;
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (files.length > maxFiles) {
                alert(`You can only upload up to ${maxFiles} images.`);
                imageInput.value = '';
                return;
            }
            // Process each file
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                // Check file size
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large (max 5MB).`);
                    continue;
                }
                // Check if it's an image
                if (!file.type.match('image.*')) {
                    alert(`File "${file.name}" is not an image.`);
                    continue;
                }
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'image-preview';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-image';
                    removeBtn.innerHTML = '×';
                    removeBtn.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        previewDiv.remove();
                        const dataTransfer = new DataTransfer();
                        const currentFiles = imageInput.files;
                        for (let j = 0; j < currentFiles.length; j++) {
                            if (j !== i) {
                                dataTransfer.items.add(currentFiles[j]);
                            }
                        }
                        imageInput.files = dataTransfer.files;
                    };
                    previewDiv.appendChild(img);
                    previewDiv.appendChild(removeBtn);
                    previewContainer.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            }
        }

        // --- Form Submission Handler ---
        const propertyForm = document.getElementById('property-form');
        propertyForm.addEventListener('submit', function(e) {
            e.preventDefault(); // <-- This is CRITICAL
            const formData = new FormData(propertyForm);
            const submitButton = propertyForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            // Hide any previous alerts
            
            submitButton.textContent = 'Adding Property...';
            submitButton.disabled = true;
            fetch('uploadproperty.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    // Show success message
                    showSnackbar("success", data.success);
                    // Optionally, reset the form
                    resetForm();
                } else {
                    // Show error message
                    showSnackbar("error", data.message);
                    if (data.message) {
                        showSnackbar("error", data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showSnackbar()
            })
            .finally(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });

        // --- Address Autocomplete ---
        const addressInput = document.getElementById('property-address');
        const suggestionsContainer = document.getElementById('address-suggestions');
        let debounceTimer;
        addressInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            if (query.length < 3) {
                suggestionsContainer.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => fetchAddressSuggestions(query), 300);
        });
        function fetchAddressSuggestions(query) {
            const url = `proxy.php?q=${encodeURIComponent(query + ', Nigeria')}`;
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.length > 0) {
                        suggestionsContainer.innerHTML = '';
                        data.forEach(item => {
                            const suggestion = document.createElement('div');
                            suggestion.className = 'address-suggestion';
                            suggestion.textContent = item.display_name;
                            suggestion.style.cssText = 'padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f3f4f6;';
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
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!addressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
        // --- Form Reset ---
        window.resetForm = function() {
            propertyForm.reset();
            previewContainer.innerHTML = '';
            document.getElementById('success-alert').style.display = 'none';
            document.getElementById('error-alert').style.display = 'none';
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
    });
</script>
<div id="snackbar"></div>

</body>
</html>
