<?php
    session_start();
    include("connect.php"); // Include the database connection

    $server = $_SERVER['REQUEST_METHOD'];
    $errormsg = [];

    if (isset($_POST['submit']) && $server === "POST") {
        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            $errormsg[] = "Missing form inputs. Please fill all required fields.";
        } else {
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            $PasswordEl = htmlspecialchars(trim($_POST['password']));

            if (!$email) {
                $errormsg[] = "Invalid email format.";
            }

            if (empty($errormsg)) {
                try {
                    $select_user = "SELECT * FROM users WHERE email=:email LIMIT 1";
                    $stmt = $pdo->prepare($select_user);
                    $stmt->execute(["email" => $email]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($row) {
                        if (password_verify($PasswordEl, $row['password_hash'])) {
                            $_SESSION['realtor_id'] = $row['id'];
                            header("location: realtorpage.php");
                            exit();
                            
                        } else {
                            $errormsg[] = "Incorrect password";
                            header("location: realtorregistration.php");
                        }
                    } else {
                        $errormsg[] = "Account not found.";
                        header("location: realtorregistration.php");
                    }
                } catch (PDOException $e) {
                    $errormsg[] = "Database error: " . $e->getMessage();
                }
            }
        }

        $_SESSION['error_messages'] = $errormsg;
        
    }
?>
