<?php
session_start();
include("connect.php");
$buyer_id = $_SESSION['buyer_id'];

if (isset($_SESSION['success_message']) && is_array($_SESSION['success_message'])) {
    echo "<script>window.onload = function() {";
    foreach ($_SESSION['success_message'] as $succ) {
        echo "showSnackbar('$succ');";
    }
    echo "}</script>";

    // Clear errors after displaying
    unset($_SESSION['success_message']);
}

if ( !isset($buyer_id) ) {
  header("Location: buyersregistration.php");
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DreamHome Real Estate</title>
  <link rel="stylesheet" href="styles/buyerpage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .hero {
        height: 100vh;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('img/frame.jpg') center/cover;
        display: flex;
        align-items: center;
        color: white;
        position: relative;
      }

      /* Property Modal Styles */
.property-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.close-modal {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 24px;
    cursor: pointer;
}

.property-slider {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    margin-bottom: 20px;
    scroll-snap-type: x mandatory;
}

.property-slider .slide {
    scroll-snap-align: start;
    min-width: 100%;
}

.property-slider img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 4px;
}

.property-info {
    margin-top: 20px;
}

.property-info p {
    margin-bottom: 10px;
}
      
  </style>

</head>
<body>
  <!-- Header -->
  <header>
    <div class="container">
      <div class="header-content">
        <div class="logo">
          <img src="img/kENZOHOMES.png" alt="" style="width: 100px; heigth: 100px;">
        </div>
        <nav>
          <ul>
            <li><a href="#featured">Featured</a></li>
            <li><a href="#listProperty" class="pulse">Search Property</a></li>
            <li><a href="messages.php" target="_blanc" class="pulse">Messages</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero" id="listProperty">
    <div class="container">
      <div class="hero-content">
        <h1>Find Your Dream Home</h1>
        <p>Discover the perfect property from our extensive collection of premium listings across the country.</p>
        
        <!-- Change the search-container form to submit to the same page -->
        <div class="search-container">
  <div class="search-row">
    <div class="search-group">
      <label for="location">Location</label>
      <input type="text" id="search-location" placeholder="City, ZIP or Neighborhood">
    </div>
    <div class="search-group">
      <label for="property-type">Property Type</label>
      <select id="search-property-type">
        <option value="">All Types</option>
        <option value="house">House</option>
        <option value="apartment">Apartment</option>
        <option value="condo">Air-BNB</option>
       
      </select>
    </div>
  </div>

  <div class="search-row">
    <div class="search-group">
      <label for="min-price">Min Price</label>
      <select id="search-min-price">
        <option value="">No Min</option>
        <option value="100000">₦100,000</option>
        <option value="200000">₦200,000</option>
        <option value="300000">₦300,000</option>
        <option value="500000">₦500,000</option>
        <option value="750000">₦750,000</option>
        <option value="1000000">₦1,000,000+</option>
      </select>
    </div>
    <div class="search-group">
      <label for="max-price">Max Price</label>
      <select id="search-max-price">
        <option value="">No Max</option>
        <option value="200000">₦200,000</option>
        <option value="300000">₦300,000</option>
        <option value="500000">₦500,000</option>
        <option value="750000">₦750,000</option>
        <option value="1000000">₦1,000,000</option>
        <option value="2000000">₦2,000,000+</option>
      </select>
    </div>
    <div class="search-group">
      <label for="bedrooms">Bedrooms</label>
      <select id="search-bedrooms">
        <option value="">Any</option>
        <option value="1">1+</option>
        <option value="2">2+</option>
        <option value="3">3+</option>
        <option value="4">4+</option>
        <option value="5">5+</option>
      </select>
    </div>
    <button id="search-button" class="search-button">Search Properties</button>
  </div>
