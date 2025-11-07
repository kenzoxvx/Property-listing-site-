<?php
session_start();
include("connect.php");
$buyer_id = $_SESSION['buyer_id'];
// Get the property ID from the query parameter
$property_id = isset($_GET['property_id']) ? $_GET['property_id'] : null;

if (!$property_id) {
    // Handle error: Property ID is not provided
    echo "<p>Property ID not provided.</p>";
    exit;
}

if (!isset($buyer_id)) {
    header("Location: buyersregistration.php");
}

// Fetch property contact information
$contact_stmt = $pdo->prepare("
    SELECT contact 
    FROM property 
    WHERE property_id = ?
");
$contact_stmt->execute([$property_id]);
$contact_info = $contact_stmt->fetch(PDO::FETCH_ASSOC);

$phone_number = $contact_info['contact'] ?? '';
$contact_name = 'Property Agent'; // Default name since we don't have a name field

// Fetch property details from the database
$stmt = $pdo->prepare("
    SELECT p.title, p.location, p.bedrooms, p.bathrooms, p.Area,
           p.description, p.price, p.property_type, p.year,
           GROUP_CONCAT(pi.image_url) as image_urls
    FROM property p
    LEFT JOIN propery_image pi ON p.property_id = pi.propery_id
    WHERE p.property_id = ?
    GROUP BY p.property_id
");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    // Handle error: Property not found
    echo "<p>Property not found.</p>";
    exit;
}

// Extract property details
$title = htmlspecialchars($property['title']);
$location = htmlspecialchars($property['location']);
$bedrooms = $property['bedrooms'];
$bathrooms = $property['bathrooms'];
$area = number_format($property['Area']);
$description = htmlspecialchars($property['description']);
$price = number_format($property['price']);
$property_type = htmlspecialchars($property['property_type']);
$year = $property['year'];
$image_urls = explode(',', $property['image_urls']);

// Fetch existing notices for this property
$notices_stmt = $pdo->prepare("
    SELECT n.notice_title, n.notice_content, n.created_at, u.full_name, n.notice_type
    FROM community_notices n
    JOIN users u ON n.user_id = u.id
    WHERE n.property_id = ?
    ORDER BY n.created_at DESC
");
$notices_stmt->execute([$property_id]);
$notices = $notices_stmt->fetchAll(PDO::FETCH_ASSOC);

// If the table doesn't exist yet, we'll handle it gracefully
if (!$notices) {
    $notices = array(); // Initialize as empty array
}

// Add this after your property query
// Fetch like information
$like_stmt = $pdo->prepare("
    SELECT COUNT(*) as like_count,
           EXISTS(SELECT 1 FROM property_likes WHERE property_id = ? AND buyer_id = ?) as is_liked
    FROM property_likes
    WHERE property_id = ?
");
$like_stmt->execute([$property_id, $buyer_id, $property_id]);
$like_info = $like_stmt->fetch(PDO::FETCH_ASSOC);

$like_count = $like_info['like_count'];
$is_liked = $like_info['is_liked'];

// Add this after your property query
// Fetch save information
$save_stmt = $pdo->prepare("
    SELECT EXISTS(SELECT 1 FROM saved_properties WHERE property_id = ? AND buyer_id = ?) as is_saved
    FROM saved_properties
    WHERE property_id = ?
");
$save_stmt->execute([$property_id, $buyer_id, $property_id]);
$save_info = $save_stmt->fetch(PDO::FETCH_ASSOC);

$is_saved = $save_info['is_saved'] ?? "";

function isPropertySaved($property_id, $pdo) {
    if (!isset($_SESSION['buyer_id'])) {
        return false;
    }
    $buyer_id = $_SESSION['buyer_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_properties WHERE property_id = ? AND buyer_id = ?");
    $stmt->execute([$property_id, $buyer_id]);
    return $stmt->fetchColumn() > 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title; ?> - KENZO HOME Listing</title>
  <link rel="stylesheet" href="styles/propertyview.css">
  <style>
    /* (Your existing CSS styles here) */
 .notice-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #eaeaea;
}

.notice-inputs {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notice-title-input, .notice-content-input {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
}

.notice-content-input {
    resize: vertical;
    min-height: 80px;
}

.notice-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notice-type-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.type-label {
    font-size: 14px;
    color: #666;
}

#notice-type {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.notice-submit {
    background-color: #4a6ee0;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 10px 20px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notice-submit:hover:not(:disabled) {
    background-color: #3a5ec0;
}

.notice-submit:disabled {
    background-color: #a0b0e0;
    cursor: not-allowed;
}

.notices-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.notice {
    display: flex;
    gap: 15px;
    padding: 20px;
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #4a6ee0;
}

.notice.general {
    border-left-color: #4a6ee0;
}

.notice.question {
    border-left-color: #ff9800;
}

.notice.alert {
    border-left-color: #f44336;
}

.notice.event {
    border-left-color: #4caf50;
}

.notice-content {
    flex: 1;
}

.notice-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.notice-author {
    font-weight: 600;
    color: #333;
}

.notice-type-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.notice-type-badge.general {
    background-color: #e8f0fe;
    color: #4a6ee0;
}

.notice-type-badge.question {
    background-color: #fff3e0;
    color: #ff9800;
}

.notice-type-badge.alert {
    background-color: #ffebee;
    color: #f44336;
}

.notice-type-badge.event {
    background-color: #e8f5e9;
    color: #4caf50;
}

.notice-date {
    font-size: 12px;
    color: #999;
    margin-left: auto;
}

.notice-title {
    font-size: 18px;
    margin-bottom: 8px;
    color: #333;
}

.notice-text {
    color: #555;
    line-height: 1.5;
    white-space: pre-line;
}

.no-notices {
    text-align: center;
    color: #777;
    font-style: italic;
    padding: 30px;
}

/* WhatsApp Button Styles */
.whatsapp-btn {
    background-color: #25D366;
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s;
}

.whatsapp-btn:hover {
    background-color: #128C7E;
    color: white;
}

.floating-whatsapp {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.whatsapp-float {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background-color: #25D366;
    color: white;
    border-radius: 50%;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    text-decoration: none;
    font-size: 24px;
    transition: all 0.3s;
}

.whatsapp-float:hover {
    background-color: #128C7E;
    transform: scale(1.1);
    color: white;
}

.engagement-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

/* Realtor Info Styles */
.realtor-info {
  display: flex;
  align-items: center;
  gap: 15px;
  margin: 20px 0;
  padding: 15px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #eaeaea;
}

.realtor-avatar {
  flex-shrink: 0;
}

.realtor-avatar .avatar {
  width: 50px;
  height: 50px;
  font-size: 20px;
  background: linear-gradient(135deg, #4a6ee0, #6a8efd);
}

.realtor-details {
  flex: 1;
}

.realtor-name {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 5px;
}

.verification-status {
  font-size: 14px;
}

.verified-badge {
  color: #28a745;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 5px;
}

.verified-badge::before {
  content: "✓";
  background: #28a745;
  color: white;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.not-verified-badge {
  color: #dc3545;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 5px;
}

.not-verified-badge::before {
  content: "✗";
  background: #dc3545;
  color: white;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.contact-realtor-btn {
  background: #25D366;
  color: white;
  padding: 8px 15px;
  border-radius: 20px;
  transition: all 0.3s;
  text-decoration: none;
}

.contact-realtor-btn:hover {
  background: #128C7E;
  transform: translateY(-2px);

}

/* Google Maps Button */
.map-btn {
  background: #4285F4;
  color: white;
  padding: 4px;
  border-radius: 4px;
  text-decoration: none;
}


.map-btn:hover {
  background: #3367D6;
  transform: translateY(-2px);
}

  /* Like Button */
    #like-btn {
      transition: all 0.3s ease;
      cursor: pointer;
    }

    #like-btn.active {
      color: #e74c3c;
      animation: pulse 0.5s;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }

    /* Save Button */
    #save-btn {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    #save-btn.active {
        color: #4a6ee0;
        animation: pulse 0.5s;
    }



  </style>
</head>
<body>
  <div class="container">
    <nav class="navbar">
      <div class="logo">KENZOHOMES</div>
      <div class="nav-links">
        <a href="#property">Property</a>
        <a href="#details">Property details</a>
        <a href="#comments">Comments</a>
        
      </div>
    </nav>

    <div class="property-container" id="property">
      <!-- Carousel Implementation -->
      <div class="carousel-container">
        <div class="carousel" id="property-carousel">
          <?php foreach ($image_urls as $image_url): ?>
            <div class="carousel-slide">
              <img src="<?php echo htmlspecialchars(trim($image_url)); ?>" alt="<?php echo $title; ?>" class="carousel-image">
            </div>
          <?php endforeach; ?>
        </div>

        <button class="carousel-arrow carousel-prev" id="prev-btn">&lt;</button>
        <button class="carousel-arrow carousel-next" id="next-btn">&gt;</button>

        <div class="carousel-indicators" id="carousel-dots">
          <!-- Dots will be added by JavaScript -->
        </div>
      </div>

      <div class="property-details" id="details">
        <div class="property-price">₦<?php echo $price; ?></div>
        <div class="property-address"><?php echo $location; ?></div>

        <div class="property-stats">
          <div class="stat">
            <div class="stat-value"><?php echo $bedrooms; ?></div>
            <div class="stat-label">Bedrooms</div>
          </div>
          <div class="stat">
            <div class="stat-value"><?php echo $bathrooms; ?></div>
            <div class="stat-label">Bathrooms</div>
          </div>
          <div class="stat">
            <div class="stat-value"><?php echo $area; ?></div>
            <div class="stat-label">Sq. Ft.</div>
          </div>
          <div class="stat">
            <div class="stat-value"><?php echo $year; ?></div>
            <div class="stat-label">Year Built</div>
          </div>
        </div>

        <div class="realtor-info">
            <div class="realtor-avatar">
              <?php
              // Fetch realtor info
              $realtor_stmt = $pdo->prepare("SELECT full_name, is_verified FROM users WHERE id = (SELECT user_id FROM property WHERE property_id = ?)");
              $realtor_stmt->execute([$property_id]);
              $realtor = $realtor_stmt->fetch(PDO::FETCH_ASSOC);
              $realtor_name = htmlspecialchars($realtor['full_name'] ?? 'Unknown Realtor');
              $is_verified = $realtor['is_verified'] ?? 0;
              // Get initials
              $name_parts = explode(' ', $realtor_name);
              $initials = (isset($name_parts[0]) ? substr($name_parts[0], 0, 1) : '') .
                          (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : '');
              ?>
              <div class="avatar"><?php echo strtoupper($initials); ?></div>
            </div>
            <div class="realtor-details">
              <h4 class="realtor-name"><?php echo $realtor_name; ?></h4>
              <div class="verification-status">
                <?php if ($is_verified): ?>
                  <span class="verified-badge">✓ Verified Realtor</span>
                <?php else: ?>
                  <span class="not-verified-badge">Not Verified</span>
                <?php endif; ?>
              </div>
            </div>
          </div>


        <div class="property-description">
          <?php echo $description; ?>
        </div>

        <div class="property-features">
          <!-- Add property features dynamically if available in the database -->
          <div class="feature">
            <div class="feature-icon">✓</div>
            <span>Smart Home System</span>
          </div>
          <div class="feature">
            <div class="feature-icon">✓</div>
            <span>Heated Floors</span>
          </div>
          <!-- Add more features as needed -->
        </div>
      </div>

      <div class="engagement" id="comments">
        <div class="engagement-actions">
        <button class="action-btn <?php echo $is_liked ? 'active' : ''; ?>" id="like-btn">
            <?php echo $is_liked ? '❤️' : '♡'; ?>
            <span id="like-count"><?php echo $like_count; ?></span> Likes
        </button>

          
          <button class="action-btn <?php echo $is_saved ? 'active' : ''; ?>" id="save-btn">
              <?php echo $is_saved ? '🔖 Saved' : '🔖 Save'; ?>
          </button>

          <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $phone_number); ?>"
            class="action-btn contact-realtor-btn">
            📞 Contact Realtor
          </a>

          <a
            href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($location); ?>"
            target="_blank"
            class="action-btn map-btn"
          >
            🗺️ View on Map
          </a>



        </div>
        <div class="engagement-meta">
          Listed 2 days ago
        </div>
      </div>

      <div class="notices-section" id="notices">
    <h3>Community Notice Board (<?php echo count($notices); ?>)</h3>
    
    <?php if (isset($_SESSION['buyer_id'])): ?>
    <div class="notice-form" id="notice-form">
        <div class="notice-inputs">
            <input type="text" class="notice-title-input" id="notice-title-input" placeholder="Notice title..." maxlength="100">
            <textarea class="notice-content-input" id="notice-content-input" placeholder="Write your notice here..." rows="3"></textarea>
        </div>
        <div class="notice-options">
            <div class="notice-type-selector">
                <span class="type-label">Notice type:</span>
                <select id="notice-type">
                    <option value="general">General</option>
                    <option value="question">Question</option>
                    <option value="alert">Alert</option>
                    <option value="event">Event</option>
                </select>
            </div>
            <button class="notice-submit" id="notice-submit">Post Notice</button>
        </div>
    </div>
    <?php else: ?>
    <p>Please <a href="login.php">login</a> to post a notice.</p>
    <?php endif; ?>

    <div class="notices-list">
        <?php if (!empty($notices)): ?>
            <?php foreach ($notices as $notice): ?>
            <div class="notice <?php echo $notice['notice_type']; ?>">
                <div class="avatar">
                    <?php 
                    $name_parts = explode(' ', $notice['full_name']);
                    echo substr($name_parts[0] ?? '', 0, 1) . substr($name_parts[1] ?? '', 0, 1); 
                    ?>
                </div>
                <div class="notice-content">
                    <div class="notice-header">
                        <div class="notice-author"><?php echo htmlspecialchars($notice['full_name']); ?></div>
                        <div class="notice-type-badge <?php echo $notice['notice_type']; ?>">
                            <?php echo ucfirst($notice['notice_type']); ?>
                        </div>
                        <div class="notice-date"><?php echo date('F j, Y \a\t g:i A', strtotime($notice['created_at'])); ?></div>
                    </div>
                    <h4 class="notice-title"><?php echo htmlspecialchars($notice['notice_title']); ?></h4>
                    <div class="notice-text"><?php echo nl2br(htmlspecialchars($notice['notice_content'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-notices">No notices yet. Be the first to post one!</p>
        <?php endif; ?>
    </div>

    <!-- New Link to Messages Page -->
    <div class="floating-whatsapp">
      <a href="messages.php?realtor_id=<?php echo $realtor_id; ?>" class="whatsapp-float" target="_blank">
        <i class="fas fa-comments"></i> 💬
      </a>
    </div>


    




</div>
    </div>
  </div>

  <script>
    // Existing JavaScript code for carousel, comments, etc.
    document.addEventListener('DOMContentLoaded', function() {
      const carousel = document.getElementById('property-carousel');
      const slides = carousel.querySelectorAll('.carousel-slide');
      const prevBtn = document.getElementById('prev-btn');
      const nextBtn = document.getElementById('next-btn');
      const dotsContainer = document.getElementById('carousel-dots');

      let currentIndex = 0;
      const totalSlides = slides.length;

      // Create indicator dots
      for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.classList.add('carousel-dot');
        if (i === 0) dot.classList.add('active');
        dot.setAttribute('data-index', i);
        dot.addEventListener('click', () => {
          goToSlide(i);
        });
        dotsContainer.appendChild(dot);
      }

      // Update the carousel display
      function updateCarousel() {
        carousel.style.transform = `translateX(-${currentIndex * 100}%)`;

        // Update active dot
        document.querySelectorAll('.carousel-dot').forEach((dot, index) => {
          if (index === currentIndex) {
            dot.classList.add('active');
          } else {
            dot.classList.remove('active');
          }
        });
      }

      // Go to a specific slide
      function goToSlide(index) {
        currentIndex = index;
        updateCarousel();
      }

      // Previous slide
      prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
      });

      // Next slide
      nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
      });

      // Auto-advance every 5 seconds
      let interval = setInterval(() => {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
      }, 5000);

      // Pause auto-advance when hovering over carousel
      carousel.parentElement.addEventListener('mouseenter', () => {
        clearInterval(interval);
      });

      // Resume auto-advance when mouse leaves carousel
      carousel.parentElement.addEventListener('mouseleave', () => {
        interval = setInterval(() => {
          currentIndex = (currentIndex + 1) % totalSlides;
          updateCarousel();
        }, 5000);
      });

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
          currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
          updateCarousel();
        } else if (e.key === 'ArrowRight') {
          currentIndex = (currentIndex + 1) % totalSlides;
          updateCarousel();
        }
      });
    });

    // Existing JavaScript code for star rating, comments, etc.
    let userRating = 0;
    const stars = document.querySelectorAll('.star');
    const commentInput = document.getElementById('comment-input');
    const commentSubmit = document.getElementById('comment-submit');

    // Set up star rating behavior
    stars.forEach(star => {
      // Hover effect
      star.addEventListener('mouseover', function() {
        const rating = this.getAttribute('data-rating');
        highlightStars(rating);
      });

      // Reset on mouseout if no rating is selected
      star.addEventListener('mouseout', function() {
        if (userRating === 0) {
          resetStars();
        } else {
          highlightStars(userRating);
        }
      });

      // Click to set rating
      star.addEventListener('click', function() {
        userRating = this.getAttribute('data-rating');
        highlightStars(userRating);
        validateForm();
      });
    });

    // Function to highlight stars up to a certain rating
    function highlightStars(rating) {
      stars.forEach(star => {
        if (star.getAttribute('data-rating') <= rating) {
          star.classList.add('active');
        } else {
          star.classList.remove('active');
        }
      });
    }

    // Function to reset star highlights
    function resetStars() {
      stars.forEach(star => {
        star.classList.remove('active');
      });
    }

    // Notice board functionality
