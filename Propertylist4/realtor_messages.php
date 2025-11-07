<?php
session_start();
include("connect.php");
$realtor_id = $_SESSION['realtor_id'];

// Fetch all conversations for the realtor
try {
    $stmt = $pdo->prepare("
        SELECT
            c.conversation_id,
            c.last_message_at,
            m.message_text AS last_message,
            m.is_read,
            u.id AS buyer_id,
            u.full_name AS buyer_name
        FROM conversations c
        JOIN messages m ON c.last_message_id = m.message_id
        JOIN users u ON
            (u.id = c.user1_id OR u.id = c.user2_id) AND
            u.id != :realtor_id AND
            u.user_type = 'buyer'
        WHERE
            (c.user1_id = :realtor_id OR c.user2_id = :realtor_id)
        ORDER BY c.last_message_at DESC
    ");
    $stmt->bindParam(':realtor_id', $realtor_id, PDO::PARAM_INT);
    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Fetch messages for the selected conversation (if any)
// Fetch messages for the selected conversation (if any)
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
                    u.full_name AS sender_name,
                    u.user_type AS sender_type
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
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - Realtor Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Add the CSS from the previous section here */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
      min-height: 100vh;
      overflow: hidden;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .container {
      display: flex;
      height: 100vh;
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
      gap: 20px;
    }

    /* Sidebar */
    .sidebar {
      width: 350px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      display: flex;
      flex-direction: column;
      animation: slideInLeft 0.6s ease;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .sidebar-header {
      padding: 25px;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      border-radius: 25px 25px 0 0;
    }

    .sidebar-header h2 {
      color: #2c3e50;
      margin-bottom: 15px;
      font-size: 1.8rem;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .search-box {
      position: relative;
      margin-top: 15px;
    }

    .search-box input {
      width: 100%;
      padding: 12px 40px 12px 15px;
      border: 2px solid rgba(102, 126, 234, 0.3);
      border-radius: 50px;
      font-size: 14px;
      outline: none;
      transition: all 0.3s ease;
      background: white;
    }

    .search-box input:focus {
      border-color: #667eea;
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }

    .search-box i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #667eea;
      font-size: 16px;
    }

    .contacts-list {
      flex: 1;
      overflow-y: auto;
      padding: 15px;
    }

    .contact-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border-radius: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 10px;
      position: relative;
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .contact-item:hover {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      transform: translateX(5px);
    }

    .contact-item.active {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    }

    .contact-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 18px;
      margin-right: 15px;
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
      position: relative;
    }

    .contact-avatar.online::after {
      content: '';
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 12px;
      height: 12px;
      background: #4caf50;
      border: 2px solid white;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% {
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
      }
      50% {
        box-shadow: 0 0 0 6px rgba(76, 175, 80, 0);
      }
    }

    .contact-info {
      flex: 1;
      min-width: 0;
    }

    .contact-name {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 3px;
      font-size: 15px;
    }

    .contact-preview {
      font-size: 13px;
      color: #7f8c8d;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .contact-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 5px;
    }

    .contact-time {
      font-size: 11px;
      color: #95a5a6;
    }

    .unread-badge {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 600;
      animation: bounceIn 0.5s ease;
    }

    @keyframes bounceIn {
      0% {
        transform: scale(0);
      }
      50% {
        transform: scale(1.2);
      }
      100% {
        transform: scale(1);
      }
    }

    /* Chat Area */
    .chat-area {
      flex: 1;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      display: flex;
      flex-direction: column;
      animation: slideInRight 0.6s ease;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

     /* Realtor Navigation */
    .realtor-nav {
      display: flex;
      justify-content: center;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 10px;
      margin: 20px auto;
      max-width: 800px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .nav-item {
      flex: 1;
      text-align: center;
      padding: 12px 10px;
      color: #555;
      text-decoration: none;
      font-weight: 500;
      border-radius: 10px;
      margin: 0 5px;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    .nav-item i {
      font-size: 1.3rem;
      color: #667eea;
    }

    .nav-item:hover {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      transform: translateY(-2px);
    }

    .nav-item.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .nav-item.active i {
      color: white;
    }
    /* Rest of your existing CSS */
    .messages-container {
      display: flex;
      height: calc(100vh - 150px);
      gap: 20px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      margin: 20px auto;
      max-width: 1400px;
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .chat-header {
      padding: 25px;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      border-radius: 25px 25px 0 0;
    }

    .chat-header-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .chat-avatar {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 20px;
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
      position: relative;
    }

    .chat-avatar.online::after {
      content: '';
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 14px;
      height: 14px;
      background: #4caf50;
      border: 3px solid white;
      border-radius: 50%;
    }

    .chat-user-info h3 {
      color: #2c3e50;
      margin-bottom: 3px;
      font-size: 1.3rem;
    }

    .chat-user-status {
      font-size: 13px;
      color: #7f8c8d;
    }

    .chat-user-status.online {
      color: #4caf50;
    }

    .chat-actions {
      display: flex;
      gap: 15px;
    }

    .chat-action-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: none;
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .chat-action-btn:hover {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      transform: scale(1.1) rotate(10deg);
    }

    .messages-container {
      flex: 1;
      overflow-y: auto;
      padding: 25px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(102, 126, 234, 0.02) 100%);
    }

    .message {
      display: flex;
      margin-bottom: 20px;
      animation: messageSlideIn 0.4s ease;
    }

    @keyframes messageSlideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .message.sent {
      justify-content: flex-end;
    }

    .message-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 14px;
      box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
      margin-right: 12px;
    }

    .message.sent .message-avatar {
      order: 2;
      margin-right: 0;
      margin-left: 12px;
      background: linear-gradient(135deg, #f093fb, #f5576c);
    }

    .message-content {
      max-width: 60%;
    }

    .message-bubble {
      padding: 15px 18px;
      border-radius: 18px;
      background: white;
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
      position: relative;
      word-wrap: break-word;
    }

    .message.sent .message-bubble {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .message-text {
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 5px;
    }

    .message-time {
      font-size: 11px;
      color: #95a5a6;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .message.sent .message-time {
      color: rgba(255, 255, 255, 0.7);
      justify-content: flex-end;
    }

    .message-status {
      color: #4caf50;
    }

    .date-divider {
      text-align: center;
      margin: 30px 0 20px;
      position: relative;
    }

    .date-divider span {
      background: rgba(102, 126, 234, 0.1);
      padding: 6px 20px;
      border-radius: 20px;
      font-size: 12px;
      color: #7f8c8d;
      font-weight: 500;
    }

    .typing-indicator {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 15px;
      background: white;
      border-radius: 18px;
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
      width: fit-content;
      margin-bottom: 20px;
    }

    .typing-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #667eea;
      animation: typing 1.4s infinite;
    }

    .typing-dot:nth-child(2) {
      animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    @keyframes typing {
      0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.7;
      }
      30% {
        transform: translateY(-10px);
        opacity: 1;
      }
    }

    /* Input Area */
    .input-area {
      padding: 20px 25px;
      border-top: 2px solid rgba(102, 126, 234, 0.2);
      display: flex;
      gap: 15px;
      align-items: center;
      background: rgba(255, 255, 255, 0.5);
      border-radius: 0 0 25px 25px;
    }

    .input-actions {
      display: flex;
      gap: 10px;
    }

    .input-btn {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: none;
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    .input-btn:hover {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      transform: scale(1.1) rotate(10deg);
    }

    .message-input-wrapper {
      flex: 1;
      position: relative;
    }

    .message-input {
      width: 100%;
      padding: 12px 20px;
      border: 2px solid rgba(102, 126, 234, 0.3);
      border-radius: 25px;
      font-size: 14px;
      outline: none;
      transition: all 0.3s ease;
      background: white;
      resize: none;
      max-height: 120px;
      font-family: 'Poppins', sans-serif;
    }

    .message-input:focus {
      border-color: #667eea;
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }

    .send-btn {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: none;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }

    .send-btn:hover {
      transform: scale(1.1) rotate(15deg);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
    }

    .send-btn:active {
      transform: scale(0.95);
    }

    /* Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #764ba2, #f093fb);
    }

    /* Responsive */
    @media (max-width: 968px) {
      .sidebar {
        width: 300px;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }

      .sidebar {
        position: absolute;
        left: -100%;
        width: 80%;
        max-width: 300px;
        z-index: 100;
        transition: left 0.3s ease;
        height: calc(100vh - 20px);
      }

      .sidebar.active {
        left: 10px;
      }

      .message-content {
        max-width: 75%;
      }

      .chat-header h3 {
        font-size: 1.1rem;
      }

      .menu-toggle {
        display: block;
        position: fixed;
        top: 30px;
        left: 20px;
        z-index: 99;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
      }
    }

    .menu-toggle {
      display: none;
    }

    /* Empty state */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #7f8c8d;
      text-align: center;
      padding: 40px;
    }

    .empty-state i {
      font-size: 80px;
      margin-bottom: 20px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-20px);
      }
    }

    .empty-state h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: #2c3e50;
    }
    /* Plus the following for the messages area */
    .messages-container {
      display: flex;
      height: calc(100vh - 100px);
      gap: 20px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }
    .conversations-list {
      width: 300px;
      background: white;
      border-radius: 10px;
      overflow-y: auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .conversation-item {
      padding: 15px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      transition: background 0.2s;
    }
    .conversation-item:hover {
      background: #f5f7fa;
    }
    .conversation-item.active {
      background: #e3f2fd;
      font-weight: 600;
    }
    .chat-area {
      flex: 1;
      background: white;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .chat-header {
      padding: 15px;
      border-bottom: 1px solid #eee;
      background: #f9f9f9;
    }
    .chat-messages {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
    }
    .message {
      margin-bottom: 15px;
      display: flex;
      flex-direction: column;
    }
    .message.sent {
      align-items: flex-end;
    }
    .message-bubble {
      max-width: 70%;
      padding: 10px 15px;
      border-radius: 15px;
      background: #e3f2fd;
    }
    .message.sent .message-bubble {
      background: #667eea;
      color: white;
    }
    .message-input-area {
      padding: 15px;
      border-top: 1px solid #eee;
      background: #f9f9f9;
    }
    .message-input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 20px;
      outline: none;
    }
  </style>
</head>
<body>

  <div class="messages-container">
    <!-- Conversations List -->
    <div class="conversations-list">
      <h3 style="padding: 15px; margin: 0; border-bottom: 1px solid #eee;">Conversations</h3>
      <?php foreach ($conversations as $conversation): ?>
        <div class="conversation-item <?php echo $conversation['conversation_id'] == $conversation_id ? 'active' : ''; ?>"
             onclick="selectConversation(<?php echo $conversation['conversation_id']; ?>, <?php echo $conversation['buyer_id']; ?>)">
          <div style="font-weight: 600;"><?php echo htmlspecialchars($conversation['buyer_name']); ?></div>
          <div style="font-size: 0.9rem; color: #666;">
            <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 30)) . (strlen($conversation['last_message']) > 30 ? '...' : ''); ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
      <div class="chat-header">
        <h4 id="chatBuyerName">
          <?php
          if ($conversation_id) {
              $buyer_name = $messages[0]['sender_type'] === 'buyer' ? $messages[0]['sender_name'] : 'Buyer';
              echo htmlspecialchars($buyer_name);
          } else {
              echo "Select a conversation";
          }
          ?>
        </h4>
      </div>
      <div class="chat-messages" id="chatMessages">
        <?php if ($conversation_id && count($messages) > 0): ?>
          <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender_id'] == $realtor_id ? 'sent' : ''; ?>">
              <div class="message-bubble">
                <?php echo htmlspecialchars($message['message_text']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align: center; margin-top: 50px; color: #999;">
            <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <p>Select a conversation to view messages</p>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($conversation_id): ?>
        <div class="message-input-area">
          <input type="text" class="message-input" id="messageInput" placeholder="Type a message...">
          <button onclick="sendMessage()" style="margin-top: 10px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 20px; cursor: pointer;">
            Send
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Select a conversation
    function selectConversation(conversationId, buyerId) {
      window.location.href = `realtor_messages.php?conversation_id=${conversationId}`;
    }

    // Send a message
    function sendMessage() {
      const input = document.getElementById('messageInput');
      const text = input.value.trim();
      if (!text) return;

      const formData = new FormData();
      formData.append('receiver_id', <?php echo $conversation_id ? json_encode($messages[0]['sender_id'] == $realtor_id ? $messages[0]['receiver_id'] : $messages[0]['sender_id']) : 'null'; ?>);
      formData.append('message_text', text);

      fetch('send_message2.php', {
          method: 'POST',
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              input.value = '';
              location.reload(); // Refresh to show the new message
          } else {
              alert('Failed to send message.');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          alert('Failed to send message.');
      });
    }
  </script>
</body>
</html>
