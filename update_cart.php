<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user's signup ID
$user_id = $_SESSION['user_id'];
$get_signupid_query = "SELECT signupid FROM user_table WHERE user_id = ?";
$stmt = $conn->prepare($get_signupid_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $signupid = $user_data['signupid'];
} else {
    // If we can't find the signupid, check if it's directly in the session
    $signupid = isset($_SESSION['signupid']) ? $_SESSION['signupid'] : null;
    
    // If still not found, try using user_id as signupid
    if ($signupid === null) {
        $signupid = $user_id;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id']) && isset($_POST['action'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $action = $_POST['action'];
    
    // Verify the cart item belongs to the user
    $verify_query = "SELECT ci.*, ct.cart_id 
                    FROM cart_items ci 
                    JOIN cart_table ct ON ci.cart_id = ct.cart_id 
                    WHERE ci.cart_item_id = ? AND ci.signupid = ? AND ct.status = 'active'";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $cart_item_id, $signupid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $cart_item = $result->fetch_assoc();
        
        switch ($action) {
            case 'increase':
                // Increase quantity
                $new_quantity = $cart_item['quantity'] + 1;
                $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $new_quantity, $cart_item_id);
                $stmt->execute();
                break;
                
            case 'decrease':
                // Decrease quantity, but don't go below 1
                $new_quantity = max(1, $cart_item['quantity'] - 1);
                $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $new_quantity, $cart_item_id);
                $stmt->execute();
                break;
                
            case 'remove':
                // Instead of deleting, add a status column to cart_items and update it
                // First, check if the status column exists, if not, add it
                $check_column = "SHOW COLUMNS FROM cart_items LIKE 'status'";
                $result = $conn->query($check_column);
                if ($result->num_rows === 0) {
                    // Add status column if it doesn't exist
                    $add_column = "ALTER TABLE cart_items ADD COLUMN status ENUM('active', 'disabled') DEFAULT 'active'";
                    $conn->query($add_column);
                }
                
                // Update the status to 'disabled' instead of deleting
                $update_query = "UPDATE cart_items SET status = 'disabled' WHERE cart_item_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $cart_item_id);
                $stmt->execute();
                break;
        }
    }
    
    // Redirect back to the cart page
    header("Location: cart.php");
    exit();
} else {
    // Invalid request
    header("Location: cart.php");
    exit();
}
?>