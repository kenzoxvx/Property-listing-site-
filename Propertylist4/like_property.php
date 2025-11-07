<?php
session_start();
include("connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['buyer_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to like a property.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$property_id = $_POST['property_id'];
$buyer_id = $_SESSION['buyer_id'];

// Check if the user already liked the property
$check_stmt = $pdo->prepare("SELECT * FROM property_likes WHERE property_id = ? AND buyer_id = ?");
$check_stmt->execute([$property_id, $buyer_id]);
$existing_like = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_like) {
    // Unlike the property
    $delete_stmt = $pdo->prepare("DELETE FROM property_likes WHERE property_id = ? AND buyer_id = ?");
    $delete_stmt->execute([$property_id, $buyer_id]);
    $action = 'unliked';
} else {
    // Like the property
    $insert_stmt = $pdo->prepare("INSERT INTO property_likes (property_id, buyer_id, created_at) VALUES (?, ?, NOW())");
    $insert_stmt->execute([$property_id, $buyer_id]);
    $action = 'liked';
}

// Get the updated like count
$count_stmt = $pdo->prepare("SELECT COUNT(*) as like_count FROM property_likes WHERE property_id = ?");
$count_stmt->execute([$property_id]);
$like_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['like_count'];

echo json_encode([
    'success' => true,
    'action' => $action,
    'like_count' => $like_count
]);
?>
