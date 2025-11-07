<?php
    session_start();
    // Unset all session variables
    $_SESSION = array();
    // Destroy the session
    session_destroy();
    // Redirect to the login/registration page
    header("Location: realtorregistration.php");
    exit();
?>
