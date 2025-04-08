<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user's signup ID based on user_id
$user_id = $_SESSION['user_id'];
$get_signupid_query = "SELECT signupid FROM user_table WHERE user_id = ?";
$stmt = $conn->prepare($get_signupid_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $signupid = $user_data['signupid'];
} else {
    // If we can't find the signupid, use alternatives
    $signupid = isset($_SESSION['signupid']) ? $_SESSION['signupid'] : $user_id;
}

// Process checkout form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Start transaction for stock management and order processing
    $conn->begin_transaction();
    
    try {
        // Get cart items and verify stock one last time
        $get_cart_items = "SELECT ci.product_id, ci.quantity, p.stock_quantity, p.name, ci.cart_id
                          FROM cart_items ci
                          JOIN product_table p ON ci.product_id = p.product_id
                          WHERE ci.signupid = ? AND ci.status = 'active'";
        
        $stmt = $conn->prepare($get_cart_items);
        $stmt->bind_param("i", $signupid);
        $stmt->execute();
        $cart_items = $stmt->get_result();
        
        $stock_issue = false;
        $out_of_stock_items = [];
        $cart_id = null;
        
        // Verify all products are in stock with requested quantity
        while ($item = $cart_items->fetch_assoc()) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $stock_issue = true;
                $out_of_stock_items[] = $item['name'];
            }
            
            // Get cart_id (will be the same for all items)
            if (!$cart_id) {
                $cart_id = $item['cart_id'];
            }
        }
        
        if ($stock_issue) {
            throw new Exception("Some items in your cart are no longer available in the requested quantity: " . 
                               implode(", ", $out_of_stock_items));
        }
        
        // Extract form data for shipping & payment
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $postal_code = $_POST['postal_code'];
        $payment_method = $_POST['payment_method'];
        
        // Calculate order total
        $total_query = "SELECT SUM(ci.quantity * ci.price) as total
                        FROM cart_items ci
                        WHERE ci.signupid = ? AND ci.status = 'active'";
        
        $stmt = $conn->prepare($total_query);
        $stmt->bind_param("i", $signupid);
        $stmt->execute();
        $total_result = $stmt->get_result()->fetch_assoc();
        $total_amount = $total_result['total'];
        
        // Add shipping fee if needed
        if ($total_amount < 1000) {
            $total_amount += 100; // Add shipping fee for orders under 1000
        }
        
        // Add tax (5%)
        $tax_amount = $total_amount * 0.05;
        $total_amount += $tax_amount;
        
        // Generate unique order ID
        $order_id = 'ORD' . time() . rand(100, 999);
        
        // Create the order
        $create_order = "INSERT INTO orders_table (order_id, signupid, fullname, email, phone, 
                         shipping_address, payment_id, total_amount, order_status, payment_status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'processing', ?)";
        
        $payment_id = 'PAY' . time() . rand(100, 999);
        $payment_status = ($payment_method == 'cod') ? 'pending' : 'paid';
        
        $stmt = $conn->prepare($create_order);
        $stmt->bind_param("sssssssds", $order_id, $signupid, $name, $email, $phone, 
                         $address, $payment_id, $total_amount, $payment_status);
        $stmt->execute();
        
        // Reset cart items result to iterate again
        $stmt = $conn->prepare($get_cart_items);
        $stmt->bind_param("i", $signupid);
        $stmt->execute();
        $cart_items = $stmt->get_result();
        
        // Update inventory for each product - THIS IS THE CRITICAL PART
        $update_inventory = "UPDATE product_table 
                            SET stock_quantity = stock_quantity - ? 
                            WHERE product_id = ?";
        
        while ($item = $cart_items->fetch_assoc()) {
            // Decrement stock for each product based on purchase quantity
            $stmt = $conn->prepare($update_inventory);
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            
            // Verify the update actually happened
            if ($stmt->affected_rows <= 0) {
                error_log("Failed to update inventory for product ID: " . $item['product_id']);
                throw new Exception("System error: Unable to update inventory");
            }
        }
        
        // Update cart items to link to order and mark as processed
        $update_cart_items = "UPDATE cart_items 
                             SET order_id = ?, status = 'disabled' 
                             WHERE signupid = ? AND status = 'active'";
        
        $stmt = $conn->prepare($update_cart_items);
        $stmt->bind_param("si", $order_id, $signupid);
        $stmt->execute();
        
        // Update cart status
        $update_cart = "UPDATE cart_table 
                       SET status = 'completed' 
                       WHERE cart_id = ? AND signupid = ? AND status = 'active'";
        
        $stmt = $conn->prepare($update_cart);
        $stmt->bind_param("ii", $cart_id, $signupid);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Redirect to order confirmation page
        $_SESSION['order_success'] = true;
        $_SESSION['order_id'] = $order_id;
        header("Location: order_confirmation.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        
        // Display error message
        $_SESSION['checkout_error'] = $e->getMessage();
        header("Location: cart.php");
        exit();
    }
}

