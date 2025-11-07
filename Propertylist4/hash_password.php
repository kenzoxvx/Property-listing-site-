<?php
$password = "password@123";
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
echo "Hashed Password: " . $hashedPassword;
?>
