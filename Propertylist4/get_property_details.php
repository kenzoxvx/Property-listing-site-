<?php
session_start();
include("connect.php");

if (!isset($_SESSION['admin_id'])) {
  header("HTTP/1.0 403 Forbidden");
  exit;
}

if (!isset($_GET['property_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Property ID is required']);
  exit;
}

$propertyId = $_GET['property_id'];

// Fetch property details
$propertyStmt = $pdo->prepare("
  SELECT p.*, (SELECT GROUP_CONCAT(pi.image_url) FROM propery_image pi WHERE pi.propery_id = p.property_id) as image_urls
  FROM property p
  WHERE p.property_id = :property_id
  LIMIT 1
");
$propertyStmt->execute(['property_id' => $propertyId]);
$property = $propertyStmt->fetch(PDO::FETCH_ASSOC);

// Fetch realtor details
$realtorStmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id LIMIT 1");
$realtorStmt->execute(['user_id' => $property['user_id']]);
$realtor = $realtorStmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
  'success' => true,
  'property' => $property,
  'realtor' => $realtor
]);
?>
