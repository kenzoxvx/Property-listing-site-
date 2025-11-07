<?php
session_start();
include("connect.php");

$conversation_id = $_GET['conversation_id'] ?? null;
$messages = [];
if ($conversation_id) {
    try {
        // First, get user1_id and user2_id for the conversation
        $stmt = $pdo->prepare("
            SELECT user1_id, user2_id
            FROM conversations
            WHERE conversation_id = :conversation_id
        ");
        $stmt->bindParam(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->execute();
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            $user1_id = $conversation['user1_id'];
            $user2_id = $conversation['user2_id'];

            // Now fetch messages
            $stmt = $pdo->prepare("
                SELECT
                    m.message_id,
                    m.sender_id,
                    m.receiver_id,
                    m.message_text,
                    m.sent_at,
                    m.is_read,
                    u.full_name AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE
                    (m.sender_id = :user1_id AND m.receiver_id = :user2_id)
                    OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id)
                ORDER BY m.sent_at ASC
            ");
            $stmt->bindParam(':user1_id', $user1_id, PDO::PARAM_INT);
            $stmt->bindParam(':user2_id', $user2_id, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($messages);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
