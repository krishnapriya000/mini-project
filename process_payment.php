<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate payment data
if (!isset($_POST['razorpay_payment_id']) || !isset($_POST['order_id']) || !isset($_POST['amount'])) {
    header("Location: cart.php?error=payment_failed");
    exit();
}

$payment_id = $_POST['razorpay_payment_id'];
$order_id = $_POST['order_id'];
$amount = $_POST['amount'];
$user_id = $_SESSION['user_id'];

// Get user's signupid
$get_signupid_query = "SELECT signupid FROM user_table WHERE user_id = ?";
$stmt = $conn->prepare($get_signupid_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$signupid = $user_data['signupid'];

// Get shipping details
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$shipping_address = "$address, $city - $postal_code";

// Start transaction
$conn->begin_transaction();

try {
    // Create order in orders_table
    $order_query = "INSERT INTO orders_table 
                   (order_id, signupid, payment_id, total_amount, shipping_address, order_status, payment_status, created_at) 
                   VALUES (?, ?, ?, ?, ?, 'processing', 'paid', NOW())";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("sisds", $order_id, $signupid, $payment_id, $amount, $shipping_address);
    $stmt->execute();
    
    // Get cart items
    $cart_query = "SELECT ci.*, p.name, p.price, ct.cart_id
                  FROM cart_items ci
                  JOIN product_table p ON ci.product_id = p.product_id
                  JOIN cart_table ct ON ci.cart_id = ct.cart_id
                  WHERE ci.signupid = ? AND ct.status = 'active'";
    
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    $cart_items = $stmt->get_result();
    $cart_id = null;
    
    // Add each item to order_items table
    while ($item = $cart_items->fetch_assoc()) {
        $cart_id = $item['cart_id']; // Save cart_id for later
        
        $item_query = "INSERT INTO order_items 
                      (order_id, signupid, product_id, product_name, quantity, price, subtotal) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $subtotal = $item['quantity'] * $item['price'];
        
        $stmt = $conn->prepare($item_query);
        $stmt->bind_param("siisidd", $order_id, $signupid, $item['product_id'], 
                         $item['name'], $item['quantity'], $item['price'], $subtotal);
        $stmt->execute();
    }
    
    // Update cart status to 'completed'
    if ($cart_id) {
        $update_cart_query = "UPDATE cart_table SET status = 'completed' WHERE cart_id = ?";
        $stmt = $conn->prepare($update_cart_query);
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect to order confirmation page
    header("Location: order_confirmation.php?order_id=" . urlencode($order_id));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log error
    error_log("Payment processing error: " . $e->getMessage());
    
    // Redirect with error
    header("Location: cart.php?error=payment_processing_failed");
    exit();
}