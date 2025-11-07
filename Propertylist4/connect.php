<?php 
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $dbname = "propertydb";

    try {
        $pdo = new PDO("mysql:host=$hostname; dbname=$dbname", $username, $password);
        
        // Set error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // set default fetch to fetch associative

        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        


    } catch (PDOException $e) {
        echo "Connection failed" . $e->getMessage();
    }
    
?>