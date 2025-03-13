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
    // If we can't find the signupid, check if it's directly in the session
    $signupid = isset($_SESSION['signupid']) ? $_SESSION['signupid'] : null;
    
    // If still not found, try using user_id as signupid (this depends on your database structure)
    if ($signupid === null) {
        $signupid = $user_id;
    }
}

// Make sure we have a signupid before proceeding
if ($signupid === null) {
    // Display error and link to login
    echo "Session error: User identification not found. Please <a href='login.php'>login again</a>.";
    exit();
}

// Process add to cart form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity <= 0) {
        $quantity = 1; // Ensure minimum quantity is 1
    }
    
    // Get product information
    $product_query = "SELECT p.*, c.category_id, s.id as subcategory_id, p.price 
                     FROM product_table p
                     JOIN categories_table c ON p.category_id = c.category_id
                     JOIN subcategories s ON p.subcategory_id = s.id
                     WHERE p.product_id = ?";
    
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();
    
    if ($product_result && $product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        // Check if user already has an active cart
        $cart_check_query = "SELECT cart_id FROM cart_table 
                            WHERE signupid = ? AND status = 'active' 
                            LIMIT 1";
        
        $stmt = $conn->prepare($cart_check_query);
        $stmt->bind_param("i", $signupid);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        // If cart doesn't exist, create one
        if ($cart_result->num_rows === 0) {
            $create_cart_query = "INSERT INTO cart_table (signupid) VALUES (?)";
            $stmt = $conn->prepare($create_cart_query);
            $stmt->bind_param("i", $signupid);
            $stmt->execute();
            $cart_id = $conn->insert_id;
        } else {
            $cart_row = $cart_result->fetch_assoc();
            $cart_id = $cart_row['cart_id'];
        }
        
        // Check if product already exists in cart
        $check_item_query = "SELECT cart_item_id, quantity FROM cart_items 
                            WHERE cart_id = ? AND product_id = ? 
                            LIMIT 1";
        
        $stmt = $conn->prepare($check_item_query);
        $stmt->bind_param("ii", $cart_id, $product_id);
        $stmt->execute();
        $item_result = $stmt->get_result();
        
        if ($item_result->num_rows > 0) {
            // Update quantity if product already in cart
            $item_row = $item_result->fetch_assoc();
            $new_quantity = $item_row['quantity'] + $quantity;
            
            $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $new_quantity, $item_row['cart_item_id']);
            $stmt->execute();
            
            $message = "Cart updated successfully!";
        } else {
            // Add new product to cart
            $add_query = "INSERT INTO cart_items 
                         (cart_id, signupid, product_id, category_id, subcategory_id, quantity, price) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($add_query);
            $stmt->bind_param("iiiiids", $cart_id, $signupid, $product_id, 
                             $product['category_id'], $product['subcategory_id'], 
                             $quantity, $product['price']);
            
            if ($stmt->execute()) {
                $message = "Product added to cart successfully!";
            } else {
                $error = "Error adding product to cart: " . $stmt->error;
            }
        }
    } else {
        $error = "Product not found!";
    }
}

// Get cart items
$cart_items_query = "SELECT ci.*, p.name, p.image_url, p.price, c.name AS category_name, s.subcategory_name
                    FROM cart_items ci
                    JOIN product_table p ON ci.product_id = p.product_id
                    JOIN categories_table c ON ci.category_id = c.category_id
                    JOIN subcategories s ON ci.subcategory_id = s.id
                    JOIN cart_table ct ON ci.cart_id = ct.cart_id
                    WHERE ci.signupid = ? AND ct.status = 'active' 
                    AND (ci.status = 'active' OR ci.status IS NULL)";