// Get cart items to display on checkout page
$cart_items_query = "SELECT ci.*, p.name, p.image_url, p.price, p.stock_quantity
                    FROM cart_items ci
                    JOIN product_table p ON ci.product_id = p.product_id
                    JOIN cart_table ct ON ci.cart_id = ct.cart_id
                    WHERE ci.signupid = ? AND ct.status = 'active' 
                    AND ci.status = 'active'";

$stmt = $conn->prepare($cart_items_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate cart totals
$total_items = 0;
$subtotal = 0;

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

// Fetch user details (username, email, phone) from the database
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

// Update the cart items query to get only active cart items from the current cart
$cart_items_query = "SELECT ci.*, p.name, p.image_url, p.price, c.name AS category_name, s.subcategory_name
                    FROM cart_items ci
                    JOIN product_table p ON ci.product_id = p.product_id
                    JOIN categories_table c ON ci.category_id = c.category_id
                    JOIN subcategories s ON ci.subcategory_id = s.id
                    WHERE ci.signupid = ? 
                    AND ci.status = 'active'
                    AND ci.order_id IS NULL
                    AND EXISTS (
                        SELECT 1 
                        FROM cart_table ct 
                        WHERE ct.cart_id = ci.cart_id 
                        AND ct.status = 'active'
                    )";

try {
    $stmt = $conn->prepare($cart_items_query);
    $stmt->bind_param("i", $signupid);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    // Calculate totals
    $subtotal = 0;
    $total_items = 0;

    if ($cart_items && $cart_items->num_rows > 0) {
        while($item = $cart_items->fetch_assoc()) {
            $total_items += $item['quantity'];
            $item_total = $item['quantity'] * $item['price'];
            $subtotal += $item_total;
        }
        
        // Reset the cart items result for display later
        $stmt->execute();
        $cart_items = $stmt->get_result();
    } else {
        // Redirect back to cart if no active items found
        header("Location: cart.php");
        exit();
    }

    // Calculate shipping, tax, and total
    $shipping = $subtotal >= 1000 ? 0 : 100;
    $tax = $subtotal * 0.05;
    $total = $subtotal + $shipping + $tax;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Razorpay API key
$razorpay_key_id = "rzp_test_Z4RWNiIGZc3YxK";

// Create a unique order ID
$order_id = 'ORD' . time() . $user_id;

// Function to store order details in the database
function storeOrderDetails($conn, $order_id, $signupid, $payment_id, $total_amount, $shipping_address) {
    $insert_order_query = "INSERT INTO orders_table (order_id, signupid, payment_id, total_amount, shipping_address) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_order_query);
    $stmt->bind_param("sisds", $order_id, $signupid, $payment_id, $total_amount, $shipping_address);
    return $stmt->execute();
}

// Function to store payment details in the database
function storePaymentDetails($conn, $order_id, $signupid, $payment_id, $amount, $payment_method, $payment_status) {
    $insert_payment_query = "INSERT INTO payment_table (order_id, signupid, payment_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_payment_query);
    $stmt->bind_param("sisdss", $order_id, $signupid, $payment_id, $amount, $payment_method, $payment_status);
    return $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }
        
        .logo:hover {
            color: #0077cc;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #2c3e50;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .icon-btn:hover {
            color: #0077cc;
            background-color: rgba(0,119,204,0.1);
            transform: translateY(-2px);
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .checkout-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .checkout-flex {
            display: flex;
            gap: 30px;
        }
        
        .checkout-form {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .checkout-summary {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #0077cc;
            box-shadow: 0 0 0 2px rgba(0,119,204,0.2);
        }
        
        .section-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #555;
        }
        
        .summary-value {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .item-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .checkout-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .item-price {
            color: #777;
            font-size: 14px;
        }
        
        .item-quantity {
            font-weight: 600;
            color: #555;
            margin-left: 15px;
        }
        
        .payment-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .payment-btn:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }
        
        @media (max-width: 768px) {
            .checkout-flex {
                flex-direction: column;
            }
            
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-item-image {
            width: 80px;
            height: 80px;
            margin-right: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .order-item-price {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .order-item-quantity {
            color: #666;
            font-size: 14px;
        }

        .order-summary-container {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        .order-items-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-item-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-details h4 {
            font-size: 14px;
            margin: 0 0 5px 0;
            color: #333;
        }

        .item-meta {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        .item-price {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .price-breakdown {
            padding: 20px;
            background: #f8f9fa;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #666;
        }

        .price-row.total {
            border-top: 2px solid #eee;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .payment-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,204,0.2);
        }

        .payment-btn i {
            font-size: 18px;
        }

        /* Scrollbar styling */
        .order-items-list::-webkit-scrollbar {
            width: 6px;
        }

        .order-items-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .order-items-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .order-items-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="nav-links">
            <a href="index.php" style="text-decoration: none;">
                <button class="icon-btn" title="Home">
                    <i class="fas fa-home"></i>
                </button>
            </a>
            <a href="product_view.php" style="text-decoration: none;">
                <button class="icon-btn" title="Products">
                    <i class="fas fa-shopping-bag"></i>
                </button>
            </a>
            <a href="cart.php" style="text-decoration: none;">
                <button class="icon-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </a>
            <a href="profile.php" style="text-decoration: none;">
                <div class="icon-btn" title="Profile">
                    <i class="fas fa-user"></i>
                </div>
            </a>
        </div>
    </div>
    
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
        
        <div class="checkout-flex">
            <div class="checkout-form">
                <h2 class="section-title">Shipping Information</h2>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="fullname" value="<?php echo htmlspecialchars($user_details['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-input" id="phone" value="<?php echo htmlspecialchars($user_details['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-input" id="address" value="<?php echo htmlspecialchars($user_details['address'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-input" id="city" value="<?php echo htmlspecialchars($user_details['city'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Postal Code</label>
                    <input type="text" class="form-input" id="postal_code" required>
                </div>
            </div>
            
            <div class="checkout-summary">
                <h2 class="section-title">Order Summary</h2>
                
                <div class="order-summary-container">
                    <!-- Compact Order Items List -->
                    <div class="order-items-list">
                        <?php 
                        if ($cart_items && $cart_items->num_rows > 0):
                            while($item = $cart_items->fetch_assoc()): 
                                $item_total = $item['quantity'] * $item['price'];
                        ?>
                            <div class="order-item-row">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="item-meta">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="item-price">
                                    ₹<?php echo number_format($item_total, 2); ?>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Shipping</span>
                            <span><?php echo $shipping > 0 ? '₹' . number_format($shipping, 2) : 'FREE'; ?></span>
                        </div>
                        <div class="price-row">
                            <span>Tax (5%)</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="price-row total">
                            <span>Total</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <button id="razorpay-button" class="payment-btn">
                        <i class="fas fa-lock"></i> Pay Securely Now
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('razorpay-button').addEventListener('click', function() {
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('address').value;
            const city = document.getElementById('city').value;
            const postal_code = document.getElementById('postal_code').value;
            
            // Validate form fields
            if (!fullname || !email || !phone || !address || !city || !postal_code) {
                alert('Please fill all required fields');
                return;
            }
            
            // Razorpay options
            const options = {
                key: "<?php echo $razorpay_key_id; ?>",
                amount: <?php echo $total * 100; ?>, // Amount in paise
                currency: "INR",
                name: "BabyCubs",
                description: "Purchase from BabyCubs",
                image: "https://your-website.com/logo.png", // Replace with your logo URL
                order_id: "", // Generate this on your server
                handler: function(response) {
                    // On successful payment
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'process_payment.php';
                    
                    // Add payment response data
                    const paymentIdInput = document.createElement('input');
                    paymentIdInput.type = 'hidden';
                    paymentIdInput.name = 'razorpay_payment_id';
                    paymentIdInput.value = response.razorpay_payment_id;
                    form.appendChild(paymentIdInput);
                    
                    // Add order data
                    const orderIdInput = document.createElement('input');
                    orderIdInput.type = 'hidden';
                    orderIdInput.name = 'order_id';
                    orderIdInput.value = '<?php echo $order_id; ?>';
                    form.appendChild(orderIdInput);
                    
                    // Add amount data
                    const amountInput = document.createElement('input');
                    amountInput.type = 'hidden';
                    amountInput.name = 'amount';
                    amountInput.value = '<?php echo $total; ?>';
                    form.appendChild(amountInput);
                    
                    // Add shipping info
                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'fullname';
                    nameInput.value = fullname;
                    form.appendChild(nameInput);
                    
                    const emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'email';
                    emailInput.value = email;
                    form.appendChild(emailInput);
                    
                    const phoneInput = document.createElement('input');
                    phoneInput.type = 'hidden';
                    phoneInput.name = 'phone';
                    phoneInput.value = phone;
                    form.appendChild(phoneInput);
                    
                    const addressInput = document.createElement('input');
                    addressInput.type = 'hidden';
                    addressInput.name = 'address';
                    addressInput.value = address;
                    form.appendChild(addressInput);
                    
                    const cityInput = document.createElement('input');
                    cityInput.type = 'hidden';
                    cityInput.name = 'city';
                    cityInput.value = city;
                    form.appendChild(cityInput);
                    
                    const postalInput = document.createElement('input');
                    postalInput.type = 'hidden';
                    postalInput.name = 'postal_code';
                    postalInput.value = postal_code;
                    form.appendChild(postalInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                },
                prefill: {
                    name: fullname,
                    email: email,
                    contact: phone
                },
                notes: {
                    address: address + ", " + city + " - " + postal_code
                },
                theme: {
                    color: "#0077cc"
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        });
    </script>
</body>
</html>