document.addEventListener('DOMContentLoaded', function() {
    const noticeTitleInput = document.getElementById('notice-title-input');
    const noticeContentInput = document.getElementById('notice-content-input');
    const noticeTypeSelect = document.getElementById('notice-type');
    const noticeSubmit = document.getElementById('notice-submit');
    
    // Validate notice form
    function validateNoticeForm() {
        const titleValid = noticeTitleInput.value.trim() !== '';
        const contentValid = noticeContentInput.value.trim() !== '';
        noticeSubmit.disabled = !(titleValid && contentValid);
    }
    
    noticeTitleInput.addEventListener('input', validateNoticeForm);
    noticeContentInput.addEventListener('input', validateNoticeForm);
    
    // Post notice functionality
    noticeSubmit.addEventListener('click', function() {
        postNotice();
    });
    
    function postNotice() {
        const noticeTitle = noticeTitleInput.value.trim();
        const noticeContent = noticeContentInput.value.trim();
        const noticeType = noticeTypeSelect.value;
        const propertyId = <?php echo $property_id; ?>;
        const userId = <?php echo $_SESSION['buyer_id'] ?? 'null'; ?>;
        
        if (noticeTitle !== '' && noticeContent !== '' && userId) {
            // Disable button during submission
            noticeSubmit.disabled = true;
            noticeSubmit.textContent = 'Posting...';
            
            // Create FormData object
            const formData = new FormData();
            formData.append('property_id', propertyId);
            formData.append('notice_title', noticeTitle);
            formData.append('notice_content', noticeContent);
            formData.append('notice_type', noticeType);
            formData.append('user_id', userId);
            
            // Send AJAX request
            fetch('submit_notice.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create new notice element
                    const noticesList = document.querySelector('.notices-list');
                    const newNotice = document.createElement('div');
                    newNotice.className = `notice ${noticeType}`;
                    
                    // Get initials from session
                    const nameParts = "<?php echo $_SESSION['full_name'] ?? ''; ?>".split(' ');
                    const initials = (nameParts[0] ? nameParts[0].charAt(0) : '') + 
                                   (nameParts[1] ? nameParts[1].charAt(0) : '');
                    
                    newNotice.innerHTML = `
                        <div class="avatar">${initials}</div>
                        <div class="notice-content">
                            <div class="notice-header">
                                <div class="notice-author">You</div>
                                <div class="notice-type-badge ${noticeType}">${noticeType.charAt(0).toUpperCase() + noticeType.slice(1)}</div>
                                <div class="notice-date">Just now</div>
                            </div>
                            <h4 class="notice-title">${noticeTitle}</h4>
                            <div class="notice-text">${noticeContent.replace(/\n/g, '<br>')}</div>
                        </div>
                    `;
                    
                    noticesList.prepend(newNotice);
                    
                    // Reset form
                    noticeTitleInput.value = '';
                    noticeContentInput.value = '';
                    noticeTypeSelect.value = 'general';
                    noticeSubmit.disabled = true;
                    noticeSubmit.textContent = 'Post Notice';
                    
                    // Update notice count
                    const noticeCount = document.querySelector('.notices-section h3');
                    const currentCount = parseInt(noticeCount.textContent.match(/\d+/)[0]);
                    noticeCount.textContent = `Community Notice Board (${currentCount + 1})`;
                } else {
                    alert('Error: ' + data.message);
                    noticeSubmit.disabled = false;
                    noticeSubmit.textContent = 'Post Notice';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while posting your notice.');
                noticeSubmit.disabled = false;
                noticeSubmit.textContent = 'Post Notice';
            });
        }
    }
});

    


