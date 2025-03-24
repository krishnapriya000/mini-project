<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's orders
$query = "SELECT o.*, 
          p.name as product_name, 
          CONCAT('uploads/products/', p.image_url) as image_url,
          p.price, 
          ci.quantity
          FROM orders_table o
          LEFT JOIN cart_items ci ON o.order_id = ci.order_id
          LEFT JOIN product_table p ON ci.product_id = p.product_id
          WHERE o.signupid = ?
          ORDER BY o.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add this debug code temporarily
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        var_dump($row['image_url']); // Let's see what we get now
        $result->data_seek(0); // Reset the result pointer
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #333;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .header .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        .header .nav-links a:hover {
            color: #ddd;
        }
        .header .cart-icon {
            position: relative;
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        .header .cart-icon .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
        }
        .order-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        .product-details {
            flex-grow: 1;
        }
        .product-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .product-meta {
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            margin-left: 5px;
        }
        .status-processing { background-color: #fff3cd; color: #856404; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #cce5ff; color: #004085; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .print-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .print-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="logo">BABYCUBS</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
           
            <a href="order_confirmation.php">My Orders</a>
            
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <a href="logout.php">Logout</a>
            </a>
        </div>
    </div>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 30px;">My Orders</h1>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $current_order = null;
            
            while ($row = $result->fetch_assoc()):
                if ($current_order !== $row['order_id']):
                    if ($current_order !== null):
                        echo "</div>"; // Close previous order card
                    endif;
                    $current_order = $row['order_id'];
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h2>Order #<?php echo htmlspecialchars($row['order_id']); ?></h2>
                                <p>Ordered on: <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></p>
                            </div>
                            <button class="print-button" onclick="printOrder('<?php echo $row['order_id']; ?>')">
                                <i class="fas fa-download"></i> Download Receipt
                            </button>
                        </div>
                        <p>
                            <strong>Order Status:</strong>
                            <span class="status-badge status-<?php echo strtolower($row['order_status']); ?>">
                                <?php echo ucfirst($row['order_status']); ?>
                            </span>
                        </p>
                        <p>
                            <strong>Payment Status:</strong>
                            <span class="status-badge status-<?php echo strtolower($row['payment_status']); ?>">
                                <?php echo ucfirst($row['payment_status']); ?>
                            </span>
                        </p>
                    </div>
            <?php 
                endif;
                if ($row['product_name']): // Only show if product exists
            ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                             class="product-image"
                             onerror="this.src='placeholder.jpg'">
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                            <div class="product-meta">
                                <div>Quantity: <?php echo intval($row['quantity']); ?></div>
                                <div>Price: ₹<?php echo number_format(floatval($row['price']), 2); ?></div>
                            </div>
                        </div>
                    </div>
            <?php 
                endif;
            endwhile;
            
            if ($current_order !== null):
                echo "</div>"; // Close last order card
            endif;
            ?>

        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <p>No orders found.</p>
                <a href="index.php" style="color: #007bff; text-decoration: none;">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function printOrder(orderId) {
        const printWindow = window.open(`print_receipt.php?order_id=${orderId}`, '_blank');
        printWindow.onload = function() {
            printWindow.print();
        };
    }
    </script>
</body>
</html>