<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Debug line to check user ID
echo "User ID: " . $_SESSION['user_id'] . "<br>";

// Modified query to use LEFT JOINs and check the data
$query = "SELECT o.order_id, 
          o.created_at,
          o.payment_status,
          o.order_status,
          p.name as product_name,
          p.price,
          p.image_url,
          ci.quantity
          FROM orders_table o
          LEFT JOIN cart_items ci ON o.order_id = ci.order_id
          LEFT JOIN product_table p ON ci.product_id = p.product_id
          WHERE o.signupid = ?
          ORDER BY o.created_at DESC";

// Add more debug queries to check data integrity
try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug queries
    echo "Checking cart_items for this user:<br>";
    $cart_check = $conn->query("SELECT COUNT(*) as count FROM cart_items WHERE signupid = " . $_SESSION['user_id']);
    $cart_row = $cart_check->fetch_assoc();
    echo "Cart items count: " . $cart_row['count'] . "<br>";
    
    // Check a specific order's details
    $order_check = $conn->query("SELECT order_id, payment_status FROM orders_table WHERE signupid = " . $_SESSION['user_id'] . " LIMIT 1");
    if ($order = $order_check->fetch_assoc()) {
        echo "Sample order ID: " . $order['order_id'] . "<br>";
        
        // Check if this order_id exists in cart_items
        $items_check = $conn->query("SELECT COUNT(*) as count FROM cart_items WHERE order_id = '" . $order['order_id'] . "'");
        $items_row = $items_check->fetch_assoc();
        echo "Items for this order: " . $items_row['count'] . "<br>";
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
        /* ... existing styles ... */
        .header {
            background-color: #333;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .header a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }
        .no-orders {
            text-align: center;
            padding: 40px;
        }
        .no-orders a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .product-details {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .order-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            background-color: #e9ecef;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php">Home</a>
        <a href="cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>My Orders</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $current_order = null;
            while ($row = $result->fetch_assoc()):
                if ($current_order !== $row['order_id']):
                    if ($current_order !== null): ?>
                        </div> <!-- Close previous order card -->
                    <?php endif;
                    $current_order = $row['order_id'];
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h2>Order #<?php echo htmlspecialchars($row['order_id']); ?></h2>
                            <p>Ordered on: <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></p>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo strtolower($row['payment_status']); ?>">
                                <?php echo ucfirst($row['payment_status']); ?>
                            </span>
                        </div>
                    </div>
            <?php endif; ?>
                
                <div class="product-details">
                    <?php if (!empty($row['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                             class="product-image"
                             onerror="this.src='placeholder.jpg'">
                    <?php endif; ?>
                    <div>
                        <h3><?php echo htmlspecialchars($row['product_name'] ?? 'Product Name Not Available'); ?></h3>
                        <p>Quantity: <?php echo $row['quantity'] ?? '0'; ?></p>
                        <p>Price: ₹<?php echo number_format($row['price'] ?? 0, 2); ?></p>
                    </div>
                </div>
            
            <?php endwhile;
            if ($current_order !== null): ?>
                </div> <!-- Close last order card -->
            <?php endif; ?>
        <?php else: ?>
            <div class="order-card no-orders">
                <p>No orders found.</p>
                <a href="index.php">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>