document.getElementById('save-btn').addEventListener('click', function() {
    const propertyId = <?php echo $property_id; ?>;
    const saveBtn = this;

    fetch('save_property.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'property_id=' + propertyId,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.action === 'saved') {
            saveBtn.classList.add('active');
            saveBtn.innerHTML = '🔖 Saved';
        } else {
            saveBtn.classList.remove('active');
            saveBtn.innerHTML = '🔖 Save';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

// Like Button Functionality
// Like Button Functionality
document.getElementById('like-btn').addEventListener('click', function() {
    const propertyId = <?php echo $property_id; ?>;
    const likeBtn = this;
    const likeCountSpan = document.getElementById('like-count');

    fetch('like_property.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'property_id=' + propertyId,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (data.action === 'liked') {
                likeBtn.classList.add('active');
                likeBtn.innerHTML = '❤️ <span id="like-count">' + data.like_count + '</span> Likes';
            } else {
                likeBtn.classList.remove('active');
                likeBtn.innerHTML = '♡ <span id="like-count">' + data.like_count + '</span> Likes';
            }
        } else {
            alert(data.message || 'An error occurred.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});





    // Update the postComment function to use AJAX
function postComment() {
    const commentText = commentInput.value.trim();
    const propertyId = <?php echo $property_id; ?>;
    const userId = <?php echo $_SESSION['buyer_id'] ?? 'null'; ?>;

    if (commentText !== '' && userRating > 0 && userId) {
        // Disable button during submission
        commentSubmit.disabled = true;
        commentSubmit.textContent = 'Posting...';

        // Create FormData object
        const formData = new FormData();
        formData.append('property_id', propertyId);
        formData.append('rating', userRating);
        formData.append('review_text', commentText);
        formData.append('user_id', userId);

        // Send AJAX request
        fetch('submit_review.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create new comment element
                const commentsList = document.querySelector('.comments-list');
                const newComment = document.createElement('div');
                newComment.className = 'comment';

                // Get initials from session
                const nameParts = "<?php echo $_SESSION['full_name'] ?? ''; ?>".split(' ');
                const initials = (nameParts[0] ? nameParts[0].charAt(0) : '') + 
                               (nameParts[1] ? nameParts[1].charAt(0) : '');

                // Create star display
                let starsHTML = '';
                for (let i = 0; i < userRating; i++) {
                    starsHTML += '★';
                }

                newComment.innerHTML = `
                    <div class="avatar">${initials}</div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <div class="comment-author">You</div>
                            <div class="comment-stars">${starsHTML}</div>
                        </div>
                        <div class="comment-text">${commentText}</div>
                        <div class="comment-date">Just now</div>
                    </div>
                `;

                commentsList.prepend(newComment);

                // Reset form
                commentInput.value = '';
                userRating = 0;
                resetStars();
                commentSubmit.disabled = true;
                commentSubmit.textContent = 'Post';
                
                // Update comment count
                const commentCount = document.querySelector('.comments-section h3');
                const currentCount = parseInt(commentCount.textContent.match(/\d+/)[0]);
                commentCount.textContent = `Comments (${currentCount + 1})`;
            } else {
                alert('Error: ' + data.message);
                commentSubmit.disabled = false;
                commentSubmit.textContent = 'Post';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting your review.');
            commentSubmit.disabled = false;
            commentSubmit.textContent = 'Post';
        });
    }
}
  </script>
</body>
</html>
