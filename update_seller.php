<?php
session_start();
require 'db.php';

if (!isset($_SESSION['signupid'])) {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['signupid'];

if (isset($_POST['update_seller'])) {
    $business_name = trim($_POST['business_name']);
    $description = trim($_POST['description']);

    // Validate input
    if (empty($business_name) || empty($description)) {
        echo "All fields are required!";
        exit();
    }

    // Update seller details
    $sql = "UPDATE seller SET business_name = ?, description = ? WHERE signupid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $business_name, $description, $seller_id);

    if ($stmt->execute()) {
        echo "<script>alert('Seller details updated successfully!'); window.location.href='sellerdashboard.php';</script>";
    } else {
        echo "Error updating details: " . $conn->error;
    }
}
?>
