<?php
// Start the session
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}
// Include database connection
include("connect.php");

// Handle property approval/rejection via AJAX
if (isset($_POST['action']) && isset($_POST['property_id'])) {
    $property_id = $_POST['property_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE property SET confirmation_status = 'confirmed' WHERE property_id = :property_id");
        $stmt->execute(['property_id' => $property_id]);
        echo json_encode(['success' => true, 'message' => 'Property approved successfully!']);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE property SET confirmation_status = 'rejected' WHERE property_id = :property_id");
        $stmt->execute(['property_id' => $property_id]);
        echo json_encode(['success' => true, 'message' => 'Property rejected successfully!']);
    }
    exit;
}

// Fetch pending properties
$pendingStmt = $pdo->prepare("
    SELECT p.*, (SELECT GROUP_CONCAT(pi.image_url) FROM propery_image pi WHERE pi.propery_id = p.property_id) as image_urls
    FROM property p
    WHERE p.confirmation_status = 'pending'
    ORDER BY p.cretedAt DESC
");
$pendingStmt->execute();
$pendingProperties = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch confirmed properties
$confirmedStmt = $pdo->prepare("
    SELECT p.*, (SELECT GROUP_CONCAT(pi.image_url) FROM propery_image pi WHERE pi.propery_id = p.property_id) as image_urls
    FROM property p
    WHERE p.confirmation_status = 'confirmed'
    ORDER BY p.cretedAt DESC
");
$confirmedStmt->execute();
$confirmedProperties = $confirmedStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all realtors (users with user_type = 'realtor')
$realtorsStmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'realtor' ORDER BY created_at DESC");
$realtorsStmt->execute();
$realtors = $realtorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all buyers (users with user_type = 'buyer')
$buyersStmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'buyer' ORDER BY created_at DESC");
$buyersStmt->execute();
$buyers = $buyersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | DreamHome Real Estate</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background: #f8f9fa;
      min-height: 100vh;
    }
    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }
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
    .sidebar-nav-item {
      padding: 12px 30px;
      cursor: pointer;
      font-weight: 500;
      color: #6b7280;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
      display: block;
    }
    .sidebar-nav-item.active {
      color: #667eea;
      font-weight: 600;
      border-left: 3px solid #667eea;
      background: rgba(102, 126, 234, 0.1);
    }
    .sidebar-nav-item:hover:not(.active) {
      color: #667eea;
      background: rgba(102, 126, 234, 0.05);
    }
    .main-content {
      flex: 1;
      margin-left: 280px;
      padding: 40px;
    }
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
    .welcome-message {
      font-size: 20px;
      font-weight: 600;
      color: #374151;
    }
    .logout-btn {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    }
    .content-area {
      padding: 40px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .card h2 {
      color: #1f2937;
      font-size: 20px;
      margin-bottom: 15px;
    }
    /* Tabs */
    .tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #e5e7eb;
    }
    .tab {
      padding: 12px 20px;
      cursor: pointer;
      font-weight: 500;
      color: #6b7280;
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
    }
    .tab.active {
      color: #667eea;
      border-bottom: 3px solid #667eea;
      font-weight: 600;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    /* Property Cards */
    .property-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .property-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }
    .property-card:hover {
      transform: translateY(-5px);
    }
    .property-image {
      height: 200px;
      overflow: hidden;
      position: relative;
    }
    .property-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .property-card:hover .property-image img {
      transform: scale(1.05);
    }
    .property-price {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 5px 10px;
      border-radius: 8px;
      font-weight: 600;
    }
    .property-details {
      padding: 15px;
    }
    .property-title {
      font-size: 16px;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 8px;
    }
    .property-location {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 10px;
    }
    .property-features {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 10px;
    }
    .feature {
      font-size: 12px;
      color: #6b7280;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .property-actions {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    .btn-approve, .btn-reject {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
    }
    .btn-approve {
      background: #10b981;
      color: white;
    }
    .btn-reject {
      background: #ef4444;
      color: white;
    }
    .success-message {
      background: #d1fae5;
      color: #065f46;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }
    .no-properties, .no-users {
      text-align: center;
      padding: 40px;
      color: #6b7280;
      font-style: italic;
    }
    /* Modal Popup */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 12px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    .modal h3 {
      margin-bottom: 15px;
      color: #1f2937;
    }
    .modal p {
      margin-bottom: 20px;
      color: #6b7280;
    }
    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }
    .modal-btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .modal-confirm {
      background: #667eea;
      color: white;
      border: none;
    }
    .modal-cancel {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #d1d5db;
    }
    /* Section Content */
    .section-content {
      display: none;
    }
    .section-content.active {
      display: block;
    }
    /* Tables */
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }
    th {
      background: #f8f9fa;
      font-weight: 600;
      color: #374151;
      font-size: 14px;
    }
    td {
      color: #6b7280;
      font-size: 14px;
    }
    tr:hover {
      background: rgba(102, 126, 234, 0.05);
    }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    .badge-active {
      background: #10b981;
      color: white;
    }
    .badge-inactive {
      background: #ef4444;
      color: white;
    }

    /* Property Detail Modal */
    #property-detail-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1001;
      justify-content: center;
      align-items: center;
    }
    #property-detail-modal .modal-content {
      background: white;
      max-width: 800px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    #modal-property-thumbnails img {
      width: 80px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      border: 2px solid transparent;
    }
    #modal-property-thumbnails img:hover,
    #modal-property-thumbnails img.active {
      border-color: #667eea;
    }

  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="sidebar">
      <div style="padding: 0 30px;">
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 30px;">DreamHome Admin</div>
        <ul style="list-style: none; padding: 0;">
          <li style="margin-bottom: 15px;">
            <a href="#" class="sidebar-nav-item active" data-section="properties">Properties</a>
          </li>
          <li style="margin-bottom: 15px;">
            <a href="#" class="sidebar-nav-item" data-section="users">Users</a>
          </li>
          
        </ul>
      </div>
    </div>
    <div class="main-content">
      <div class="header">
        <div class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</div>
        <form action="admin-logout.php" method="POST">
          <button type="submit" class="logout-btn">Logout</button>
        </form>

      </div>
      <div class="content-area">
        <!-- Properties Section -->
        <div class="section-content active" id="properties-section">
          <div class="card">
            <h2>Properties</h2>
            <div id="success-message" class="success-message" style="display: none;"></div>
            <div class="tabs">
              <div class="tab active" data-tab="pending">Pending Properties</div>
              <div class="tab" data-tab="confirmed">Confirmed Properties</div>
            </div>
            <!-- Pending Properties Tab -->
            <div class="tab-content active" id="pending">
              <?php if (empty($pendingProperties)): ?>
                <div class="no-properties">No pending properties found.</div>
              <?php else: ?>
                <div class="property-grid">
                  <?php foreach ($pendingProperties as $property): ?>
                    <div class="property-card">
                      <div class="property-image">
                        <?php
                        $image_urls = explode(',', $property['image_urls']);
                        $image = !empty($image_urls[0]) ? htmlspecialchars(trim($image_urls[0])) : 'Img/house1.jpg';
                        ?>
                        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <div class="property-price">₦<?php echo number_format($property['price']); ?></div>
                      </div>
                      <div class="property-details">
                        <div class="property-title"><?php echo htmlspecialchars($property['title']); ?></div>
                        <div class="property-location">
                          <i class="fas fa-map-marker-alt" style="margin-right: 5px; font-size: 12px;"></i>
                          <?php echo htmlspecialchars($property['location']); ?>
                        </div>
                        <div class="property-features">
                          <div class="feature"><i class="fas fa-bed" style="font-size: 12px;"></i> <?php echo $property['bedrooms']; ?></div>
                          <div class="feature"><i class="fas fa-bath" style="font-size: 12px;"></i> <?php echo $property['bathrooms']; ?></div>
                          <div class="feature"><i class="fas fa-vector-square" style="font-size: 12px;"></i> <?php echo number_format($property['Area']); ?> sqft</div>
                        </div>
                        <div class="property-actions">
                          <button class="btn-view" style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;" onclick="openPropertyDetailModal(<?php echo $property['property_id']; ?>)">View</button>
                          <button class="btn-approve" onclick="showConfirmationModal('approve', <?php echo $property['property_id']; ?>)">Approve</button>
                          <button class="btn-reject" onclick="showConfirmationModal('reject', <?php echo $property['property_id']; ?>)">Reject</button>
                        </div>

                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <!-- Confirmed Properties Tab -->
            <div class="tab-content" id="confirmed">
              <?php if (empty($confirmedProperties)): ?>
                <div class="no-properties">No confirmed properties found.</div>
              <?php else: ?>
                <div class="property-grid">
                  <?php foreach ($confirmedProperties as $property): ?>
                    <div class="property-card">
                      <div class="property-image">
                        <?php
                        $image_urls = explode(',', $property['image_urls']);
                        $image = !empty($image_urls[0]) ? htmlspecialchars(trim($image_urls[0])) : 'Img/house1.jpg';
                        ?>
                        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <div class="property-price">₦<?php echo number_format($property['price']); ?></div>
                      </div>
                      <div class="property-details">
                        <div class="property-title"><?php echo htmlspecialchars($property['title']); ?></div>
                        <div class="property-location">
                          <i class="fas fa-map-marker-alt" style="margin-right: 5px; font-size: 12px;"></i>
                          <?php echo htmlspecialchars($property['location']); ?>
                        </div>
                        <div class="property-features">
                          <div class="feature"><i class="fas fa-bed" style="font-size: 12px;"></i> <?php echo $property['bedrooms']; ?></div>
                          <div class="feature"><i class="fas fa-bath" style="font-size: 12px;"></i> <?php echo $property['bathrooms']; ?></div>
                          <div class="feature"><i class="fas fa-vector-square" style="font-size: 12px;"></i> <?php echo number_format($property['Area']); ?> sqft</div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Users Section -->
        <div class="section-content" id="users-section">
          <div class="card">
            <h2>Users</h2>
            <div class="tabs">
              <div class="tab active" data-tab="realtors">Realtors</div>
              <div class="tab" data-tab="buyers">Buyers</div>
            </div>

            <!-- Realtors Tab -->
            <div class="tab-content active" id="realtors">
              <?php if (empty($realtors)): ?>
                <div class="no-users">No realtors found.</div>
              <?php else: ?>
                <div class="table-container">
                  <table>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($realtors as $realtor): ?>
                        <tr>
                          <td><?php echo $realtor['id']; ?></td>
                          <td><?php echo htmlspecialchars($realtor['full_name']); ?></td>
                          <td><?php echo htmlspecialchars($realtor['email']); ?></td>
                          <td><?php echo htmlspecialchars($realtor['phone_number']); ?></td>
                          <td>
                            <span class="badge badge-active">Active</span>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($realtor['created_at'])); ?></td>
                          <td>
                            <button class="btn-view" style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;">View</button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Buyers Tab -->
            <div class="tab-content" id="buyers">
              <?php if (empty($buyers)): ?>
                <div class="no-users">No buyers found.</div>
              <?php else: ?>
                <div class="table-container">
                  <table>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($buyers as $buyer): ?>
                        <tr>
                          <td><?php echo $buyer['id']; ?></td>
                          <td><?php echo htmlspecialchars($buyer['full_name']); ?></td>
                          <td><?php echo htmlspecialchars($buyer['email']); ?></td>
                          <td><?php echo htmlspecialchars($buyer['phone_number']); ?></td>
                          <td>
                            <span class="badge badge-active">Active</span>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($buyer['created_at'])); ?></td>
                          <td>
                            <button class="btn-view" style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;">View</button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmation-modal" class="modal">
    <div class="modal-content">
      <h3 id="modal-title">Confirm Action</h3>
      <p id="modal-message">Are you sure you want to approve this property?</p>
      <div class="modal-buttons">
        <button class="modal-btn modal-confirm" id="confirm-action">Confirm</button>
        <button class="modal-btn modal-cancel" onclick="hideConfirmationModal()">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Property Detail Modal -->