</div>
      </div>
    </div>
  </section>

  <!-- Featured Properties -->
  <section class="section" id="featured">
  <div class="container">
    <div class="section-title">
      <h2>Featured Properties</h2>
      <p>Handpicked premium properties in the most sought-after locations</p>
    </div>

    <div class="properties-grid">
  <!-- Property Cards -->
  <?php
  try {
      // Prepare SQL statement to get one image per property
      $stmt = $pdo->prepare("
          SELECT p.property_id, p.title, p.location, p.bedrooms, p.bathrooms, p.Area,
                p.description, p.price, p.status, p.property_type, p.year,
                (SELECT GROUP_CONCAT(pi.image_url) FROM propery_image pi
                  WHERE pi.propery_id = p.property_id) as image_urls
          FROM property p
          WHERE p.confirmation_status = 'confirmed'
          ORDER BY p.cretedAt DESC
      ");
      $stmt->execute();

      // Fetch all properties
      $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Debug: Check if any properties are fetched
      if (empty($properties)) {
          echo "<p>No properties found.</p>";
      }

      foreach ($properties as $row) {
          $property_id = $row['property_id'];
          $title = htmlspecialchars($row['title']);
          $location = htmlspecialchars($row['location']);
          $bedrooms = $row['bedrooms'];
          $bathrooms = $row['bathrooms'];
          $area = number_format($row['Area']);
          $description = htmlspecialchars($row['description']);
          $price = number_format($row['price']);
          $status = htmlspecialchars($row['status']);
          $property_type = htmlspecialchars($row['property_type']);
          $year = $row['year'];

          // Split the image URLs and get the first one
          $image_urls = explode(',', $row['image_urls']);
          $image = !empty($image_urls[0]) ? htmlspecialchars(trim($image_urls[0])) : 'Img/house1.jpg';

          // Debug: Check if image URL is valid
          if ($image == 'Img/house1.jpg') {
              echo "<p>No image found for property ID: $property_id</p>";
          }
  ?>

  <div class="property-card show" data-property-id="<?php echo $property_id; ?>">
      <div class="property-image">
          <img src="<?php echo $image; ?>" alt="<?php echo $title; ?>">
          <div class="property-tag"><?php echo $status; ?></div>
          <div class="property-price">₦<?php echo $price; ?></div>
      </div>
      <div class="property-details">
          <h3 class="property-title"><?php echo $title; ?></h3>
          <div class="property-location">
              <i class="fas fa-map-marker-alt"></i> <?php echo $location; ?>
          </div>
          <div class="property-features">
              <div class="feature">
                  <i class="fas fa-bed"></i> <?php echo $bedrooms; ?> Beds
              </div>
              <div class="feature">
                  <i class="fas fa-bath"></i> <?php echo $bathrooms; ?> Baths
              </div>
              <div class="feature">
                  <i class="fas fa-vector-square"></i> <?php echo $area; ?> sqft
              </div>
              <div class="feature">
                  <i class="fas fa-home"></i> <?php echo $property_type; ?>
              </div>
              <?php if ($year): ?>
              <div class="feature">
                  <i class="fas fa-calendar"></i> <?php echo $year; ?>
              </div>
              <?php endif; ?>
          </div>
      </div>
      <div class="property-footer">
          <div class="property-action">
          <a class="view-details" href="propertyview.php?property_id=<?php echo $property_id; ?>">View Details</a>

          </div>
      </div>
  </div>

  <?php }

  } catch (PDOException $e) {
      // Debug: Display any database errors
      echo "<p>Error: " . $e->getMessage() . "</p>";
  }
  ?>
</div>




      
      <!-- Repeat similar structure for other properties -->
    </div>
  </div>
</section>

<div id="snackbar-container"></div>

<script src="js/realtoreg.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Handle view details click
    

    // Function to fetch property details and images
    function fetchPropertyDetails(propertyId) {
        fetch('get_property_details.php?property_id=' + propertyId)
            .then(response => response.json())
            .then(data => {
                showPropertyModal(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load property details');
            });
    }

    // Function to show modal with property details
    function showPropertyModal(property) {
        // Create modal HTML
        const modalHTML = `
            <div class="property-modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>${property.title}</h2>
                    <div class="property-slider">
                        ${property.images.map(image => `
                            <div class="slide">
                                <img src="${image.image_url}" alt="Property Image">
                            </div>
                        `).join('')}
                    </div>
                    <div class="property-info">
                        <p><strong>Location:</strong> ${property.location}</p>
                        <p><strong>Price:</strong> ₦ ${property.price.toLocaleString()}</p>
                        <p><strong>Type:</strong> ${property.property_type}</p>
                        <p><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                        <p><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                        <p><strong>Area:</strong> ${property.Area.toLocaleString()} sqft</p>
                        <p><strong>Year Built:</strong> ${property.year}</p>
                        <p><strong>Description:</strong> ${property.description}</p>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add event listener for closing modal
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.querySelector('.property-modal').remove();
        });
    }
});

document.getElementById('search-button').addEventListener('click', function() {
    const location = document.getElementById('search-location').value;
    const propertyType = document.getElementById('search-property-type').value;
    const minPrice = document.getElementById('search-min-price').value;
    const maxPrice = document.getElementById('search-max-price').value;
    const bedrooms = document.getElementById('search-bedrooms').value;

    // Construct the query parameters
    const params = new URLSearchParams({
        location: location,
        property_type: propertyType,
        min_price: minPrice,
        max_price: maxPrice,
        bedrooms: bedrooms
    });

    // Fetch the search results from the backend
    fetch(`search_properties.php?${params.toString()}`)
        .then(response => response.text())
        .then(data => {
            // Update the properties grid with the search results
            document.querySelector('.properties-grid').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load search results');
        });
});
</script>
</body>
</html>