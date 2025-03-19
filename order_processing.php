<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');
// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
// Get form data
$order_id = $_POST['order_id'] ?? '';
$signupid = $_POST['signupid'] ?? '';
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$shipping_address = $_POST['address'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$payment_id = $_POST['payment_id'] ?? '';
$total_amount = $_POST['total_amount'] ?? 0;
// Validate required fields
if (!$order_id || !$signupid || !$fullname || !$email || !$phone || !$shipping_address || !$payment_method || !$payment_id || !$total_amount) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
// Start transaction
$conn->begin_transaction();
try {
    // 1. Insert into orders_table
    $insert_order_query = "INSERT INTO orders_table (order_id, signupid, payment_id, total_amount, shipping_address) 
                          VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_order_query);
    $stmt->bind_param("sisds", $order_id, $signupid, $payment_id, $total_amount, $shipping_address);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    
    // 2. Insert into payment_table
    $insert_payment_query = "INSERT INTO payment_table (order_id, signupid, payment_id, amount, payment_method, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?)";
    $payment_status = ($payment_method === 'cod') ? 'pending' : 'paid';
    $stmt = $conn->prepare($insert_payment_query);
    $stmt->bind_param("sisdss", $order_id, $signupid, $payment_id, $total_amount, $payment_method, $payment_status);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create payment record: " . $stmt->error);
    }
    
    // 3. Get cart items for this user
    $cart_query = "SELECT * FROM cart_table WHERE signupid = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception("No items found in cart");
    }
    
    // 4. Insert cart items into order_items_table
    $insert_items_query = "INSERT INTO order_items_table (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_items_query);
    
    while ($cart_item = $cart_result->fetch_assoc()) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $price = $cart_item['price'];
        
        $stmt->bind_param("siid", $order_id, $product_id, $quantity, $price);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add order item: " . $stmt->error);
        }
        
        // 5. Update product inventory (optional)
        $update_inventory_query = "UPDATE products_table SET stock = stock - ? WHERE product_id = ? AND stock >= ?";
        $inventory_stmt = $conn->prepare($update_inventory_query);
        $inventory_stmt->bind_param("iii", $quantity, $product_id, $quantity);
        
        if (!$inventory_stmt->execute()) {
            throw new Exception("Failed to update inventory: " . $inventory_stmt->error);
        }
        
        if ($inventory_stmt->affected_rows === 0) {
            throw new Exception("Not enough stock for product ID: " . $product_id);
        }
    }
    
    // 6. Clear the user's cart
    $clear_cart_query = "DELETE FROM cart_table WHERE signupid = ?";
    $stmt = $conn->prepare($clear_cart_query);
    $stmt->bind_param("i", $signupid);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to clear cart: " . $stmt->error);
    }
    
    // 7. Create customer record if not exists
    $check_customer_query = "SELECT * FROM customers_table WHERE signupid = ?";
    $stmt = $conn->prepare($check_customer_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        $insert_customer_query = "INSERT INTO customers_table (signupid, fullname, email, phone) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_customer_query);
        $stmt->bind_param("isss", $signupid, $fullname, $email, $phone);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create customer record: " . $stmt->error);
        }
    }
    
    // Commit transaction if everything is successful
    $conn->commit();
    
    // 8. Send order confirmation email (implementation depends on your email setup)
    // send_order_confirmation_email($email, $order_id, $total_amount);
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully', 
        'order_id' => $order_id,
        'payment_status' => $payment_status
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log the error (implement your logging mechanism)
    error_log("Order processing error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
} finally {
    // Close the connection
    $conn->close();
}
?>