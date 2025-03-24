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
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(255, 234, 249);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 2.5rem;
            text-decoration: none;
            color: blue;
        }

        .search-cart {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px 0 0 20px;
            width: 200px;
            height: 20px;
        }

        .search-button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 20px 20px 0;
            padding: 8px 15px;
            cursor: pointer;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-button:hover {
            background-color: #0056b3;
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 100px auto 20px;
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

        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .product-details {
            padding: 20px;
        }

        .product-name {
            font-size: 1.1em;
            margin: 0 0 10px 0;
            color: #333;
        }

        .product-price {
            font-weight: bold;
            color: #007bff;
            font-size: 1.2em;
            margin: 10px 0;
        }

        .product-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-results p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .pagination a:hover {
            background-color: #0056b3;
        }

        .pagination a.active {
            background-color: #0056b3;
        }

        /* Profile Icon Styles */
        .profile-icon-container {
            position: relative;
            display: inline-block;
        }

        .profile-icon {
            font-size: 1.2rem;
            color: #333;
            background-color: #f1f1f1;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            background-color: #f1f1f1;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            right: 0;
            border-radius: 5px;
            overflow: hidden;
        }

        .profile-dropdown a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown a:hover {
            background-color: #ddd;
        }

        .search-form {
            display: flex;
            align-items: center;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 2rem;
            }

            .search-box {
                width: 150px;
            }

            .search-results {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.5rem;
            }

            .search-box {
                width: 120px;
            }

            .search-results {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">BabyCubs</a>
            <div class="search-cart">
                <form action="search.php" method="GET" class="search-form">
                    <input type="search" name="query" placeholder="Search products..." 
                           class="search-box" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="cart.php" style="text-decoration: none; color: #333; font-size: 1.2rem; margin-left: 10px;">
                    <i class="fas fa-shopping-cart"></i>
                </a>
                <div class="profile-icon-container">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="profile-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-dropdown">
                            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="my_orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    <?php else: ?>
                        <div class="profile-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-dropdown">
                            <a href="login.php">Login</a>
                            <a href="signup.php">Sign Up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="search-header">
            <h1>Search Results</h1>
            <p>Showing results for "<?php echo htmlspecialchars($search_query); ?>"</p>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="search-results">
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="seller/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image"
                             onerror="this.src='placeholder.jpg'">
                        <div class="product-details">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description">
                                <?php 
                                    $desc = htmlspecialchars($product['description']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 97) . '...' : $desc;
                                ?>
                            </p>
                            <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn">View Details</a>
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
                <p>No products found matching your search.</p>
                <a href="index.php" class="btn">Return to Home</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Profile dropdown functionality
        document.addEventListener("DOMContentLoaded", function() {
            const profileIcon = document.querySelector(".profile-icon");
            const profileDropdown = document.querySelector(".profile-dropdown");

            profileIcon.addEventListener("click", function(event) {
                event.stopPropagation();
                profileDropdown.style.display = profileDropdown.style.display === "block" ? "none" : "block";
            });

            document.addEventListener("click", function() {
                profileDropdown.style.display = "none";
            });

            profileDropdown.addEventListener("click", function(event) {
                event.stopPropagation();
            });
        });
    </script>
</body>
</html>