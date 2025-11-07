<?php 
session_start();
include("connect.php");

$server = $_SERVER['REQUEST_METHOD'];
$errormsg = [];
$success_message = [];

if ($server === "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phonenumber'] ?? '';
    $passwordEl = $_POST['password'] ?? '';
    $user_type = "buyer";
    $currentDate = date("y-m-d");

    // Validate inputs
    if (empty($name) || empty($email) || empty($passwordEl) || empty($phone)) {
        $errormsg[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errormsg[] = "Invalid email format";
    }

    // Only check database if basic validation passes
    if (empty($errormsg)) {
        $sql = "SELECT * FROM users 
                WHERE (email = :email OR phone_number = :phone) 
                AND user_type = :user_type";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["user_type" => $user_type, "email" => $email, "phone" => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['email'] === $email) {
            $errormsg[] = "Email already exists";
        }

        if ($user && $user['phone_number'] == $phone) {
            $errormsg[] = "Phone number already exists.";
        }
    }

    // If no errors, create account
    if (empty($errormsg)) {
        $hashed_password = password_hash($passwordEl, PASSWORD_DEFAULT);
        $insert_user = "INSERT INTO users (full_name, email, password_hash, phone_number, user_type, created_at)
                        VALUES(:fullname, :email, :passwordhash, :phone_number, :user_type, :createdAt)";
        $stmt = $pdo->prepare($insert_user);
        
        if ($stmt->execute([
            "fullname" => $name,    
            "email" => $email,
            "passwordhash" => $hashed_password,
            "phone_number" => $phone,
            "user_type" => $user_type,
            "createdAt" => $currentDate
        ])) {
            $success_message[] = "Account created successfully";
            $_SESSION['buyer_id'] = $pdo->lastInsertId();
            header("Location: buyerpage.php");
            exit;
        } else {
            $errormsg[] = "Error creating account";
        }
    }

    // Store messages in session
    $_SESSION['error_messages'] = $errormsg;
    $_SESSION['success_messages'] = $success_message;
    header("Location: buyersregistration.php");
    exit;
}
?>