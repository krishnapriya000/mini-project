<?php
session_start();
require 'config.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Fetch all products from the database
$product_query = "
    SELECT p.*, c.name AS category_name 
    FROM product_table p
    LEFT JOIN categories_table c ON p.category_id = c.category_id
    WHERE p.status = 1
    ORDER BY p.created_at DESC
";

try {
    $result = $conn->query($product_query);
} catch (Exception $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BabyCubs - Baby Products</title>
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
            text-align: center; /* Center align header content */
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
            margin: 0 auto; /* Center the logo */
        }
        
        .logo:hover {
            color: #0077cc;
        }
        
        .search-container {
            display: flex;
            flex-grow: 1;
            justify-content: center;
            margin: 0 auto; /* Center the search bar */
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
            text-align: center; /* Center the placeholder text */
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
            justify-content: center; /* Center the navigation links */
            margin: 0 auto; /* Center align nav links */
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
        
        .category-nav {
            display: flex;
            justify-content: center; /* Center the category buttons */
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            text-align: center; /* Center align category text */
        }
        
        .category-nav::-webkit-scrollbar {
            height: 4px;
        }
        
        .category-nav::-webkit-scrollbar-thumb {
            background: #0077cc;
            border-radius: 4px;
        }
        
        .category-nav button {
            padding: 8px 20px;
            margin: 0 8px;
            background-color: white;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .category-nav button:hover {
            background-color: #0077cc;
            border-color: #0077cc;
            color: white;
            transform: translateY(-2px);
        }
        
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 40px;
            background-color: #f8f9fa;
            max-width: 1200px; /* Constrain max width */
            margin: 0 auto; /* Center the product container */
        }
        
        .product-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            text-align: center; /* Center all content in cards */
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .product-image {
            position: relative;  /* Added for absolute positioning of like button */
            height: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            transition: transform 0.3s ease;
            margin: 0 auto; /* Center the image */
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 20px;
            text-align: center; /* Center the product info */
            background: white;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center; /* Center items horizontally */
        }
        
        .product-title {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.4;
            height: 45px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-align: center; /* Center the title text */
        }
        
        .product-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 15px;
            text-align: center; /* Center the price */
        }
        
        .discount {
            color: #27ae60;
            font-size: 15px;
            font-weight: 600;
            margin-left: 8px;
            background-color: #e8f5e9;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block; /* Keep the discount inline */
        }
        
        .like-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: 2px solid #ff69b4;
            color: #ff69b4;
            padding: 8px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 2;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .like-btn:hover {
            transform: scale(1.1);
        }
        
        .like-btn.active {
            background: #ff69b4;
            color: white;
        }
        
        .product-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        
        .cart-buy-buttons {
            display: flex;
            gap: 8px;
            width: 100%;
        }
        
        .action-button {
            flex: 1;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .add-to-cart {
            background: linear-gradient(135deg, #0077cc, #005fa3);
            color: white;
        }
        
        .buy-now {
            background: linear-gradient(135deg, #00b894, #00a382);
            color: white;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-products {
            text-align: center;
            padding: 60px 40px;
            background-color: white;
            border-radius: 15px;
            width: 100%;
            max-width: 800px;
            margin: 40px auto; /* Center the no products message */
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .no-products h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center; /* Center the heading */
        }
        
        .no-products p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            text-align: center; /* Center the paragraph */
        }
        
        /* Update header layout for better centering */
        .header {
            flex-direction: column; /* Stack elements vertically */
            gap: 15px;
        }
        
        @media (min-width: 768px) {
            .header {
                flex-direction: row; /* Return to row on larger screens */
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }
            
            .search-container {
                margin: 15px auto; /* Center with margin */
                width: 90%;
            }
            
            .nav-links {
                gap: 15px;
                justify-content: center; /* Keep centered on mobile */
                width: 100%;
            }
            
            .product-container {
                padding: 20px;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .product-image {
                height: 200px;
            }
            
            .product-title {
                font-size: 14px;
                height: 40px;
            }
        }
        
        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
                text-align: center; /* Ensure centered on smallest screens */
                width: 100%;
            }
            
            .search-container input {
                padding: 10px 15px;
            }
            
            .search-btn {
                padding: 10px 20px;
            }
            
            .icon-btn {
                font-size: 20px;
            }
            
            .user-icon {
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" style="text-decoration: none; margin: 0 auto;">
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
    
    <div class="category-nav">
        <?php
        // Fetch categories for the navigation
        $cat_query = "SELECT * FROM categories_table ORDER BY name";
        $cat_result = $conn->query($cat_query);
        
        if ($cat_result && $cat_result->num_rows > 0) {
            while ($cat_row = $cat_result->fetch_assoc()) {
                echo '<a href="category.php?id='.$cat_row['category_id'].'" style="text-decoration: none;">';
                echo '<button>'.htmlspecialchars($cat_row['name']).'</button>';
                echo '</a>';
            }
        }
        ?>
    </div>
    
    <div class="product-container">
        <?php 
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                // Calculate discount percentage if needed
                $discount_percentage = 0;
                $original_price = $row['price'];
                
                // You can implement your own discount logic here
                // For now, we'll use a random discount between 10-60%
                $discount_percentage = rand(10, 60);
                $discounted_price = $original_price; // Set your actual discounted price calculation
                ?>
                
                <div class="product-card">
                    <div class="product-image">
                        <button class="like-btn" onclick="toggleLike(this, <?php echo $row['product_id']; ?>)">
                            <i class="fas fa-heart"></i>
                        </button>
                        <a href="product_details.php?id=<?php echo $row['product_id']; ?>">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php else: ?>
                                <img src="images/placeholder.jpg" alt="No Image Available">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="product-info">
                        <div class="product-title">
                            <a href="product_details.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: #2c3e50;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </a>
                        </div>
                        <div class="product-price">
                            ₹<?php echo number_format($row['price'], 2); ?>
                            <span class="discount">(<?php echo $discount_percentage; ?>% off)</span>
                        </div>
                        <div class="product-buttons">
                            <div class="cart-buy-buttons">
                                <form action="cart.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="action-button add-to-cart">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </form>
                                <form action="buy_now.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="action-button buy-now">
                                        <i class="fas fa-bolt"></i> Buy Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php 
            }
        } else { 
        ?>
            <div class="no-products">
                <h3>No products found</h3>
                <p>We couldn't find any products in our database. Please check back later!</p>
            </div>
        <?php 
        } 
        ?>
    </div>
    
    <?php
    // Close the connection
    $conn->close();
    ?>
</body>
</html>