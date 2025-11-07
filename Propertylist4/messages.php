<?php
session_start();
include("connect.php");

$realtor_id = $_GET['realtor_id'] ?? null;
$buyer_id = $_SESSION['buyer_id'];
$current_user_id = $buyer_id;

// Fetch all realtors
try {
    $stmt = $pdo->prepare("
        SELECT id, full_name
        FROM users
        WHERE user_type = 'realtor'
    ");
    $stmt->execute();
    $realtors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Fetch recent conversations
try {
    $stmt = $pdo->prepare("
        SELECT
            c.conversation_id,
            c.last_message_at,
            m.message_text AS last_message,
            m.is_read,
            u.id AS other_user_id,
            u.full_name AS other_user_name,
            u.user_type AS other_user_type
        FROM conversations c
        JOIN messages m ON c.last_message_id = m.message_id
        JOIN users u ON
            (u.id = c.user1_id OR u.id = c.user2_id) AND
            u.id != :current_user_id
        WHERE
            (c.user1_id = :current_user_id OR c.user2_id = :current_user_id)
        ORDER BY c.last_message_at DESC
    ");
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recent_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <title>Messages - RealEstate Connect</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
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
  </style>
</head>
<body>
  <button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>

  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <h2>Messages</h2>
        <div class="search-box">
          <input type="text" placeholder="Search conversations..." id="searchInput">
          <i class="fas fa-search"></i>
        </div>
      </div>
      <div class="contacts-list" id="contactsList">
        <!-- Contacts will be dynamically added here -->
      </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
      <div class="chat-header">
        <div class="chat-header-info">
          <div class="chat-avatar online" id="chatAvatar">SP</div>
          <div class="chat-user-info">
            <h3 id="chatUserName">Sarah Parker</h3>
            <span class="chat-user-status online" id="chatUserStatus">Online</span>
          </div>
        </div>
        <div class="chat-actions">
          <button class="chat-action-btn" title="Voice Call">
            <i class="fas fa-phone"></i>
          </button>
          <button class="chat-action-btn" title="Video Call">
            <i class="fas fa-video"></i>
          </button>
          <button class="chat-action-btn" title="More Options">
            <i class="fas fa-ellipsis-v"></i>
          </button>
        </div>
      </div>

      <div class="messages-container" id="messagesContainer">
        <!-- Messages will be dynamically added here -->
      </div>

      <div class="input-area">
        <div class="input-actions">
          <button class="input-btn" title="Attach File">
            <i class="fas fa-paperclip"></i>
          </button>
          <button class="input-btn" title="Emoji">
            <i class="fas fa-smile"></i>
          </button>
        </div>
        <div class="message-input-wrapper">
          <textarea class="message-input" id="messageInput" placeholder="Type a message..." rows="1"></textarea>
        </div>
        <button class="send-btn" onclick="sendMessage()">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>

<script>
/* =============================
   1. Load PHP data into JS
   ============================= */
const realtors = <?php echo json_encode(array_map(function($realtor) {
    $parts = explode(' ', $realtor['full_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    return [
        'id' => $realtor['id'],
        'name' => $realtor['full_name'],
        'initials' => $initials,
        'is_realtor' => true,
        'is_recent' => false,
        'unread' => 0,
        'online' => true,
        'preview' => 'Click to start chat',
        'time' => 'Realtor'
    ];
}, $realtors)); ?>;

// At the end of your init() function, add:
window.onload = function() {
    init();
    // Auto-select realtor if realtor_id is provided
    const urlParams = new URLSearchParams(window.location.search);
    const realtorId = urlParams.get('realtor_id');
    if (realtorId) {
        const realtor = contacts.find(c => c.id == realtorId && c.is_realtor);
        if (realtor) {
            selectContact({ currentTarget: document.querySelector('.contact-item') }, realtor.id, null);
        }
    }
};


const recentChats = <?php echo json_encode(array_map(function($chat) {
    $parts = explode(' ', $chat['other_user_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    return [
        'id' => $chat['other_user_id'],
        'name' => $chat['other_user_name'],
        'initials' => $initials,
        'last_message' => $chat['last_message'],
        'last_message_at' => $chat['last_message_at'],
        'unread' => $chat['is_read'] ? 0 : 1,
        'is_realtor' => $chat['other_user_type'] === 'realtor',
        'is_recent' => true,
        'conversation_id' => $chat['conversation_id']
    ];
}, $recent_chats)); ?>;

let messages = <?php echo json_encode($messages); ?>;

/* =============================
   2. Merge contacts
   ============================= */
const contacts = [...recentChats];
realtors.forEach(r => {
    if (!contacts.some(c => c.id === r.id)) {
        contacts.push(r);
    }
});

/* =============================
   3. Global state
   ============================= */
let currentContact = contacts[0] || null;
let currentConversationId = <?php echo $conversation_id ?? 'null'; ?>;

/* =============================
   4. Functions
   ============================= */

// Initialize
function init() {
    renderContacts();
    renderMessages();
    setupInputAutoResize();
    document.getElementById('messageInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

// Render contacts list
function renderContacts() {
    const contactsList = document.getElementById('contactsList');
    contactsList.innerHTML = contacts.map((contact, index) => `
        <div class="contact-item ${index === 0 ? 'active' : ''} ${contact.is_recent ? 'recent' : ''}"
             onclick="selectContact(event, ${contact.id}, ${contact.conversation_id || 'null'})"
             style="animation-delay: ${index * 0.1}s">
            <div class="contact-avatar ${contact.online ? 'online' : ''}">${contact.initials}</div>
            <div class="contact-info">
                <div class="contact-name">${contact.name}</div>
                ${contact.is_recent ?
                    `<div class="contact-preview">${contact.last_message}</div>` :
                    `<div class="contact-preview">Click to start chat</div>`
                }
            </div>
            <div class="contact-meta">
                ${contact.is_recent ?
                    `<div class="contact-time">${new Date(contact.last_message_at).toLocaleTimeString()}</div>` :
                    `<div class="contact-time">Realtor</div>`
                }
                ${contact.unread > 0 ? `<div class="unread-badge">${contact.unread}</div>` : ''}
            </div>
        </div>
    `).join('');
}

// Render messages
// Render messages
function renderMessages() {
    const messagesContainer = document.getElementById('messagesContainer');
    if (!currentConversationId || messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Select a contact to start chatting</h3>
            </div>
        `;
        return;
    }
    messagesContainer.innerHTML = `
        <div class="date-divider"><span>Today</span></div>
        ${messages.map(msg => `
            <div class="message ${msg.sender_id == <?php echo $current_user_id; ?> ? 'sent' : ''}">
                <div class="message-avatar">${msg.sender_name.substring(0, 2).toUpperCase()}</div>
                <div class="message-content">
                    <div class="message-bubble">
                        <div class="message-text">${msg.message_text}</div>
                        <div class="message-time">
                            ${new Date(msg.sent_at).toLocaleTimeString()}
                        </div>
                    </div>
                </div>
            </div>
        `).join('')}
    `;
    scrollToBottom();
}


function selectContact(e, contactId, conversationId) {
    currentContact = contacts.find(c => c.id == contactId);
    currentConversationId = conversationId;
    if (!currentContact) return;

    document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
    if (e.currentTarget) {
        e.currentTarget.classList.add('active');
    }

    document.getElementById('chatAvatar').textContent = currentContact.initials;
    document.getElementById('chatUserName').textContent = currentContact.name;

    if (conversationId && conversationId !== 'null') {
        // Load messages for this conversation via AJAX
        fetch(`get_messages.php?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                messages = data;
                renderMessages();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('messagesContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Failed to load messages: ${error.message}</h3>
                    </div>
                `;
            });
    } else {
        // Start a new conversation
        messages = [];
        document.getElementById('messagesContainer').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Start a new chat with ${currentContact.name}</h3>
            </div>
        `;
    }
}




// Send message (AJAX)
function sendMessage() {
    const input = document.getElementById('messageInput');
    const text = input.value.trim();
    if (!text || !currentContact) return;

    const formData = new FormData();
    formData.append('receiver_id', currentContact.id);
    formData.append('message_text', text);

    // Optimistically add the message to the UI
    const now = new Date();
    const newMessage = {
        message_id: Date.now(), // Temporary ID
        sender_id: <?php echo $current_user_id; ?>,
        receiver_id: currentContact.id,
        message_text: text,
        sent_at: now.toISOString(),
        is_read: false,
        sender_name: 'You'
    };
    messages.push(newMessage);
    renderMessages();
    input.value = '';

    // Send the message to the server
    fetch('send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to send message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Failed to send message: ${error.message}`);
        // Remove the optimistically added message
        messages.pop();
        renderMessages();
    });
}





// Auto-resize input
function setupInputAutoResize() {
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

// Scroll to bottom
function scrollToBottom() {
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

/* =============================
   5. Init app
   ============================= */
window.onload = init;
</script>
