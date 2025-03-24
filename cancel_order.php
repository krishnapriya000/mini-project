<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: view_order_details.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'];

// Update order status
$query = "UPDATE orders_table 
          SET order_status = 'cancelled' 
          WHERE order_id = ? AND signupid = ? AND order_status = 'processing'";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $order_id, $user_id);

if ($stmt->execute()) {
    echo "<script>
            alert('Order cancelled successfully');
            window.location.href='view_order_details.php';
          </script>";
} else {
    echo "<script>
            alert('Failed to cancel order');
            window.location.href='view_order_details.php';
          </script>";
}
?>