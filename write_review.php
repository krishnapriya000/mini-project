<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';
$product_details = [];
$products = [];

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: view_order_details.php");
    exit();
}

$order_id = $_GET['order_id'];

// Verify this order belongs to the current user
$check_order_query = "SELECT * FROM orders_table WHERE order_id = ? AND signupid = ?";
$stmt = $conn->prepare($check_order_query);
$stmt->bind_param("si", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    header("Location: view_order_details.php");
    exit();
}

// Get the order details
$order_data = $order_result->fetch_assoc();

// Get product details from the cart_items table using the order_id
$products_query = "SELECT c.product_id, p.name AS product_name, p.image_url AS product_image 
                   FROM cart_items c 
                   JOIN product_table p ON c.product_id = p.product_id 
                   WHERE c.order_id = ?";
$stmt = $conn->prepare($products_query);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("s", $order_id);
if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}
$products_result = $stmt->get_result();

// If no products found in cart_items, fetch a single product as fallback
if ($products_result->num_rows == 0) {
    $fallback_query = "SELECT product_id, name AS product_name, image_url AS  
                      FROM product_table 
                      ORDER BY RAND() LIMIT 1";
    $fallback_result = $conn->query($fallback_query);
    if ($fallback_result && $fallback_result->num_rows > 0) {
        $products_result = $fallback_result;
    }
}

// Store all products from this order
while ($product = $products_result->fetch_assoc()) {
    $products[] = $product;
}

// Get the selected product_id from URL or select the first product
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : ($products[0]['product_id'] ?? null);

// If we have a product_id, get its details
if ($product_id) {
    foreach ($products as $product) {
        if ($product['product_id'] == $product_id) {
            $product_details = $product;
            break;
        }
    }
}

// If no product details, redirect back
if (empty($product_details)) {
    header("Location: view_order_details.php");
    exit();
}

// Check if user has already reviewed this product
$check_review_query = "SELECT * FROM review_table WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($check_review_query);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$existing_review = $stmt->get_result();
$review_exists = ($existing_review->num_rows > 0);
$review_data = $review_exists ? $existing_review->fetch_assoc() : null;

// Process review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        $error_message = "Please select a rating between 1 and 5.";
    } elseif (empty($comment)) {
        $error_message = "Please enter a review comment.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, check if user exists in user_table
            $check_user = "SELECT user_id FROM user_table WHERE signupid = ?";
            $stmt = $conn->prepare($check_user);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();
            
            // If user doesn't exist in user_table, create them
            if ($user_result->num_rows == 0) {
                // Get user details from signup table
                $get_user = "SELECT * FROM signup WHERE signupid = ?";
                $stmt = $conn->prepare($get_user);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $signup_user = $stmt->get_result()->fetch_assoc();
                
                // Insert into user_table
                $insert_user = "INSERT INTO user_table (signupid, username, reg_type, status) 
                               VALUES (?, ?, 'user', 'active')";
                $stmt = $conn->prepare($insert_user);
                $stmt->bind_param("is", $user_id, $signup_user['username']);
                $stmt->execute();
                
                // Get the newly created user_id
                $new_user_id = $conn->insert_id;
            } else {
                $user_data = $user_result->fetch_assoc();
                $new_user_id = $user_data['user_id'];
            }
            
            // Now proceed with review
            if ($review_exists) {
                $update_review = "UPDATE review_table 
                                SET rating = ?, comment = ? 
                                WHERE user_id = ? AND product_id = ?";
                $stmt = $conn->prepare($update_review);
                $stmt->bind_param("isii", $rating, $comment, $new_user_id, $product_id);
                $stmt->execute();
                $success_message = "Your review has been updated successfully!";
            } else {
                $insert_review = "INSERT INTO review_table (user_id, product_id, rating, comment) 
                                VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_review);
                $stmt->bind_param("iiis", $new_user_id, $product_id, $rating, $comment);
                $stmt->execute();
                $success_message = "Your review has been submitted successfully!";
            }
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error processing your review: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Write Product Review</title>
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
            max-width: 800px;
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
        
        .review-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
            border-radius: 10px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-details h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .product-details p {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .product-select {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .review-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .star-rating {
            display: flex;
            margin-bottom: 15px;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
            margin-right: 5px;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffb70d;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        textarea:focus {
            border-color: #3a77bf;
            outline: none;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .back-btn {
            background-color: #fff;
            color: #3a77bf;
            border: 1px solid #3a77bf;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .back-btn:hover {
            background-color: #f5f9ff;
        }
        
        .submit-btn {
            background-color: #3a77bf;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #2c5c94;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #4caf50;
            border: 1px solid #c8e6c9;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #f44336;
            border: 1px solid #ffcdd2;
        }
        
        @media (max-width: 600px) {
            .product-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: 15px;
                margin-right: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .back-btn, .submit-btn {
                width: 100%;
                margin-bottom: 10px;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Back Button -->
        <a href="view_order_details.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        
        <!-- Page Title -->
        <div class="page-title">
            <div class="page-title-icon">
                <i class="fas fa-star"></i>
            </div>
            <h1><?php echo $review_exists ? 'Edit Your Review' : 'Write a Review'; ?></h1>
        </div>
        
        <!-- Review Container -->
        <div class="review-container">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Product Information -->
            <div class="product-info">
                <div class="product-image">
                    <?php if (!empty($product_details['product_image'])): ?>
                        <img src="<?php echo htmlspecialchars($product_details['product_image']); ?>" alt="<?php echo htmlspecialchars($product_details['product_name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-box fa-2x" style="color: #bbb;"></i>
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <h2><?php echo htmlspecialchars($product_details['product_name'] ?? 'Product'); ?></h2>
                    <p>Order #<?php echo htmlspecialchars($order_id); ?></p>
                    
                    <?php if (count($products) > 1): ?>
                        <form action="" method="GET">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                            <select name="product_id" class="product-select" onchange="this.form.submit()">
                                <option value="">Select a product to review</option>
                                <?php foreach ($products as $prod): ?>
                                    <option value="<?php echo $prod['product_id']; ?>" <?php echo ($prod['product_id'] == $product_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['product_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Review Form -->
            <form class="review-form" method="POST" action="">
                <div class="form-group">
                    <label for="rating">Your Rating:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($review_exists && $review_data['rating'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comment">Your Review:</label>
                    <textarea id="comment" name="comment" placeholder="Share your thoughts about this product..."><?php echo $review_exists ? htmlspecialchars($review_data['comment']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="view_order_details.php" class="back-btn">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="submit_review" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> <?php echo $review_exists ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>