<div id="property-detail-modal" class="modal">
  <div class="modal-content" style="max-width: 800px; padding: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 id="modal-property-title" style="color: #1f2937; font-size: 24px;">Property Details</h2>
      <button onclick="closePropertyDetailModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div>
        <div id="modal-property-image" style="height: 300px; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
          <img id="modal-property-main-image" src="" alt="Property Image" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div id="modal-property-thumbnails" style="display: flex; gap: 10px; margin-bottom: 20px;"></div>
      </div>
      <div>
        <div style="margin-bottom: 15px;">
          <h3 style="color: #1f2937; font-size: 20px; margin-bottom: 10px;">Basic Info</h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
            <div><strong>Price:</strong> <span id="modal-property-price"></span></div>
            <div><strong>Type:</strong> <span id="modal-property-type"></span></div>
            <div><strong>Area:</strong> <span id="modal-property-area"></span> sqft</div>
            <div><strong>Bedrooms:</strong> <span id="modal-property-bedrooms"></span></div>
            <div><strong>Bathrooms:</strong> <span id="modal-property-bathrooms"></span></div>
            <div><strong>Year Built:</strong> <span id="modal-property-year"></span></div>
            <div><strong>Status:</strong> <span id="modal-property-status"></span></div>
            <div><strong>C of O:</strong> <a id="modal-property-c-of-o" href="#" target="_blank" style="color: #667eea;">View Document</a></div>
          </div>
        </div>
        <div style="margin-bottom: 15px;">
          <h3 style="color: #1f2937; font-size: 20px; margin-bottom: 10px;">Location</h3>
          <p id="modal-property-location" style="color: #6b7280; font-size: 14px;"></p>
        </div>
        <div style="margin-bottom: 15px;">
          <h3 style="color: #1f2937; font-size: 20px; margin-bottom: 10px;">Description</h3>
          <p id="modal-property-description" style="color: #6b7280; font-size: 14px; line-height: 1.6;"></p>
        </div>
      </div>
    </div>
    <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
      <h3 style="color: #1f2937; font-size: 20px; margin-bottom: 15px;">Realtor Info</h3>
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <p style="color: #1f2937; font-size: 16px; font-weight: 600; margin-bottom: 5px;" id="modal-realtor-name"></p>
          <p style="color: #6b7280; font-size: 14px; margin-bottom: 5px;" id="modal-realtor-email"></p>
          <p style="color: #6b7280; font-size: 14px;" id="modal-realtor-phone"></p>
        </div>
        <div>
          <button id="verify-realtor-btn" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Verify Realtor</button>
        </div>
      </div>
    </div>
  </div>
