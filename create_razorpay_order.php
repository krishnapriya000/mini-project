<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
require_once('razorpay-config.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

try {
    // Initialize Razorpay
    $api = new Api(rzp_test_Z4RWNiIGZc3YxK, Wa9TpadPGeElNjNTWddAUDFp);
    
    // Calculate order amount
    $user_id = $_SESSION['user_id'];
    
    // Fetch cart items and calculate total
    $cart_query = "SELECT ci.*, p.price 
                   FROM cart_items ci
                   JOIN product_table p ON ci.product_id = p.product_id
                   JOIN cart_table ct ON ci.cart_id = ct.cart_id
                   WHERE ci.signupid = ? AND ct.status = 'active'";
    
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subtotal = 0;
    while($item = $result->fetch_assoc()) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $shipping = $subtotal >= 1000 ? 0 : 100;
    $tax = $subtotal * 0.05;
    $total = ($subtotal + $shipping + $tax) * 100; // Convert to paise
    
    // Create Razorpay Order
    $orderData = [
        'receipt'         => 'order_' . time(),
        'amount'          => $total,
        'currency'        => 'INR',
        'payment_capture' => 1
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    
    echo json_encode([
        'order_id' => $razorpayOrder['id'],
        'amount'   => $total
    ]);
    
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>