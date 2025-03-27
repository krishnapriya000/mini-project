<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$signupid = null;

// Try to get signupid from signup table
$get_user_query = "SELECT * FROM signup WHERE signupid = ?";
$stmt = $conn->prepare($get_user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $signupid = $user_data['signupid'] ?? null;
} else {
    // If no rows returned, initialize empty user_data to avoid null errors
    $user_data = array(
        'username' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => ''
    );
}

// If signupid is still not found, try alternate methods
if (!$signupid) {
    // Check if it's directly in the session
    $signupid = isset($_SESSION['signupid']) ? $_SESSION['signupid'] : null;
    
    // If still not found, try using user_id as signupid
    if (!$signupid) {
        $signupid = $user_id;
    }
}

// Make sure we have a signupid before proceeding
if (!$signupid) {
    // Display error and link to login
    echo "Session error: User identification not found. Please <a href='login.php'>login again</a>.";
    exit();
}

// Fetch user details
$user_details = array(
    'username' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => ''
);

if ($signupid) {
    $user_query = "SELECT username, email, phone, address, city 
                   FROM signup 
                   WHERE signupid = ? 
                   LIMIT 1";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result && $user_result->num_rows > 0) {
        $user_details = $user_result->fetch_assoc();
    }
}

// Fetch user orders with their items and product details
$orders_query = "SELECT * FROM orders_table 
                WHERE signupid = ? 
                ORDER BY created_at DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$orders_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Nunito', Arial, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeaea;
        }
        
        .page-title-icon {
            background-color: #3a77bf;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .page-title h1 {
            font-size: 28px;
            color: #333;
            font-weight: 700;
        }
        
        .orders-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 22px;
            color: #3a77bf;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 700;
        }
        
        .order-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background-color: #f5f9ff;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .order-id {
            font-weight: 700;
            color: #3a77bf;
            font-size: 16px;
        }
        
        .order-date {
            color: #777;
            font-size: 14px;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-processing {
            background-color: #fff8e1;
            color: #ff9800;
            border: 1px solid #ffe082;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #4caf50;
            border: 1px solid #c8e6c9;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #f44336;
            border: 1px solid #ffcdd2;
        }
        
        .status-shipped {
            background-color: #e3f2fd;
            color: #2196f3;
            border: 1px solid #bbdefb;
        }
        
        .status-pending {
            background-color: #f3e5f5;
            color: #9c27b0;
            border: 1px solid #e1bee7;
        }
        
        .order-content {
            padding: 20px;
        }
        
        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .order-details {
            flex: 1;
            min-width: 250px;
        }
        
        .detail-row {
            margin-bottom: 8px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            width: 140px;
            margin-right: 10px;
        }
        
        .detail-value {
            color: #333;
            flex: 1;
        }
        
        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: #3a77bf;
            text-align: right;
            min-width: 150px;
            margin-top: 10px;
        }
        
        .order-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            outline: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .action-btn i {
            margin-right: 8px;
        }
        
        .cancel-btn {
            background-color: #fff;
            color: #f44336;
            border: 1px solid #f44336;
        }
        
        .cancel-btn:hover {
            background-color: #f44336;
            color: #fff;
        }
        
        .review-btn {
            background-color: #fff;
            color: #ff9800;
            border: 1px solid #ff9800;
        }
        
        .review-btn:hover {
            background-color: #ff9800;
            color: #fff;
        }
        
        .view-details-btn {
            background-color: #fff;
            color: #3a77bf;
            border: 1px solid #3a77bf;
        }
        
        .view-details-btn:hover {
            background-color: #3a77bf;
            color: #fff;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            color: #3a77bf;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-orders-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-orders-text {
            font-size: 18px;
            color: #888;
            margin-bottom: 20px;
        }
        
        .shop-now-btn {
            background-color: #3a77bf;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        
        .shop-now-btn:hover {
            background-color: #2c5c94;
        }
        
        /* Get products from cart */
        .fetch-products-btn {
            background-color: #673ab7;
            color: white;
            margin-top: 10px;
            border-radius: 4px;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        
        .fetch-products-btn:hover {
            background-color: #5e35b1;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 10px;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        .order-products {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .product-item {
            position: relative;
            min-width: 80px;
        }
        
        .product-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        
        .product-quantity {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: #3a77bf;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <div class="main-container">
        <!-- Back Button -->
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Homepage
        </a>
        
        <!-- Page Title -->
        <div class="page-title">
            <div class="page-title-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <h1>My Orders</h1>
        </div>
        
        <!-- Orders Container -->
        <div class="orders-container">
            <h2 class="section-title"><i class="fas fa-shopping-bag"></i> Order History</h2>
            
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <?php while($order = $orders_result->fetch_assoc()): ?>
                    <?php
                        // Format date
                        $order_date = date('F j, Y', strtotime($order['created_at']));
                        
                        // Determine status class
                        $status_class = '';
                        switch (strtolower($order['order_status'])) {
                            case 'processing':
                                $status_class = 'status-processing';
                                break;
                            case 'delivered':
                                $status_class = 'status-delivered';
                                break;
                            case 'cancelled':
                                $status_class = 'status-cancelled';
                                break;
                            case 'shipped':
                                $status_class = 'status-shipped';
                                break;
                            case 'pending':
                                $status_class = 'status-pending';
                                break;
                            default:
                                $status_class = 'status-processing';
                        }
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                            <div class="order-date"><?php echo $order_date; ?></div>
                            <div class="order-status <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-content">
                            <div class="order-summary">
                                <div class="order-details">
                                    <div class="detail-row">
                                        <div class="detail-label">Payment ID:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($order['payment_id'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Payment Status:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($order['payment_status'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Shipping Address:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($order['shipping_address'] ?? 'Not specified'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="order-total">
                                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="order-products">
                                
                                <?php
                                // Safely get items from orders_table with proper error checking
                                $items = [];
                                if (isset($order['items']) && !empty($order['items'])) {
                                    $items = json_decode($order['items'], true);
                                }
                                
                                if ($items && is_array($items) && !empty($items)) {
                                    foreach ($items as $item) {
                                        // Improved image path handling
                                        $imagePath = 'images/placeholder.jpg'; // Default placeholder
                                        
                                        // Fetch product details including image from product_table
                                        $product_query = "SELECT p.*, pi.image_url 
                                                        FROM product_table p 
                                                        LEFT JOIN product_images pi ON p.product_id = pi.product_id 
                                                        WHERE p.product_id = ? 
                                                        LIMIT 1";
                                        $stmt = $conn->prepare($product_query);
                                        $stmt->bind_param("i", $item['product_id']);
                                        $stmt->execute();
                                        $product_result = $stmt->get_result();
                                        $product_data = $product_result->fetch_assoc();
                                        
                                        // Enhanced image path resolution
                                        if ($product_data && !empty($product_data['image_url'])) {
                                            $potential_paths = [
                                                $_SERVER['DOCUMENT_ROOT'] . '/baby/uploads/' . $product_data['image_url'],
                                                $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $product_data['image_url'],
                                                'uploads/' . $product_data['image_url'],
                                                'images/' . $product_data['image_url']
                                            ];

                                            foreach ($potential_paths as $path) {
                                                if (file_exists($path)) {
                                                    $imagePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Sanitize image path
                                        $imagePath = htmlspecialchars(trim($imagePath));
                                        ?>
                                        <div class="product-item">
                                            <img src="<?php echo $imagePath; ?>" 
                                                 alt="<?php echo htmlspecialchars($product_data['product_name'] ?? 'Product Image'); ?>"
                                                 onerror="this.onerror=null; this.src='images/placeholder.jpg';"
                                                 style="object-fit: cover; width: 80px; height: 80px;">
                                            <span class="product-quantity">x<?php echo htmlspecialchars($item['quantity'] ?? 1); ?></span>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    // Fallback for orders without items data
                                    ?>
                                    <div class="product-item">
                                        <img src="images/placeholder.jpg" alt="Order Item">
                                        <span class="product-quantity">1</span>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            
                            <div class="order-actions">
                                <?php if ($order['order_status'] == 'processing'): ?>
                                    <a href="cancel_order.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="action-btn cancel-btn" onclick="return confirm('Are you sure you want to cancel this order?');">
                                        <i class="fas fa-times-circle"></i> Cancel Order
                                    </a>
                                <?php endif; ?>

                                    

                                
                                <?php if ($order['order_status'] == 'delivered'): ?>
                                    <a href="write_review.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="action-btn review-btn">
                                        <i class="fas fa-star"></i> Write Review
                                    </a>
                                <?php endif; ?>
                                
                                <a href="view_details.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="action-btn view-details-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <div class="empty-orders-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="empty-orders-text">You haven't placed any orders yet.</div>
                    <a href="index.php" class="shop-now-btn">Shop Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>