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
            padding: 15px 30px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0077cc;
        }
        
        .search-container {
            display: flex;
            flex-grow: 1;
            justify-content: center;
            margin: 0 20px;
        }
        
        .search-container input {
            width: 300px;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        
        .search-btn {
            padding: 8px 20px;
            background-color: #0077cc;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .category-nav {
            display: flex;
            justify-content: center;
            background-color: #f8f9fa;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }
        
        .category-nav button {
            padding: 5px 15px;
            margin: 0 5px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
        }
        
        .category-nav button:hover {
            background-color: #f1f1f1;
        }
        
        .product-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
            gap: 20px;
        }
        
        .product-card {
            width: 250px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
        }
        
        .product-info {
            padding: 15px;
            text-align: center;
        }
        
        .product-title {
            font-weight: bold;
            margin-bottom: 8px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-price {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .discount {
            color: #27ae60;
            font-size: 14px;
            margin-left: 5px;
        }
        
        .add-to-cart {
            background-color: #0077cc;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s;
        }
        
        .add-to-cart:hover {
            background-color: #005fa3;
        }
        
        .user-icon {
            width: 35px;
            height: 35px;
            background-color: #6c5ce7;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
        }
        
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #444;
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 800px;
            margin: 40px auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <?php else: ?>
                            <img src="images/placeholder.jpg" alt="No Image Available">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-title"><?php echo htmlspecialchars($row['name']); ?></div>
                        <div class="product-price">
                            ₹<?php echo number_format($row['price'], 2); ?>
                            <span class="discount">(<?php echo $discount_percentage; ?>% off)</span>
                        </div>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="add-to-cart">Add to Cart</button>
                        </form>
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