$stmt = $conn->prepare($cart_items_query);
$stmt->bind_param("i", $signupid);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate cart totals
$total_items = 0;
$subtotal = 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Shopping Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .cart-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .cart-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .cart-error {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .cart-empty {
            text-align: center;
            padding: 60px 40px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .cart-empty h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .cart-empty p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .continue-shopping {
            background: linear-gradient(135deg, #0077cc, #005fa3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .continue-shopping:hover {
            background: linear-gradient(135deg, #005fa3, #004c82);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,204,0.3);
        }
        
        .cart-items {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
            background-color: #f8f9fa;
            padding: 10px;
        }
        
        .item-details {
            padding: 0 20px;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .item-category {
            color: #777;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 18px;
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .quantity-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #2c3e50;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background-color: #0077cc;
            color: white;
        }
        
        .quantity {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .remove-btn {
            background-color: #ffebee;
            color: #c62828;
            border: none;
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .remove-btn:hover {
            background-color: #c62828;
            color: white;
        }
        
        .cart-summary {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 25px;
        }
        
        .summary-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #555;
        }
        
        .summary-value {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .checkout-btn {
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
        
        .checkout-btn:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }
        
        .cart-flex {
            display: flex;
            gap: 30px;
        }
        
        .cart-items-container {
            flex: 2;
        }
        
        .cart-summary-container {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .cart-flex {
                flex-direction: column;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }
            
            .item-image {
                width: 80px;
                height: 80px;
            }
            
            .item-actions {
                grid-column: span 2;
                justify-content: space-between;
                margin-top: 15px;
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
        
        /* Update quantity form styling */
        .update-quantity-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Remove button styling */
        .remove-form {
            display: inline-block;
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
            <a href="profile.php" style="text-decoration: none;">
                <div class="icon-btn" title="Profile">
                    <i class="fas fa-user"></i>
                </div>
            </a>
            <a href="logout.php" style="text-decoration: none;">
                <button class="icon-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </a>
        </div>
    </div>
    
    <div class="cart-container">
        <h1 class="cart-title">Your Shopping Cart</h1>
        
        <?php if(isset($message)): ?>
            <div class="cart-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="cart-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($cart_items && $cart_items->num_rows > 0): ?>
            <div class="cart-flex">
                <div class="cart-items-container">
                    <div class="cart-items">
                        <?php 
                        while($item = $cart_items->fetch_assoc()): 
                            $total_items += $item['quantity'];
                            $item_total = $item['quantity'] * $item['price'];
                            $subtotal += $item_total;
                        ?>
                            <div class="cart-item">
                                <img src="<?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-category">
                                        <?php echo htmlspecialchars($item['category_name']); ?> | 
                                        <?php echo htmlspecialchars($item['subcategory_name']); ?>
                                    </div>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                
                                <div class="item-actions">
                                    <form action="update_cart.php" method="POST" class="update-quantity-form">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <div class="quantity-control">
                                            <button type="submit" name="action" value="decrease" class="quantity-btn">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="quantity"><?php echo $item['quantity']; ?></span>
                                            <button type="submit" name="action" value="increase" class="quantity-btn">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <form action="update_cart.php" method="POST" class="remove-form">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <button type="submit" name="action" value="remove" class="remove-btn">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="cart-summary-container">
                    <div class="cart-summary">
                        <h2 class="summary-title">Order Summary</h2>
                        
                        <div class="summary-row">
                            <span class="summary-label">Items (<?php echo $total_items; ?>)</span>
                            <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value">
                                <?php 
                                $shipping = $subtotal >= 1000 ? 0 : 100;
                                echo $shipping > 0 ? '₹' . number_format($shipping, 2) : 'FREE';
                                ?>
                            </span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tax (5%)</span>
                            <span class="summary-value">
                                <?php 
                                $tax = $subtotal * 0.05;
                                echo '₹' . number_format($tax, 2);
                                ?>
                            </span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label" style="font-size: 18px; color: #2c3e50;">Total</span>
                            <span class="summary-value" style="font-size: 18px; color: #e74c3c;">
                                <?php 
                                $total = $subtotal + $shipping + $tax;
                                echo '₹' . number_format($total, 2);
                                ?>
                            </span>
                        </div>
                        
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-empty">
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="product_view.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>