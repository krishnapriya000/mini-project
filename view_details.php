<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: view_order_details.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'];

// Get order details with product information
$query = "SELECT o.*, p.name as product_name, p.image_url, p.price, ci.quantity 
          FROM orders_table o
          LEFT JOIN cart_items ci ON o.order_id = ci.order_id
          LEFT JOIN product_table p ON ci.product_id = p.product_id
          WHERE o.order_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-delivered {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-processing {
            background: #e3f2fd;
            color: #1976d2;
        }
        .product-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .shipping-address {
            background: #fff;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin: 20px 0;
        }
        .product-image {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-header">
            <div>
                <h1>Order Details</h1>
                <p>Order #<?php echo $order_id; ?></p>
            </div>
            <span class="status-badge status-<?php echo strtolower($order['order_status'] ?? ''); ?>">
                <?php echo $order['order_status'] ?? ''; ?>
            </span>
        </div>

        <div class="product-details">
            <?php if (!empty($order['image_url'])): ?>
                <img src="seller/<?php echo $order['image_url']; ?>" 
                     alt="Product Image"
                     class="product-image"
                     onerror="this.src='placeholder.jpg'">
            <?php endif; ?>

            <p><strong>Order Date:</strong> 
                <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
            </p>
            
            <p><strong>Payment Status:</strong> 
                <?php echo $order['payment_status'] ?? ''; ?>
            </p>

            <?php if (!empty($order['product_name'])): ?>
                <p><strong>Product:</strong> 
                    <?php echo $order['product_name']; ?>
                </p>
            <?php endif; ?>

            <?php if (isset($order['quantity'])): ?>
                <p><strong>Quantity:</strong> 
                    <?php echo $order['quantity']; ?>
                </p>
            <?php endif; ?>

            <?php if (isset($order['price'])): ?>
                <p><strong>Price per item:</strong> ₹
                    <?php echo $order['price'] ? number_format($order['price'], 2) : '0.00'; ?>
                </p>
            <?php endif; ?>

            <p><strong>Total Amount:</strong> ₹
                <?php echo $order['total_amount'] ? number_format($order['total_amount'], 2) : '0.00'; ?>
            </p>
        </div>

        <?php if (!empty($order['shipping_address'])): ?>
            <div class="shipping-address">
                <h3>Shipping Address</h3>
                <p><?php echo $order['shipping_address']; ?></p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="view_order_details.php" 
               style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">
                Back to Orders
            </a>
        </div>
    </div>
</body>
</html>