<?php
//session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only create connection if it doesn't already exist
if (!isset($conn) || !$conn) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "baby_db";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Fetch categories and subcategories
$query = "SELECT c.*, s.id as subcategory_id, s.subcategory_name 
          FROM categories_table c 
          LEFT JOIN subcategories s ON c.category_id = s.category_id 
          ORDER BY c.category_id, s.subcategory_name";
$result = $conn->query($query);

// Organize categories and subcategories
$categories = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($categories[$row['category_id']])) {
        $categories[$row['category_id']] = [
            'name' => $row['name'],
            'subcategories' => []
        ];
    }
    if ($row['subcategory_id']) {
        $categories[$row['category_id']]['subcategories'][] = [
            'id' => $row['subcategory_id'],
            'name' => $row['subcategory_name']
        ];
    }
}

// Fetch user information
$user_query = "SELECT username FROM user_table WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Don't close the connection here!
// The connection will remain open for use in other parts of your application
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .header {
            background-color: #f8f9fa;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left-section {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .right-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-btn {
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 1rem;
            color: #333;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 4px;
            padding: 0.5rem 0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: #333;
            padding: 0.5rem 1rem;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .home-icon {
            font-size: 1.5rem;
            color: #333;
            text-decoration: none;
        }

        .username {
            font-weight: 500;
            color: #333;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .dropdown-btn::after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 0.5rem;
        }
    </style>
    <!-- Add Font Awesome for the home icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="left-section">
            <?php foreach ($categories as $cat_id => $category): ?>
                <div class="dropdown">
                    <button class="dropdown-btn"><?php echo htmlspecialchars($category['name']); ?></button>
                    <div class="dropdown-content">
                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                            <a href="products.php?category=<?php echo $cat_id; ?>&subcategory=<?php echo $subcategory['id']; ?>">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="right-section">
            <a href="index.php" class="home-icon">
                <i class="fas fa-home"></i>
            </a>
            <span class="username">
                
    <?php 
    if (isset($user['username'])) {
        echo htmlspecialchars($_SESSION['username']);
    } else {
        echo "Guest"; // Fallback text
    }
    ?>
</span>


            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Rest of your page content goes here -->
</body>
</html>