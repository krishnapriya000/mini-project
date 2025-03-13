<?php
//require('vendor/autoload.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
session_start();

$key_id = 'rzp_test_Z4RWNiIGZc3YxK';
$key_secret = 'Wa9TpadPGeElNjNTWddAUDFp';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $api = new Razorpay\Api\Api($key_id, $key_secret);
        
        // Verify signature
        $attributes = [
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];
        
        $api->utility->verifyPaymentSignature($attributes);
        
        // Payment successful, save order details
        $conn->begin_transaction();
        
        try {
            // Insert into orders table
            $order_query = "INSERT INTO orders (user_id, total_amount, payment_id, order_status) 
                           VALUES (?, ?, ?, 'confirmed')";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("ids", $_SESSION['user_id'], $total, $_POST['razorpay_payment_id']);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order items
            $items_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                           SELECT ?, ci.product_id, ci.quantity, p.price 
                           FROM cart_items ci 
                           JOIN product_table p ON ci.product_id = p.product_id 
                           WHERE ci.signupid = ?";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
            $stmt->execute();
            
            // Clear cart
            $clear_cart = "UPDATE cart_table SET status = 'completed' 
                          WHERE signupid = ? AND status = 'active'";
            $stmt = $conn->prepare($clear_cart);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            
            $conn->commit();
            
            // Redirect to success page
            header("Location: order_success.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch(Exception $e) {
        header("Location: checkout.php?error=payment_failed");
        exit();
    }
}
?>