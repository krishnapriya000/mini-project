<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "baby_db";

try {
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
   // echo "Connected successfully";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>