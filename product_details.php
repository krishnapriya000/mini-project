<?php
session_start();
require 'config.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: product_view.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$product_query = "
    SELECT p.*, c.name AS category_name, s.subcategory_name, b.brand_name, sl.seller_name 
    FROM product_table p
    LEFT JOIN categories_table c ON p.category_id = c.category_id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    LEFT JOIN brand_table b ON p.brand = b.brand_name
    LEFT JOIN seller sl ON p.seller_id = sl.seller_id
    WHERE p.product_id = ? AND p.status = 1
";

$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Product not found or not active
    header("Location: product_view.php");
    exit();
}

$product = $result->fetch_assoc();

// Get related products from the same category
$related_query = "
    SELECT p.*, c.name AS category_name 
    FROM product_table p
    LEFT JOIN categories_table c ON p.category_id = c.category_id
    WHERE p.category_id = ? AND p.product_id != ? AND p.status = 1
    ORDER BY RAND()
    LIMIT 4
";

$stmt = $conn->prepare($related_query);
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$related_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - BabyCubs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #e6ebfa;
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
        
        .search-container {
            display: flex;
            flex-grow: 1;
            justify-content: center;
            margin: 0 40px;
            max-width: 600px;
            position: relative;
        }
        
        .search-container input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-container form {
            width: 100%;
            display: flex;
            position: relative;
        }
        
        .search-btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            padding: 0 25px;
            background-color: #0077cc;
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background-color: #005fa3;
        }
        
        .search-container input[type="text"] {
            width: 100%;
            padding-right: 100px;
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
        
        .user-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6c5ce7, #8e44ad);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(108,92,231,0.3);
        }
        
        .user-icon:hover {
            transform: scale(1.1);
        }
        
        /* Product detail specific styles */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .product-detail {
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .product-gallery {
            flex: 1;
            padding: 30px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .main-image {
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .thumbnail-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .thumbnail:hover, .thumbnail.active {
            border-color: #0077cc;
            transform: scale(1.05);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info-container {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
        }
        
        .product-name {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .product-category {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .product-price-detail {
            font-size: 26px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .original-price {
            font-size: 18px;
            color: #7f8c8d;
            text-decoration: line-through;
            margin-left: 10px;
        }
        
        .discount-badge {
            background: #e8f5e9;
            color: #27ae60;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 15px;
        }
        
        .product-description {
            color: #34495e;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .product-meta {
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            margin-bottom: 10px;
        }
        
        .meta-label {
            width: 120px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .meta-value {
            color: #34495e;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #e0e0e0;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
            margin: 0 10px;
            font-size: 16px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .add-to-cart-btn {
            flex: 1;
            padding: 15px 25px;
            background: linear-gradient(135deg, #0077cc, #005fa3);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #005fa3, #004c82);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,119,204,0.3);
        }
        
        .wishlist-btn {
            width: 50px;
            height: 50px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin: 50px 0 30px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 60px;
            background: #0077cc;
        }
        
        .related-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .related-product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .related-product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .related-product-image {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .related-product-image img {
            max-width: 100%;
            max-height: 160px;
            object-fit: contain;
        }
        
        .related-product-info {
            padding: 15px;
        }
        
        .related-product-name {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }
        
        .related-product-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 18px;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .product-detail {
                flex-direction: column;
            }
            
            .product-gallery, .product-info-container {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }
            
            .search-container {
                margin: 0 15px;
            }
            
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-name {
                font-size: 24px;
            }
            
            .related-products {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .thumbnail {
                width: 60px;
                height: 60px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .wishlist-btn {
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="search-container">
            <form action="search.php" method="GET">
                <input type="text" name="query" placeholder="Search products">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
        
        <div class="nav-links">
            <a href="index.php" style="text-decoration: none;">
                <button class="icon-btn" title="Home">
                    <i class="fas fa-home"></i>
                </button>
            </a>
            <a href="profile.php" style="text-decoration: none;">
                <div class="user-icon" title="Profile">
                    <?php 
                    // Get first letter of username if logged in
                    if(isset($_SESSION['username'])) {
                        echo substr($_SESSION['username'], 0, 1);
                    } else {
                        echo "P"; 
                    }
                    ?>
                </div>
            </a>
            <a href="cart.php" style="text-decoration: none;">
                <button class="icon-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </a>
            <a href="logout.php" style="text-decoration: none;">
                <button class="icon-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="product-detail">
            <div class="product-gallery">
                <div class="main-image">
                    <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                </div>
                <div class="thumbnail-container">
                    <!-- Main product image as first thumbnail -->
                    <div class="thumbnail active" onclick="changeImage(this, '<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/placeholder.jpg'; ?>')">
                        <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    
                    <!-- You can add more thumbnails if you have multiple product images -->
                    <!-- For now, we'll add placeholder images as examples -->
                    <!-- In a real implementation, you would fetch these from a product_images table -->
                    <div class="thumbnail active" onclick="changeImage(this, '<?php echo !empty($product['image_url_2']) ? htmlspecialchars($product['image_url_2']) : 'images/placeholder.jpg'; ?>')">
                        <img src="<?php echo !empty($product['image_url_2']) ? htmlspecialchars($product['image_url_2']) : 'images/placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="thumbnail active" onclick="changeImage(this, '<?php echo !empty($product['image_url_3']) ? htmlspecialchars($product['image_url_3']) : 'images/placeholder.jpg'; ?>')">
                        <img src="<?php echo !empty($product['image_url_3']) ? htmlspecialchars($product['image_url_3']) : 'images/placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    
                </div>
            </div>
            <div class="product-info-container">
                <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-category">
                    <?php echo htmlspecialchars($product['category_name']); ?> &gt; 
                    <?php echo htmlspecialchars($product['subcategory_name']); ?>
                </div>
                
                <div class="product-price-detail">
                    ₹<?php echo number_format($product['price'], 2); ?>
                    <?php 
                    // Calculate discount percentage (you can replace this with your actual logic)
                    $discount_percentage = rand(10, 60);
                    $original_price = $product['price'] * (100 / (100 - $discount_percentage));
                    ?>
                    <span class="original-price">₹<?php echo number_format($original_price, 2); ?></span>
                    <span class="discount-badge"><?php echo $discount_percentage; ?>% off</span>
                </div>
                
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <div class="meta-label">Brand:</div>
                        <div class="meta-value"><?php echo !empty($product['brand']) ? htmlspecialchars($product['brand']) : 'N/A'; ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Size:</div>
                        <div class="meta-value"><?php echo htmlspecialchars($product['size']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Color:</div>
                        <div class="meta-value"><?php echo htmlspecialchars($product['colour']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Condition:</div>
                        <div class="meta-value"><?php echo htmlspecialchars($product['condition_type']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Stock:</div>
                        <div class="meta-value">
                            <?php 
                            if ($product['stock_quantity'] <= 0) {
                                echo '<span style="color: #e74c3c; font-weight: bold;"><i class="fas fa-times-circle"></i> Out of Stock</span>';
                            } elseif ($product['stock_quantity'] <= 5) {
                                echo '<span style="color: #e67e22; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Low Stock: ' . $product['stock_quantity'] . ' available</span>';
                            } else {
                                echo '<span style="color: #27ae60;"><i class="fas fa-check-circle"></i> In Stock (' . $product['stock_quantity'] . ' available)</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Seller:</div>
                        <div class="meta-value"><?php echo htmlspecialchars($product['seller_name']); ?></div>
                    </div>
                </div>
                
                <form action="cart.php" method="POST">
                    <div class="quantity-selector">
                        <div class="meta-label">Quantity:</div>
                        <div class="quantity-btn" onclick="decrementQuantity()">-</div>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="quantity-input">
                        <div class="quantity-btn" onclick="incrementQuantity(<?php echo $product['stock_quantity']; ?>)">+</div>
                    </div>
                    
                    <div class="action-buttons">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button type="submit" class="add-to-cart-btn" <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> 
                            <?php echo ($product['stock_quantity'] <= 0) ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                        <div class="wishlist-btn">
                            <i class="far fa-heart"></i>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Related Products Section -->
        <h2 class="section-title">Related Products</h2>
        <div class="related-products">
            <?php 
            if ($related_result && $related_result->num_rows > 0) {
                while ($related = $related_result->fetch_assoc()) { 
            ?>
                <a href="product_details.php?id=<?php echo $related['product_id']; ?>" style="text-decoration: none;">
                    <div class="related-product-card">
                        <div class="related-product-image">
                            <?php if (!empty($related['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php else: ?>
                                <img src="images/placeholder.jpg" alt="No Image Available">
                            <?php endif; ?>
                        </div>
                        <div class="related-product-info">
                            <div class="related-product-name"><?php echo htmlspecialchars($related['name']); ?></div>
                            <div class="related-product-price">₹<?php echo number_format($related['price'], 2); ?></div>
                        </div>
                    </div>
                </a>
            <?php 
                }
            } else {
                echo '<p>No related products found.</p>';
            }
            ?>
        </div>
    </div>
    
    <script>
        // Function to change the main product image
        function changeImage(thumbnail, imageUrl) {
            // Update main image
            document.getElementById('main-product-image').src = imageUrl;
            
            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(item => {
                item.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        // Functions to increment/decrement quantity
        function incrementQuantity(maxStock) {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        }
        
        function decrementQuantity() {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        }
    </script>
</body>
</html>