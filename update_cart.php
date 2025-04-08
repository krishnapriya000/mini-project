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
    
    // Start transaction for stock management
    $conn->begin_transaction();
    
    try {
        // Get current cart item data with product information
        $get_item_query = "SELECT ci.*, p.stock_quantity 
                          FROM cart_items ci
                          JOIN product_table p ON ci.product_id = p.product_id
                          WHERE ci.cart_item_id = ?";
        
        $stmt = $conn->prepare($get_item_query);
        $stmt->bind_param("i", $cart_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            $current_quantity = $item['quantity'];
            $product_id = $item['product_id'];
            $stock_quantity = $item['stock_quantity'];
            
            // Process action
            switch ($action) {
                case 'increase':
                    // Check if increasing quantity is possible
                    if ($current_quantity < $stock_quantity) {
                        $new_quantity = $current_quantity + 1;
                        
                        $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("ii", $new_quantity, $cart_item_id);
                        $stmt->execute();
                        
                        $_SESSION['cart_message'] = "Cart updated successfully!";
                    } else {
                        $_SESSION['cart_error'] = "Cannot add more items. Maximum stock reached.";
                    }
                    break;
                    
                case 'decrease':
                    if ($current_quantity > 1) {
                        $new_quantity = $current_quantity - 1;
                        
                        $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("ii", $new_quantity, $cart_item_id);
                        $stmt->execute();
                        
                        $_SESSION['cart_message'] = "Cart updated successfully!";
                    } else {
                        // If trying to decrease below 1, remove the item
                        $delete_query = "UPDATE cart_items SET status = 'disabled' WHERE cart_item_id = ?";
                        $stmt = $conn->prepare($delete_query);
                        $stmt->bind_param("i", $cart_item_id);
                        $stmt->execute();
                        
                        $_SESSION['cart_message'] = "Item removed from cart!";
                    }
                    break;
                    
                case 'remove':
                    // Soft delete by setting status to disabled
                    $delete_query = "UPDATE cart_items SET status = 'disabled' WHERE cart_item_id = ?";
                    $stmt = $conn->prepare($delete_query);
                    $stmt->bind_param("i", $cart_item_id);
                    $stmt->execute();
                    
                    $_SESSION['cart_message'] = "Item removed from cart!";
                    break;
                    
                default:
                    $_SESSION['cart_error'] = "Invalid action!";
                    break;
            }
            
            // Commit the transaction
            $conn->commit();
            
        } else {
            $_SESSION['cart_error'] = "Cart item not found!";
            $conn->rollback();
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['cart_error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect back to cart page
    header("Location: cart.php");
    exit();
} else {
    $_SESSION['cart_error'] = "Invalid request!";
    header("Location: cart.php");
    exit();
}
?>