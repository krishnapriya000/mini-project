<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['seller_id'];

// Get orders for this seller by joining orders_table, cart_items, product_table
$orders_query = "
    SELECT DISTINCT o.order_id, 
           o.created_at,
           o.order_status, 
           o.payment_status,
           o.shipping_address,
           o.total_amount,
           s.username as customer_name,
           s.email as customer_email,
           s.phone as customer_phone
    FROM orders_table o
    JOIN cart_items ci ON o.order_id = ci.order_id
    JOIN product_table p ON ci.product_id = p.product_id
    JOIN signup s ON o.signupid = s.signupid
    WHERE p.seller_id = ?
    ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all order items for the orders
$items_query = "
    SELECT ci.order_id,
           p.name as product_name,
           p.image_url as product_image,
           ci.quantity,
           ci.price as sold_price,
           (ci.quantity * ci.price) as subtotal
    FROM cart_items ci
    JOIN product_table p ON ci.product_id = p.product_id
    WHERE p.seller_id = ? AND ci.order_id IN (
        SELECT DISTINCT o.order_id
        FROM orders_table o
        JOIN cart_items ci2 ON o.order_id = ci2.order_id
        JOIN product_table p2 ON ci2.product_id = p2.product_id
        WHERE p2.seller_id = ?
    )";

$stmt = $conn->prepare($items_query);
$stmt->bind_param("ii", $seller_id, $seller_id);
$stmt->execute();
$all_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize items by order_id for easy access
$order_items = [];
foreach ($all_items as $item) {
    $order_items[$item['order_id']][] = $item;
}

// Process order status update if requested
if (isset($_POST['mark_completed']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    
    $update_query = "UPDATE orders_table SET order_status = 'completed' WHERE order_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("s", $order_id);
    
    if ($stmt->execute()) {
        header("Location: order.php?success=order_completed");
        exit;
    } else {
        $error_message = "Failed to update order status";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-header i {
            font-size: 28px;
            color: #b8451f;
            margin-right: 15px;
        }

        .page-title {
            font-size: 28px;
            color: #333;
            margin: 0;
        }

        .order-count {
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 14px;
            color: #666;
            margin-left: 15px;
        }

        .orders-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 5px solid #b8451f;
            margin-bottom: 30px;
        }

        .no-orders {
            text-align: center;
            padding: 60px 0;
        }

        .no-orders i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-orders h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .no-orders p {
            color: #666;
            font-size: 16px;
        }

        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-family: monospace;
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }

        .status-processing { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-pending { background: #cce5ff; color: #004085; }

        .order-body {
            padding: 20px;
        }

        .customer-info {
            display: flex;
            margin-bottom: 20px;
            gap: 30px;
        }

        .info-column {
            flex: 1;
        }

        .info-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .customer-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .customer-contact {
            color: #666;
            margin-bottom: 3px;
        }

        .shipping-address {
            white-space: pre-line;
            line-height: 1.4;
        }

        .order-items {
            margin-top: 20px;
        }

        .items-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .item-list {
            border-top: 1px solid #eee;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
        }

        .item-price {
            width: 100px;
            text-align: right;
        }

        .item-quantity {
            width: 70px;
            text-align: center;
        }

        .price-amount {
            font-weight: 500;
        }

        .price-subtotal {
            color: #666;
            font-size: 13px;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .order-total {
            font-size: 18px;
            font-weight: 600;
        }

        .action-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-button:hover {
            background: #218838;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #b8451f;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: #943618;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .orders-container {
                padding: 20px;
            }
            
            .customer-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .order-item {
                flex-wrap: wrap;
            }
            
            .item-details {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .item-price {
                width: auto;
                margin-left: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <i class="fas fa-shopping-cart"></i>
            <h1 class="page-title">Orders</h1>
            <span class="order-count"><?php echo count($orders); ?> orders</span>
        </div>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders</h3>
                    <p>You haven't received any orders yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="customer-info">
                                <div class="info-column">
                                    <div class="info-label">Customer</div>
                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="customer-contact">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?>
                                    </div>
                                    <?php if (!empty($order['customer_phone'])): ?>
                                    <div class="customer-contact">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="info-column">
                                    <div class="info-label">Shipping Address</div>
                                    <div class="shipping-address">
                                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                    </div>
                                </div>
                                
                                <div class="info-column">
                                    <div class="info-label">Payment</div>
                                    <div class="customer-name"><?php echo ucfirst($order['payment_status']); ?></div>
                                </div>
                            </div>

                            <div class="order-items">
                                <div class="items-label">Order Items</div>
                                <div class="item-list">
                                    <?php if (isset($order_items[$order['order_id']])): ?>
                                        <?php foreach ($order_items[$order['order_id']] as $item): ?>
                                            <div class="order-item">
                                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                                
                                                <div class="item-details">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                </div>
                                                
                                                <div class="item-quantity">
                                                    <?php echo $item['quantity']; ?> × 
                                                </div>
                                                
                                                <div class="item-price">
                                                    <div class="price-amount">₹<?php echo number_format($item['sold_price'], 2); ?></div>
                                                    <div class="price-subtotal">₹<?php echo number_format($item['subtotal'], 2); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No items found for this order.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                Total: ₹<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                            
                            <?php if ($order['order_status'] === 'processing'): ?>
                                <form method="post">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                    <button type="submit" name="mark_completed" class="action-button">
                                        <i class="fas fa-check"></i> Mark as Completed
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="sellerdashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>