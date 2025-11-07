<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HomeHub - Find Your Dream Property</title>
  <link rel="stylesheet" href="styles/realtorregistration.css"> 
</head>
<body>
  <div class="container" id="container">
    <div class="form-container sign-up">
      <form action="Signuplogic.php" method="post">
        <h1>Create Account</h1>
        <p>Find your dream property with just a few clicks</p>
        
        <input type="text" placeholder="Name" name="fullname" />
        <input type="email" placeholder="Email" name="email" />
        <input type="number" placeholder="Phone number" name="phonenumber" />
        <input type="password" placeholder="Password" name="password" />
        <a href="#" class="forgot-password" id="signInMobile" >signup</a>
        <button>Sign Up</button>
      </form>
    </div>
    <div class="form-container sign-in">
      <form action="signinlogic.php" method="post">
        <h1>Sign In</h1>
        <p>Access your account to view saved properties</p>
        
        <input type="email" placeholder="Email" name="email" required />
        <input type="password" placeholder="Password" name="password" required />
        <a href="#" class="forgot-password">Forgot your password?</a>
        
        <a href="#" class="forgot-password" id="signUpMobile" >signup</a>
        <button name="submit">Sign In</button>
      </form>
    </div>
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>Welcome Back!</h1>
          <div class="building-animation">
            <div class="building">
              <div class="windows-container">
                <div class="window" style="--i:1"></div>
                <div class="window" style="--i:2"></div>
                <div class="window" style="--i:3"></div>
                <div class="window" style="--i:4"></div>
                <div class="window" style="--i:5"></div>
                <div class="window" style="--i:6"></div>
              </div>
            </div>
          </div>
          <p>To keep connected with us please login with your personal info</p>
          <button class="ghost" id="signIn">Sign In</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Hello, Friend!</h1>
          <div class="building-animation">
            <div class="building">
              <div class="windows-container">
                <div class="window" style="--i:1"></div>
                <div class="window" style="--i:2"></div>
                <div class="window" style="--i:3"></div>
                <div class="window" style="--i:4"></div>
                <div class="window" style="--i:5"></div>
                <div class="window" style="--i:6"></div>
              </div>
            </div>
          </div>
          <p>Enter your personal details and start your journey with us</p>
          <button class="ghost" id="signUp">Sign Up</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Snackbar container -->
  <div id="snackbar-container"></div>

  <script>
    // Function to show notifications
    function showSnackbar(message, type = 'error') {
      const snackbarContainer = document.getElementById('snackbar-container');
      const snackbar = document.createElement('div');
      snackbar.className = `snackbar ${type}`;
      snackbar.innerHTML = `
        <span>${message}</span>
        <span class="close" onclick="this.parentElement.style.opacity='0'">×</span>
      `;
      
      snackbarContainer.appendChild(snackbar);
      
      // Auto-remove after 5s
      setTimeout(() => {
        if (snackbar.parentElement) {
          snackbar.style.opacity = '0';
          setTimeout(() => snackbarContainer.removeChild(snackbar), 500);
        }
      }, 5000);
    }

    // Display PHP session messages
    window.onload = function() {
      <?php
      if (isset($_SESSION['error_messages']) && is_array($_SESSION['error_messages'])) {
          foreach ($_SESSION['error_messages'] as $error) {
              echo "showSnackbar('" . addslashes($error) . "', 'error');";
          }
          unset($_SESSION['error_messages']);
      }
      
      if (isset($_SESSION['success_messages']) && is_array($_SESSION['success_messages'])) {
          foreach ($_SESSION['success_messages'] as $success) {
              echo "showSnackbar('" . addslashes($success) . "', 'success');";
          }
          unset($_SESSION['success_messages']);
      }
      ?>
    }
  </script>

  <script>
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const signUpMobileButton = document.getElementById('signUpMobile');
    const signInMobileButton = document.getElementById('signInMobile');
    const container = document.getElementById('container');

    signUpButton.addEventListener('click', () => {
      container.classList.add('right-panel-active');
    });

    signInButton.addEventListener('click', () => {
      container.classList.remove('right-panel-active');
    });

    if (signUpMobileButton) {
      signUpMobileButton.addEventListener('click', (e) => {
        e.preventDefault();
        container.classList.add('right-panel-active');
      });
    }

    if (signInMobileButton) {
      signInMobileButton.addEventListener('click', (e) => {
        e.preventDefault();
        container.classList.remove('right-panel-active');
      });
    }
  </script>
  <script src="js/realtoreg.js"></script>
</body>
</html>
