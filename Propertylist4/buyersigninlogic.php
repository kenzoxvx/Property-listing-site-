<?php
    session_start();
    include("connect.php"); // Include the database connection

    $server = $_SERVER['REQUEST_METHOD'];
    $errormsg = [];
    $successmsg = [];

    if (isset($_POST['submit']) && $server === "POST") {
        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            $errormsg[] = "Missing form inputs. Please fill all required fields.";
        } else {
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            $PasswordEl = htmlspecialchars(trim($_POST['password']));
            $user_type = "buyer";

            if (!$email) {
                $errormsg[] = "Invalid email format.";
            }

            if (empty($errormsg)) {
                try {
                    $select_user = "SELECT * FROM users WHERE email=:email AND user_type=:user_type LIMIT 1";
                    $stmt = $pdo->prepare($select_user);
                    $stmt->execute(["email" => $email, "user_type" => $user_type]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($row) {
                        if (password_verify($PasswordEl, $row['password_hash'])) {
                            $_SESSION['buyer_id'] = $row['id'];
                            $_SESSION['success_message'] = "Account created successfully";
                            header("location: buyerpage.php");
                            exit();
                            
                        } else {
                            $errormsg[] = "Incorrect password";
                            header("location: buyersregistration.php");
                        }
                    } else {
                        $errormsg[] = "Account not found.";
                        header("location: buyersregistration.php");
                    }
                } catch (PDOException $e) {
                    $errormsg[] = "Database error: " . $e->getMessage();
                }
            }
        }

        $_SESSION['error_messages'] = $errormsg;
        $_SESSION['success_message'] = $successmsg;
    }
?>
