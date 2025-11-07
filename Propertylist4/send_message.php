<?php
session_start();
include("connect.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['buyer_id'] ?? $_SESSION['realtor_id'];
    $receiver_id = $_POST['receiver_id'];
    $message_text = $_POST['message_text'];

    try {
        $pdo->beginTransaction();

        // Insert the message
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message_text)
            VALUES (:sender_id, :receiver_id, :message_text)
        ");
        $stmt->execute([
            ':sender_id' => $sender_id,
            ':receiver_id' => $receiver_id,
            ':message_text' => $message_text
        ]);
        $message_id = $pdo->lastInsertId();

        // Get or create the conversation
        $stmt = $pdo->prepare("
            SELECT conversation_id FROM conversations
            WHERE (user1_id = :user1_id AND user2_id = :user2_id)
               OR (user1_id = :user2_id AND user2_id = :user1_id)
        ");
        $stmt->execute([
            ':user1_id' => $sender_id,
            ':user2_id' => $receiver_id
        ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            // Update the conversation
            $stmt = $pdo->prepare("
                UPDATE conversations
                SET last_message_id = :message_id, last_message_at = NOW()
                WHERE conversation_id = :conversation_id
            ");
            $stmt->execute([
                ':message_id' => $message_id,
                ':conversation_id' => $conversation['conversation_id']
            ]);
        } else {
            // Create a new conversation
            $stmt = $pdo->prepare("
                INSERT INTO conversations (user1_id, user2_id, last_message_id)
                VALUES (:user1_id, :user2_id, :message_id)
            ");
            $stmt->execute([
                ':user1_id' => $sender_id,
                ':user2_id' => $receiver_id,
                ':message_id' => $message_id
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
