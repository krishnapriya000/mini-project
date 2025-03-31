<?php
session_start();
require 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if (!isset($_POST['product_id'], $_POST['action'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Get user_id from user_table using signupid
$signupid = $_SESSION['user_id'];
$user_query = "SELECT user_id FROM user_table WHERE signupid = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user_id = $result->fetch_assoc()['user_id'];
$productId = (int)$_POST['product_id'];
$action = $_POST['action'] === 'add' ? 'add' : 'remove';

try {
    // Verify product exists
    $checkProduct = $conn->prepare("SELECT 1 FROM product_table WHERE product_id = ?");
    $checkProduct->bind_param("i", $productId);
    $checkProduct->execute();
    
    if (!$checkProduct->get_result()->num_rows) {
        throw new Exception("Product doesn't exist");
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $productId);
    } else {
        $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $productId);
    }
    
    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}