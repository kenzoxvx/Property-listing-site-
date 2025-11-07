<?php
session_start();
include("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['buyer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to post a notice']);
        exit;
    }
    
    // Get form data
    $property_id = $_POST['property_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $notice_title = $_POST['notice_title'] ?? '';
    $notice_content = $_POST['notice_content'] ?? '';
    $notice_type = $_POST['notice_type'] ?? 'general';
    
    // Validate input
    if (empty($property_id) || empty($user_id) || empty($notice_title) || empty($notice_content)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    try {
        // Insert notice into database
        $stmt = $pdo->prepare("
            INSERT INTO community_notices (property_id, user_id, notice_title, notice_content, notice_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $success = $stmt->execute([$property_id, $user_id, $notice_title, $notice_content, $notice_type]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Notice posted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to post notice']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>