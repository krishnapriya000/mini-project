<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Get the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// If search query is empty, redirect back to index
if (empty($search_query)) {
    header('Location: index.php');
    exit();
}

// Pagination
$results_per_page = 12; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $results_per_page; // Offset for SQL query

// Prepare the search query
$query = "SELECT * FROM product_table 
          WHERE name LIKE ? 
          OR description LIKE ?
          OR brand LIKE ?
          LIMIT ? OFFSET ?";

try {
    $stmt = $conn->prepare($query);
    $search_term = "%{$search_query}%";
    $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $results_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get total number of results for pagination
    $total_results_query = "SELECT COUNT(*) AS total FROM product_table 
                            WHERE name LIKE ? 
                            OR description LIKE ?
                            OR brand LIKE ?";
    $stmt_total = $conn->prepare($total_results_query);
    $stmt_total->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt_total->execute();
    $total_results = $stmt_total->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_results / $results_per_page);
} catch (Exception $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - BabyCubs</title>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" as="style">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }

        body {
            background: linear-gradient(to bottom, #f8f9fa, #e6ebfa);
            min-height: 100vh;
        }

        /* Enhanced Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        /* Search Container Improvements */
        .search-container {
            flex-grow: 1;
            max-width: 600px;
            margin: 0 40px;
        }

        .search-container form {
            display: flex;
            position: relative;
            width: 100%;
        }

        .search-container input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-container input:focus {
            border-color: #0077cc;
            box-shadow: 0 0 15px rgba(0,119,204,0.1);
        }

        .search-btn {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            padding: 0 25px;
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(45deg, #005fa3, #0066cc);
        }

        /* Navigation Links Enhancement */
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
            padding: 10px;
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
            background: linear-gradient(45deg, #6c5ce7, #8e44ad);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108,92,231,0.3);
        }

        .user-icon:hover {
            transform: scale(1.1);
        }

        /* Category Navigation Enhancement */
        .category-nav {
            display: flex;
            justify-content: center;
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 30px;
        }

        .category-nav::-webkit-scrollbar {
            height: 3px;
        }

        .category-nav::-webkit-scrollbar-thumb {
            background: #0077cc;
            border-radius: 10px;
        }

        .category-nav button {
            padding: 10px 25px;
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
            border-color: #0077cc;
            color: #0077cc;
            transform: translateY(-2px);
        }

        .category-nav button.active {
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,119,204,0.3);
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 20px auto 20px;
            padding: 20px;
        }

        .search-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .search-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Updated Product Card Styles */
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 40px;
            margin: 0 auto;
            max-width: 1400px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .product-image {
            position: relative;
            height: 250px;
            background: #f8f9fa;
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
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
            background: white;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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
            text-align: center;
        }

        .product-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .discount {
            color: #27ae60;
            font-size: 14px;
            font-weight: 600;
            background-color: #e8f5e9;
            padding: 4px 10px;
            border-radius: 12px;
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

        .action-button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .add-to-cart {
            background: linear-gradient(45deg, #0077cc, #1a8cff);
            color: white;
        }

        .buy-now {
            background: linear-gradient(45deg, #00b894, #00a382);
            color: white;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* No Products Message Enhancement */
        .no-results {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 800px;
            margin: 40px auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .no-results h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .no-results p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            padding: 10px 20px;
            margin: 0 5px;
            background: linear-gradient(45deg, #007bff, #00bfff);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .pagination a:hover {
            background: linear-gradient(45deg, #0056b3, #0098ff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .pagination a.active {
            background: linear-gradient(45deg, #0056b3, #0098ff);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-results {
                padding: 20px;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .search-container {
                margin: 0 15px;
            }

            .search-header h1 {
                font-size: 24px;
            }

            .product-card {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
            }

            .nav-links {
                gap: 15px;
            }

            .user-icon {
                width: 35px;
                height: 35px;
            }

            .category-nav button {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="header">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">BabyCubs</div>
        </a>
        
        <div class="search-container">
            <form action="search.php" method="GET">
                <input type="text" name="query" placeholder="Search products" 
                       value="<?php echo htmlspecialchars($search_query); ?>" required>
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

    <!-- Category Navigation -->
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

    <!-- Main Content -->
    <div class="container">
        <div class="search-header">
            <h1>Search Results</h1>
            <p>Showing results for "<?php echo htmlspecialchars($search_query); ?>"</p>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="search-results">
                <?php while ($product = $result->fetch_assoc()): 
                    // Calculate discount percentage (you can replace this with your actual discount logic)
                    $discount_percentage = rand(10, 60);
                ?>
                    <div class="product-card">
                        <div class="product-image">
                        <button class="like-btn <?php echo $isLiked ? 'active' : ''; ?>" 
        onclick="toggleLike(this, <?php echo $product['product_id']; ?>)">
    <i class="fas fa-heart"></i>
</button>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </a>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">
                                ₹<?php echo number_format($product['price'], 2); ?>
                                <span class="discount">(<?php echo $discount_percentage; ?>% off)</span>
                            </div>
                            <div class="product-buttons">
                                <form action="cart.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="action-button add-to-cart">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </form>
                                <form action="buy_now.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="action-button buy-now">
                                        <i class="fas fa-bolt"></i> Buy Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="search.php?query=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="search.php?query=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="search.php?query=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>No products found matching your search</h3>
                <p>We couldn't find any products matching "<?php echo htmlspecialchars($search_query); ?>". Please try a different search term!</p>
                <a href="index.php" class="action-button add-to-cart" style="display: inline-block; width: auto; padding: 10px 30px; margin-top: 20px;">
                    Return to Home
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleLike(button, productId) {
    button.classList.toggle('active');
    
    // Send AJAX request to update favorites
    fetch('update_favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=' + (button.classList.contains('active') ? 'add' : 'remove')
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            button.classList.toggle('active'); // Revert if error
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        button.classList.toggle('active'); // Revert if error
    });
}
    </script>
</body>
</html>