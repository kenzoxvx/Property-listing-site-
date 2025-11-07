<?php
session_start();
include("connect.php");

// Check if user is logged in
if (!isset($_SESSION['buyer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

// Validate input
if (!isset($_POST['property_id'], $_POST['rating'], $_POST['review_text'], $_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$property_id = $_POST['property_id'];
$rating = (int)$_POST['rating'];
$review_text = trim($_POST['review_text']);
$user_id = $_POST['user_id'];

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

// Validate review text
if (empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Review text cannot be empty']);
    exit;
}

// Insert review into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, property_id, rating, review_text, created_At)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $property_id, $rating, $review_text]);
    
    // Get user info for the response
    $user_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'full_name' => $user['full_name']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>