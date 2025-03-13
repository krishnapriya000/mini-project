<?php
session_start();
include 'db.php'; // Database connection
include 'razorpay-config.php'; // Razorpay API config

use Razorpay\Api\Api;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in"]));
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$total_amount = $_POST['total_amount'] ?? 0;

if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($total_amount)) {
    die(json_encode(["status" => "error", "message" => "All fields are required"]));
}

try {
    // Initialize Razorpay API
    $api = new Api($razorpayKey, $razorpaySecret);
    
    // Create Razorpay order
    $orderData = [
        'receipt' => 'order_' . time(),
        'amount' => $total_amount * 100, // Convert to paise
        'currency' => 'INR',
        'payment_capture' => 1, // Auto-capture
    ];
    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];

    // Insert order details into database
    $query = "INSERT INTO orders (user_id, name, email, phone, address, total_amount, payment_status, razorpay_order_id, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssdss", $user_id, $name, $email, $phone, $address, $total_amount, $razorpayOrderId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "order_id" => $razorpayOrderId, "amount" => $total_amount * 100, "currency" => "INR"]);
    } else {
        throw new Exception("Failed to create order");
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