</div>


  <script>
    // Open property detail modal
function openPropertyDetailModal(propertyId) {
  fetch(`get_property_details.php?property_id=${propertyId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const property = data.property;
        const realtor = data.realtor;
        // Set property details
        document.getElementById('modal-property-title').textContent = property.title;
        document.getElementById('modal-property-price').textContent = `₦${parseInt(property.price).toLocaleString()}`;
        document.getElementById('modal-property-type').textContent = property.property_type;
        document.getElementById('modal-property-area').textContent = parseInt(property.Area).toLocaleString();
        document.getElementById('modal-property-bedrooms').textContent = property.bedrooms;
        document.getElementById('modal-property-bathrooms').textContent = property.bathrooms;
        document.getElementById('modal-property-year').textContent = property.year;
        document.getElementById('modal-property-status').textContent = property.status;
        document.getElementById('modal-property-location').textContent = property.location;
        document.getElementById('modal-property-description').textContent = property.description;
        document.getElementById('modal-property-c-of-o').href = property.c_of_o_url || '#';
        if (!property.c_of_o_url) {
          document.getElementById('modal-property-c-of-o').style.display = 'none';
        } else {
          document.getElementById('modal-property-c-of-o').style.display = 'inline';
        }
        // Set main image
        const mainImage = document.getElementById('modal-property-main-image');
        mainImage.src = property.image_urls ? property.image_urls.split(',')[0] : '';
        // Set thumbnails
        const thumbnailsContainer = document.getElementById('modal-property-thumbnails');
        thumbnailsContainer.innerHTML = '';
        if (property.image_urls) {
          property.image_urls.split(',').forEach((url, index) => {
            const img = document.createElement('img');
            img.src = url;
            img.alt = `Thumbnail ${index + 1}`;
            img.onclick = () => {
              mainImage.src = url;
              document.querySelectorAll('#modal-property-thumbnails img').forEach(i => i.classList.remove('active'));
              img.classList.add('active');
            };
            thumbnailsContainer.appendChild(img);
          });
        }
        // Set realtor info
        document.getElementById('modal-realtor-name').textContent = realtor.full_name;
        document.getElementById('modal-realtor-email').textContent = realtor.email;
        document.getElementById('modal-realtor-phone').textContent = realtor.phone_number;
        // Set verify button
        const verifyBtn = document.getElementById('verify-realtor-btn');
        verifyBtn.onclick = () => verifyRealtor(realtor.id);
        // Show modal
        document.getElementById('property-detail-modal').style.display = 'flex';
      }
    })
    .catch(error => console.error('Error:', error));
}

// Close property detail modal
function closePropertyDetailModal() {
  document.getElementById('property-detail-modal').style.display = 'none';
}

// Verify realtor
function verifyRealtor(realtorId) {
  if (confirm('Are you sure you want to verify this realtor?')) {
    fetch('verify_realtor.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `realtor_id=${realtorId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Realtor verified successfully!');
        document.getElementById('verify-realtor-btn').textContent = 'Verified ✓';
        document.getElementById('verify-realtor-btn').style.background = '#6b7280';
        document.getElementById('verify-realtor-btn').disabled = true;
      } else {
        alert('Failed to verify realtor.');
      }
    })
    .catch(error => console.error('Error:', error));
  }
}


    // Section switching
    document.querySelectorAll('.sidebar-nav-item').forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.sidebar-nav-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active'));

        this.classList.add('active');
        document.getElementById(this.dataset.section + '-section').classList.add('active');
      });
    });

    // Inner tab switching for properties
    document.querySelectorAll('#properties-section .tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('#properties-section .tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('#properties-section .tab-content').forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
      });
    });

    // Inner tab switching for users
    document.querySelectorAll('#users-section .tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('#users-section .tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('#users-section .tab-content').forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
      });
    });

    // Modal variables
    let currentAction = '';
    let currentPropertyId = '';

    // Show confirmation modal
    function showConfirmationModal(action, propertyId) {
      currentAction = action;
      currentPropertyId = propertyId;

      const modal = document.getElementById('confirmation-modal');
      const title = document.getElementById('modal-title');
      const message = document.getElementById('modal-message');

      if (action === 'approve') {
        title.textContent = 'Approve Property';
        message.textContent = 'Are you sure you want to approve this property?';
      } else {
        title.textContent = 'Reject Property';
        message.textContent = 'Are you sure you want to reject this property?';
      }

      modal.style.display = 'flex';
    }

    // Hide confirmation modal
    function hideConfirmationModal() {
      document.getElementById('confirmation-modal').style.display = 'none';
    }

    // Handle confirmation
    document.getElementById('confirm-action').addEventListener('click', function() {
      const formData = new FormData();
      formData.append('action', currentAction);
      formData.append('property_id', currentPropertyId);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('success-message').textContent = data.message;
          document.getElementById('success-message').style.display = 'block';

          // Refresh the page after 2 seconds
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });

      hideConfirmationModal();
    });
  </script>
</body>
</html>
