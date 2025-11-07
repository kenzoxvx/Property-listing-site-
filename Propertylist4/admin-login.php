<?php
// Start the session
session_start();

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin-dashboard.php");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include("connect.php"); // Include your database connection

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        // Fetch admin from the database
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($admin && password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['full_name'];

            // Redirect to the admin dashboard
            header("Location: admin-dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | DreamHome Real Estate</title>
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 450px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .logo {
      font-size: 32px;
      font-weight: 800;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
    }

    .tagline {
      color: #6b7280;
      font-size: 14px;
      margin-bottom: 30px;
    }

    .login-form .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .login-form label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #374151;
      font-size: 14px;
    }

    .login-form input {
      width: 100%;
      padding: 14px 20px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.8);
    }

    .login-form input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    .login-btn {
      width: 100%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    .forgot-password {
      text-align: right;
      margin-top: -15px;
      margin-bottom: 20px;
    }

    .forgot-password a {
      color: #667eea;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }

    .login-footer {
      margin-top: 30px;
      color: #6b7280;
      font-size: 14px;
    }

    .login-footer a {
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
    }

    .login-footer a:hover {
      text-decoration: underline;
    }

    .error-message {
      color: #ef4444;
      font-size: 14px;
      margin-bottom: 20px;
      text-align: center;
      background: rgba(255, 0, 0, 0.1);
      padding: 10px;
      border-radius: 8px;
      display: <?php echo isset($error) ? 'block' : 'none'; ?>;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">DreamHome</div>
    <div class="tagline">Admin Portal</div>

    <?php if (isset($error)): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST" action="admin-login.php">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>

      <div class="forgot-password">
        <a href="#">Forgot password?</a>
      </div>

      <button type="submit" class="login-btn">Login</button>
    </form>

    <div class="login-footer">
      <p>Not an admin? <a href="index.html">Return to Homepage</a></p>
    </div>
  </div>
</body>
</html>
