<?php
session_start();
include("connect.php");

if (!isset($_SESSION['admin_id'])) {
  header("HTTP/1.0 403 Forbidden");
  exit;
}

if (!isset($_POST['realtor_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Realtor ID is required']);
  exit;
}

$realtorId = $_POST['realtor_id'];

// Update realtor verification status
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = :realtor_id");
$stmt->execute(['realtor_id' => $realtorId]);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Realtor verified successfully']);
?>
