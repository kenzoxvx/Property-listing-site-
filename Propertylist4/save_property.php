<?php
session_start();
include("connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['buyer_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to save a property.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$property_id = $_POST['property_id'];
$buyer_id = $_SESSION['buyer_id'];

// Check if the user already saved the property
$check_stmt = $pdo->prepare("SELECT * FROM saved_properties WHERE property_id = ? AND buyer_id = ?");
$check_stmt->execute([$property_id, $buyer_id]);
$existing_save = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_save) {
    // Unsave the property
    $delete_stmt = $pdo->prepare("DELETE FROM saved_properties WHERE property_id = ? AND buyer_id = ?");
    $delete_stmt->execute([$property_id, $buyer_id]);
    $action = 'unsaved';
} else {
    // Save the property
    $insert_stmt = $pdo->prepare("INSERT INTO saved_properties (property_id, buyer_id, saved_at) VALUES (?, ?, NOW())");
    $insert_stmt->execute([$property_id, $buyer_id]);
    $action = 'saved';
}

echo json_encode([
    'success' => true,
    'action' => $action
]);
?>
