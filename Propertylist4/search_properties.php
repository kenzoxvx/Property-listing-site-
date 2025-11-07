<?php
session_start();
include("connect.php");

// Get search parameters
$location = $_GET['location'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';

// Build the base query
$query = "
    SELECT p.property_id, p.title, p.location, p.bedrooms, p.bathrooms, p.Area,
           p.description, p.price, p.status, p.property_type, p.year,
           (SELECT GROUP_CONCAT(pi.image_url) FROM propery_image pi
            WHERE pi.propery_id = p.property_id) as image_urls
    FROM property p
    WHERE p.confirmation_status = 'confirmed'
";

// Add search conditions
$conditions = [];
$params = [];

if (!empty($location)) {
    $conditions[] = "p.location LIKE :location";
    $params[':location'] = "%$location%";
}
if (!empty($property_type)) {
    $conditions[] = "p.property_type = :property_type";
    $params[':property_type'] = $property_type;
}
if (!empty($min_price)) {
    $conditions[] = "p.price >= :min_price";
    $params[':min_price'] = $min_price;
}
if (!empty($max_price)) {
    $conditions[] = "p.price <= :max_price";
    $params[':max_price'] = $max_price;
}
if (!empty($bedrooms)) {
    $conditions[] = "p.bedrooms >= :bedrooms";
    $params[':bedrooms'] = $bedrooms;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY p.cretedAt DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML for the properties
if (empty($properties)) {
    echo "<p>No properties found matching your criteria.</p>";
} else {
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
        $image_urls = explode(',', $row['image_urls']);
        $image = !empty($image_urls[0]) ? htmlspecialchars(trim($image_urls[0])) : 'Img/house1.jpg';
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
        <?php
    }
}
?>
