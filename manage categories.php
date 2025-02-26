<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Ensure database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to validate names (no numbers allowed)
function validateName($name) {
    return preg_match('/^[A-Za-z ]+$/', $name);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                if (!validateName($name)) {
                    $_SESSION['error'] = "Category name must contain only letters and spaces.";
                    break;
                }
            
                $checkStmt = $conn->prepare("SELECT * FROM categories_table WHERE name = ?");
                $checkStmt->bind_param("s", $name);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
            
                if ($checkResult->num_rows > 0) {
                    $_SESSION['error'] = "Category already exists.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories_table (name, description, is_active) VALUES (?, ?, 1)");
                    $stmt->bind_param("ss", $name, $description);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Category added successfully";
                    } else {
                        $_SESSION['error'] = "Error adding category: " . $stmt->error;
                    }
                }
                break;

            case 'add_subcategory':
                $category_id = $_POST['category_id'];
                $subcategory_name = $_POST['subcategory_name'];
                $description = $_POST['description'];
                
                // if (!validateName($subcategory_name)) {
                //     $_SESSION['error'] = "Subcategory name must contain only letters and spaces.";
                //     break;
                // }
                
                $checkStmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? AND subcategory_name = ?");
                $checkStmt->bind_param("is", $category_id, $subcategory_name);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $_SESSION['error'] = "Subcategory already exists under this category.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name, description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $category_id, $subcategory_name, $description);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Subcategory added successfully";
                    } else {
                        $_SESSION['error'] = "Error adding subcategory: " . $stmt->error;
                    }
                }
                break;

                case 'add_brand':
                    $category_id = $_POST['category_id'];
                    $subcategory_id = $_POST['subcategory_id'];
                    $brand_name = $_POST['brand_name'];
                
                    // if (!validateName($brand_name)) {
                    //     $_SESSION['error'] = "Brand name must contain only letters and spaces.";
                    //     break;
                    // }
                
                    // Check if the brand already exists under the same category and subcategory
                    $checkStmt = $conn->prepare("SELECT * FROM brand_table WHERE category_id = ? AND subcategory_id = ? AND brand_name = ?");
                    $checkStmt->bind_param("iis", $category_id, $subcategory_id, $brand_name);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                
                    if ($checkResult->num_rows > 0) {
                        $_SESSION['error'] = "Brand already exists under this category/subcategory.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO brand_table (category_id, subcategory_id, brand_name) VALUES (?, ?, ?)");
                        $stmt->bind_param("iis", $category_id, $subcategory_id, $brand_name);
                        if ($stmt->execute()) {
                            $_SESSION['message'] = "Brand added successfully";
                        } else {
                            $_SESSION['error'] = "Error adding brand: " . $stmt->error;
                        }
                    }
                    break;

                    case 'add_nested_subcategory':
                        $brand_id = $_POST['brand_id'];
                        $subcategory_id = $_POST['subcategory_id']; // Correct column
                        $nested_subcategory_name = $_POST['nested_subcategory_name'];
                        $description = $_POST['description'];
                        
                        // Check if nested subcategory exists under this brand
                        $checkStmt = $conn->prepare("SELECT * FROM nested_subcategories WHERE brand_id = ? AND nested_subcategory_name = ?");
                        $checkStmt->bind_param("is", $brand_id, $nested_subcategory_name);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        
                        if ($checkResult->num_rows > 0) {
                            $_SESSION['error'] = "Nested subcategory already exists under this brand.";
                        } else {
                            // Use subcategory_id instead of parent_subcategory_id
                            $stmt = $conn->prepare("INSERT INTO nested_subcategories (parent_subcategory_id, brand_id, nested_subcategory_name, description, is_active) 
                                                    VALUES (?, ?, ?, ?, 1)");
                            $stmt->bind_param("iiss", $subcategory_id, $brand_id, $nested_subcategory_name, $description);
                            if ($stmt->execute()) {
                                $_SESSION['message'] = "Nested subcategory added successfully";
                            } else {
                                $_SESSION['error'] = "Error adding nested subcategory: " . $stmt->error;
                            }
                        }
                        break;
            // Update operations
            case 'update_category':
                $category_id = $_POST['category_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                if (!validateName($name)) {
                    $_SESSION['error'] = "Category name must contain only letters and spaces.";
                    break;
                }
                
                $checkStmt = $conn->prepare("SELECT * FROM categories_table WHERE name = ? AND category_id != ?");
                $checkStmt->bind_param("si", $name, $category_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $_SESSION['error'] = "Category name already exists.";
                } else {
                    $stmt = $conn->prepare("UPDATE categories_table SET name = ?, description = ? WHERE category_id = ?");
                    $stmt->bind_param("ssi", $name, $description, $category_id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Category updated successfully";
                    } else {
                        $_SESSION['error'] = "Error updating category: " . $stmt->error;
                    }
                }
                break;

            case 'update_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $subcategory_name = $_POST['subcategory_name'];
                $description = $_POST['description'];
                
                if (!validateName($subcategory_name)) {
                    $_SESSION['error'] = "Subcategory name must contain only letters and spaces.";
                    break;
                }
                
                $stmt = $conn->prepare("UPDATE subcategories SET subcategory_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $subcategory_name, $description, $subcategory_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Subcategory updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating subcategory: " . $stmt->error;
                }
                break;

            case 'update_brand':
                $brand_id = $_POST['brand_id'];
                $brand_name = $_POST['brand_name'];
                
                // if (!validateName($brand_name)) {
                //     $_SESSION['error'] = "Brand name must contain only letters and spaces.";
                //     break;
                // }
                
                $stmt = $conn->prepare("UPDATE brand_table SET brand_name = ? WHERE brand_id = ?");
                $stmt->bind_param("si", $brand_name, $brand_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating brand: " . $stmt->error;
                }
                break;

            case 'update_nested_subcategory':
                $nested_id = $_POST['nested_id'];
                $nested_name = $_POST['nested_name'];
                $description = $_POST['description'];
                
                // if (!validateName($nested_name)) {
                //     $_SESSION['error'] = "Nested subcategory name must contain only letters and spaces.";
                //     break;
                // }
                
                $stmt = $conn->prepare("UPDATE nested_subcategories SET nested_subcategory_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $nested_name, $description, $nested_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating nested subcategory: " . $stmt->error;
                }
                break;

            // Delete operations
            case 'soft_delete_category':
                $category_id = $_POST['category_id'];
                $conn->begin_transaction();
                try {
                    // Deactivate category
                    $stmt = $conn->prepare("UPDATE categories_table SET is_active = 0 WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    // Deactivate related subcategories
                    $stmt = $conn->prepare("UPDATE subcategories SET is_active = 0 WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    // Deactivate related brands
                    $stmt = $conn->prepare("UPDATE brand_table SET is_active = 0 
                                        WHERE subcategory_id IN 
                                        (SELECT id FROM subcategories WHERE category_id = ?)");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    // Deactivate related nested subcategories
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 
                                         WHERE brand_id IN 
                                         (SELECT brand_id FROM brand_table WHERE category_id = ?)");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Category and related items deactivated successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error deactivating category: " . $e->getMessage();
                }
                break;

            case 'soft_delete_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $conn->begin_transaction();
                try {
                    // Deactivate subcategory
                    $stmt = $conn->prepare("UPDATE subcategories SET is_active = 0 WHERE id = ?");
                    $stmt->bind_param("i", $subcategory_id);
                    $stmt->execute();
                    
                    // Deactivate related brands
                    $stmt = $conn->prepare("UPDATE brand_table SET is_active = 0 WHERE subcategory_id = ?");
                    $stmt->bind_param("i", $subcategory_id);
                    $stmt->execute();
                    
                    // Deactivate related nested subcategories
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 
                                         WHERE brand_id IN 
                                         (SELECT brand_id FROM brand_table WHERE subcategory_id = ?)");
                    $stmt->bind_param("i", $subcategory_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Subcategory and related items deactivated successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error deactivating subcategory: " . $e->getMessage();
                }
                break;

            case 'delete_brand':
                $brand_id = $_POST['brand_id'];
                $conn->begin_transaction();
                try {
                    // Deactivate brand
                    $stmt = $conn->prepare("UPDATE brand_table SET is_active = 0 WHERE brand_id = ?");
                    $stmt->bind_param("i", $brand_id);
                    $stmt->execute();
                    
                    // Deactivate related nested subcategories
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 WHERE brand_id = ?");
                    $stmt->bind_param("i", $brand_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Brand and related nested subcategories deactivated successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error deactivating brand: " . $e->getMessage();
                }
                break;

            case 'delete_nested_subcategory':
                $nested_id = $_POST['nested_id'];
                $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $nested_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory deactivated successfully";
                } else {
                    $_SESSION['error'] = "Error deactivating nested subcategory: " . $stmt->error;
                }
                break;
        }
    }
}

// Fetch all active categories with their subcategories, nested subcategories, and brands
$query = "
    SELECT 
        c.category_id, c.name as category_name, c.description as category_description,
        s.id as subcategory_id, s.subcategory_name, s.description as subcategory_description,
        b.brand_id, b.brand_name,
        ns.id as nested_id, ns.nested_subcategory_name, ns.description as nested_description
    FROM categories_table c
    LEFT JOIN subcategories s ON c.category_id = s.category_id AND s.is_active = 1
    LEFT JOIN brand_table b ON s.id = b.subcategory_id AND b.is_active = 1
    LEFT JOIN nested_subcategories ns ON (s.id = ns.parent_subcategory_id AND b.brand_id = ns.brand_id AND ns.is_active = 1)
    WHERE c.is_active = 1
    ORDER BY c.name, s.subcategory_name, b.brand_name, ns.nested_subcategory_name
";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Fetch category or subcategory details based on ID
$selected_category = null;
$selected_subcategory = null;

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    $stmt = $conn->prepare("SELECT * FROM categories_table WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $selected_category = $stmt->get_result()->fetch_assoc();
}

if (isset($_GET['subcategory_id'])) {
    $subcategory_id = intval($_GET['subcategory_id']);
    $stmt = $conn->prepare("SELECT * FROM nested_subcategories WHERE parent_subcategory_id = ?");
    $stmt->bind_param("i", $subcategory_id);
    $stmt->execute();
    $selected_subcategory = $stmt->get_result()->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: rgb(248, 191, 125); display: flex; }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: rgb(179, 69, 10);
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
        }

        .sidebar .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin: 20px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .sidebar ul li a:hover {
            color: #007bff;
        }

        .sidebar ul li .dropdown-content {
            display: none;
            margin-left: 20px;
        }

        .sidebar ul li.active .dropdown-content {
            display: block;
        }

        .sidebar ul li .dropdown-content a {
            font-size: 16px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }


        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .subcategory-list {
            margin-left: 30px;
            border-left: 2px solid #007bff;
            padding-left: 15px;
        }

        .subcategory-item {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            margin-right: 5px;
        }

        .btn-primary { background: #007bff; }
        .btn-danger { background: #dc3545; }
        .btn-edit { background:rgb(103, 157, 28); color: #000; }
        
        .category-section {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .subcategory-list {
            margin-left: 30px;
            border-left: 2px solid #007bff;
            padding-left: 15px;
        }

        .subcategory-item {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .brand-list {
            margin-left: 30px;
            border-left: 2px solid #ffc107;
            padding-left: 15px;
        }
        
        .brand-item {
            margin: 10px 0;
            padding: 10px;
            background: #fff3e0;
            border-radius: 4px;
        }
        
        .nested-subcategory-list {
            margin-left: 30px;
            border-left: 2px solid #28a745;
            padding-left: 15px;
        }
        
        .nested-subcategory-item {
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
        }
        
        .nav-breadcrumb {
            margin-bottom: 20px;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        
        .add-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        
        .hierarchy-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">BabyCubs</div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li>
                <a href="#" onclick="toggleDropdown('categories-dropdown')">
                    <i class="fas fa-list"></i> Categories <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-content" id="categories-dropdown">
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        $current_category = null;
                        $current_subcategory = null;
                        mysqli_data_seek($result, 0);

                        while ($row = mysqli_fetch_assoc($result)) {
                            // Category level
                            if ($current_category !== $row['category_name']) {
                                if ($current_category !== null) {
                                    echo "</div></div>";
                                }
                                echo "<div class='category-item'>";
                                echo "<a href='#category-{$row['category_id']}'>" . htmlspecialchars($row['category_name']) . "</a>";
                                echo "<div class='subcategory-list'>";
                                $current_category = $row['category_name'];
                            }
                            
                            // Subcategory level
                            if ($row['subcategory_id'] && $current_subcategory !== $row['subcategory_name']) {
                                echo "<a href='#subcategory-{$row['subcategory_id']}'>" . htmlspecialchars($row['subcategory_name']) . "</a>";
                                $current_subcategory = $row['subcategory_name'];
                            }
                        }
                        if ($current_category !== null) {
                            echo "</div></div>";
                        }
                    } else {
                        echo "<p>No categories found</p>";
                    }
                    ?>
                </div>
            </li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- <div class="hierarchy-info">
            <h3>Category Hierarchy</h3>
            <p>Categories → Subcategories → Brands → Nested Subcategories</p>
        </div> -->


 

        <!-- Add Category Form -->
        <div class="card">
            <h2>Add New Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
        </div>

        <!-- Categories Display -->
        <div class="card">
            <h2>Categories and Hierarchy</h2>
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                $categories = array();
                $subcategories = array();
                $brands = array();
                $nested_subcategories = array();
                
                // Organize data
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)) {
                    $cat_id = $row['category_id'];
                    $subcat_id = $row['subcategory_id'];
                    $brand_id = $row['brand_id'];
                    $nested_id = $row['nested_id'];
                    
                    // Add category
                    if (!isset($categories[$cat_id]) && $cat_id) {
                        $categories[$cat_id] = array(
                            'name' => $row['category_name'],
                            'description' => $row['category_description']
                        );
                    }
                    
                    // Add subcategory
                    if (!isset($subcategories[$subcat_id]) && $subcat_id) {
                        $subcategories[$subcat_id] = array(
                            'name' => $row['subcategory_name'],
                            'description' => $row['subcategory_description'],
                            'category_id' => $cat_id
                        );
                    }
                    
                    // Add brand
                    if (!isset($brands[$brand_id]) && $brand_id) {
                        $brands[$brand_id] = array(
                            'name' => $row['brand_name'],
                            'subcategory_id' => $subcat_id,
                            'category_id' => $cat_id
                        );
                    }
                    
                    // Add nested subcategory
                    if (!isset($nested_subcategories[$nested_id]) && $nested_id) {
                        $nested_subcategories[$nested_id] = array(
                            'name' => $row['nested_subcategory_name'],
                            'description' => $row['nested_description'],
                            'brand_id' => $brand_id,
                            'subcategory_id' => $subcat_id
                        );
                    }
                }
                
                // Display hierarchy
                foreach ($categories as $cat_id => $category) {
                    echo "<div class='category-section' id='category-{$cat_id}'>";
                    echo "<div class='category-header'>";
                    echo "<h3>" . htmlspecialchars($category['name']) . "</h3>";
                    echo "<div class='action-buttons'>";
                    echo "<button onclick='showEditCategoryModal({$cat_id}, \"" . htmlspecialchars($category['name']) . "\", \"" . htmlspecialchars($category['description']) . "\")' class='btn btn-edit'>Edit</button>";
                    echo "<button onclick='showAddSubcategoryModal({$cat_id}, \"" . htmlspecialchars($category['name']) . "\")' class='btn btn-primary'>Add Subcategory</button>";
                    echo "<form method='POST' style='display: inline;'>";
                    echo "<input type='hidden' name='action' value='soft_delete_category'>";
                    echo "<input type='hidden' name='category_id' value='{$cat_id}'>";
                    echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure? This will deactivate all related items.\")'>Delete</button>";
                    echo "</form>";
                    echo "</div></div>";
                    echo "<p>" . htmlspecialchars($category['description']) . "</p>";
                    
                    // Display subcategories
                    echo "<div class='subcategory-list'>";
                    foreach ($subcategories as $subcat_id => $subcategory) {
                        if ($subcategory['category_id'] == $cat_id) {
                            echo "<div class='subcategory-item' id='subcategory-{$subcat_id}'>";
                            echo "<strong>" . htmlspecialchars($subcategory['name']) . "</strong>";
                            echo "<p>" . htmlspecialchars($subcategory['description']) . "</p>";
                            echo "<div class='action-buttons'>";
                            echo "<button onclick='showEditSubcategoryModal({$subcat_id}, \"" . htmlspecialchars($subcategory['name']) . "\", \"" . htmlspecialchars($subcategory['description']) . "\")' class='btn btn-edit'>Edit</button>";
                            echo "<button onclick='showAddBrandModal({$cat_id}, {$subcat_id})' class='btn btn-primary'>Add Brand</button>";
                            echo "<form method='POST' style='display: inline;'>";
                            echo "<input type='hidden' name='action' value='soft_delete_subcategory'>";
                            echo "<input type='hidden' name='subcategory_id' value='{$subcat_id}'>";
                            echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure? This will deactivate all related brands and nested subcategories.\")'>Delete</button>";
                            echo "</form>";
                            echo "</div>";
                            
                            // Display brands
                            echo "<div class='brand-list'>";
                            foreach ($brands as $brand_id => $brand) {
                                if ($brand['subcategory_id'] == $subcat_id) {
                                    echo "<div class='brand-item' id='brand-{$brand_id}'>";
                                    echo "<strong>" . htmlspecialchars($brand['name']) . "</strong>";
                                    echo "<div class='action-buttons'>";
                                    echo "<button onclick='showEditBrandModal({$brand_id}, \"" . htmlspecialchars($brand['name']) . "\")' class='btn btn-edit'>Edit</button>";
                                    echo "<button onclick='showAddNestedSubcategoryModal({$brand_id}, {$subcat_id})' class='btn btn-primary'>Add Nested Subcategory</button>";
                                    echo "<form method='POST' style='display: inline;'>";
                                    echo "<input type='hidden' name='action' value='delete_brand'>";
                                    echo "<input type='hidden' name='brand_id' value='{$brand_id}'>";
                                    echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure? This will deactivate all related nested subcategories.\")'>Delete</button>";
                                    echo "</form>";
                                    echo "</div>";
                                    
                                    // Display nested subcategories
                                    echo "<div class='nested-subcategory-list'>";
foreach ($nested_subcategories as $nested_id => $nested_subcategory) {
    if ($nested_subcategory['brand_id'] == $brand_id) {
        echo "<div class='nested-subcategory-item' id='nested-{$nested_id}'>";
                                            echo "<strong>" . htmlspecialchars($nested_subcategory['name']) . "</strong>";
                                            echo "<p>" . htmlspecialchars($nested_subcategory['description']) . "</p>";
                                            echo "<div class='action-buttons'>";
                                            echo "<button onclick='showEditNestedSubcategoryModal({$nested_id}, \"" . htmlspecialchars($nested_subcategory['name']) . "\", \"" . htmlspecialchars($nested_subcategory['description']) . "\")' class='btn btn-edit'>Edit</button>";
                                            echo "<form method='POST' style='display: inline;'>";
                                            echo "<input type='hidden' name='action' value='delete_nested_subcategory'>";
                                            echo "<input type='hidden' name='nested_id' value='{$nested_id}'>";
                                            echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure? This will deactivate the nested subcategory.\")'>Delete</button>";
                                            echo "</form>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                    }
                                    echo "</div>"; // Close nested-subcategory-list
                                    echo "</div>"; // Close brand-item
                                }
                            }
                            echo "</div>"; // Close brand-list
                            echo "</div>"; // Close subcategory-item
                        }
                    }
                    echo "</div>"; // Close subcategory-list
                    echo "</div>"; // Close category-section
                }
            } else {
                echo "<p>No categories found.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Modals for Adding and Editing -->
    <!-- Add Subcategory Modal -->
    <div id="addSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addSubcategoryModal')">&times;</span>
            <h2>Add Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_subcategory">
                <input type="hidden" name="category_id" id="addSubcategoryCategoryId">
                <div class="form-group">
                    <label>Subcategory Name</label>
                    <input type="text" name="subcategory_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Subcategory</button>
            </form>
        </div>
    </div>

    <!-- Add Brand Modal -->
    <div id="addBrandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addBrandModal')">&times;</span>
            <h2>Add Brand</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_brand">
                <input type="hidden" name="category_id" id="addBrandCategoryId">
                <input type="hidden" name="subcategory_id" id="addBrandSubcategoryId">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Brand</button>
            </form>
        </div>
    </div>

    <!-- Add Nested Subcategory Modal -->
    <div id="addNestedSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addNestedSubcategoryModal')">&times;</span>
            <h2>Add Nested Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_nested_subcategory">
                <input type="hidden" name="brand_id" id="addNestedSubcategoryBrandId">
                <input type="hidden" name="subcategory_id" id="addNestedSubcategorySubcategoryId">
                <div class="form-group">
                    <label>Nested Subcategory Name</label>
                    <input type="text" name="nested_subcategory_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Nested Subcategory</button>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
            <h2>Edit Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="editCategoryId">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" id="editCategoryName" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editCategoryDescription" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </form>
        </div>
    </div>

    <!-- Edit Subcategory Modal -->
    <div id="editSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editSubcategoryModal')">&times;</span>
            <h2>Edit Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_subcategory">
                <input type="hidden" name="subcategory_id" id="editSubcategoryId">
                <div class="form-group">
                    <label>Subcategory Name</label>
                    <input type="text" name="subcategory_name" id="editSubcategoryName" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editSubcategoryDescription" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Subcategory</button>
            </form>
        </div>
    </div>

    <!-- Edit Brand Modal -->
    <div id="editBrandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editBrandModal')">&times;</span>
            <h2>Edit Brand</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_brand">
                <input type="hidden" name="brand_id" id="editBrandId">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" id="editBrandName" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Brand</button>
            </form>
        </div>
    </div>

    <!-- Edit Nested Subcategory Modal -->
    <div id="editNestedSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editNestedSubcategoryModal')">&times;</span>
            <h2>Edit Nested Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_nested_subcategory">
                <input type="hidden" name="nested_id" id="editNestedSubcategoryId">
                <div class="form-group">
                    <label>Nested Subcategory Name</label>
                    <input type="text" name="nested_name" id="editNestedSubcategoryName" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editNestedSubcategoryDescription" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Nested Subcategory</button>
            </form>
        </div>
    </div>

    <script>


// JavaScript function to toggle dropdown
function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        // Function to show modals
        function showAddSubcategoryModal(categoryId, categoryName) {
            document.getElementById('addSubcategoryCategoryId').value = categoryId;
            document.getElementById('addSubcategoryModal').style.display = 'block';
        }

        function showAddBrandModal(categoryId, subcategoryId) {
            document.getElementById('addBrandCategoryId').value = categoryId;
            document.getElementById('addBrandSubcategoryId').value = subcategoryId;
            document.getElementById('addBrandModal').style.display = 'block';
        }

        function showAddNestedSubcategoryModal(brandId, subcategoryId) {
            document.getElementById('addNestedSubcategoryBrandId').value = brandId;
            document.getElementById('addNestedSubcategorySubcategoryId').value = subcategoryId;
            document.getElementById('addNestedSubcategoryModal').style.display = 'block';
        }

        function showEditCategoryModal(categoryId, categoryName, categoryDescription) {
            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editCategoryName').value = categoryName;
            document.getElementById('editCategoryDescription').value = categoryDescription;
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function showEditSubcategoryModal(subcategoryId, subcategoryName, subcategoryDescription) {
            document.getElementById('editSubcategoryId').value = subcategoryId;
            document.getElementById('editSubcategoryName').value = subcategoryName;
            document.getElementById('editSubcategoryDescription').value = subcategoryDescription;
            document.getElementById('editSubcategoryModal').style.display = 'block';
        }

        function showEditBrandModal(brandId, brandName) {
            document.getElementById('editBrandId').value = brandId;
            document.getElementById('editBrandName').value = brandName;
            document.getElementById('editBrandModal').style.display = 'block';
        }

        function showEditNestedSubcategoryModal(nestedId, nestedName, nestedDescription) {
            document.getElementById('editNestedSubcategoryId').value = nestedId;
            document.getElementById('editNestedSubcategoryName').value = nestedName;
            document.getElementById('editNestedSubcategoryDescription').value = nestedDescription;
            document.getElementById('editNestedSubcategoryModal').style.display = 'block';
        }

        // Function to close modals
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        function showAddSubcategoryModal(categoryId, categoryName) {
    document.getElementById('addSubcategoryCategoryId').value = categoryId;
    document.getElementById('addSubcategoryModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
    </script>
</body>
</html>