<?php 
require_once "connect.php";
session_start();

$server = $_SERVER['REQUEST_METHOD'];
$errors = [];

if ($server == "POST") {
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $passwordEl = trim($_POST['password']);
    $phonenumber = htmlspecialchars(trim($_POST['phonenumber']));
    $user_type = "realtor";
    $currentDate = date("Y-m-d");  // Use correct date format


    if (!$email) {
        $errors[] = "Invalid email format.";
    }

    if (empty($fullname) || empty($email) || empty($passwordEl) || empty($phonenumber)) {
        $errors[] = "All fields are required.";
    }

    // Check if email or phone number already exists
    $select_number = "SELECT * FROM users 
    WHERE (email = :email OR phone_number = :phone_number) 
    AND user_type = :user_type";
    $stmt = $pdo->prepare($select_number);
    $stmt->execute(["email" => $email, "phone_number" => $phonenumber, 
    "user_type" => $user_type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['email'] == $email) {
        $errors[] = "Email already exists.";
    }
    if ($user && $user['phone_number'] == $phonenumber) {
        $errors[] = "Phone number already exists.";
    }

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($passwordEl, PASSWORD_DEFAULT);
        
        

        // Insert user into database
        $insert_user = "INSERT INTO users (full_name, email, password_hash, phone_number, user_type, created_at)
                        VALUES(:fullname, :email, :passwordhash, :phone_number, :user_type, :createdAt)";
        $stmt = $pdo->prepare($insert_user);

        if ($stmt->execute([
            "fullname" => $fullname,
            "email" => $email,
            "passwordhash" => $hashed_password,
            "phone_number" => $phonenumber,
            "user_type" => $user_type,
            "createdAt" => $currentDate
        ])) {
            $_SESSION['realtor_id'] = $pdo->lastInsertId();
            header("location: realtorpage.php");
            exit();
        } else {
            $errors[] = "Failed to register user. Please try again.";
        }
    }

    // Store errors in session
    $_SESSION['error_messages'] = $errors;
    header("Location: realtorregistration.php"); // Redirect back to show errors
    exit();
}
?>
    