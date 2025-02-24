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
                
                if (!validateName($subcategory_name)) {
                    $_SESSION['error'] = "Subcategory name must contain only letters and spaces.";
                    break;
                }
                
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

            case 'add_nested_subcategory':
                $parent_subcategory_id = $_POST['parent_subcategory_id'];
                $nested_subcategory_name = $_POST['nested_subcategory_name'];
                $description = $_POST['description'];
                
                if (!validateName($nested_subcategory_name)) {
                    $_SESSION['error'] = "Nested subcategory name must contain only letters and spaces.";
                    break;
                }
                
                $checkStmt = $conn->prepare("SELECT * FROM nested_subcategories WHERE parent_subcategory_id = ? AND nested_subcategory_name = ?");
                $checkStmt->bind_param("is", $parent_subcategory_id, $nested_subcategory_name);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $_SESSION['error'] = "Nested subcategory already exists.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO nested_subcategories (parent_subcategory_id, nested_subcategory_name, description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $parent_subcategory_id, $nested_subcategory_name, $description);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Nested subcategory added successfully";
                    } else {
                        $_SESSION['error'] = "Error adding nested subcategory: " . $stmt->error;
                    }
                }
                break;

            case 'add_brand':
                $category_id = $_POST['category_id'];
                $subcategory_id = $_POST['subcategory_id'];
                $brand_name = $_POST['brand_name'];
                
                if (!validateName($brand_name)) {
                    $_SESSION['error'] = "Brand name must contain only letters and spaces.";
                    break;
                }
                
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

            case 'update_nested_subcategory':
                $nested_id = $_POST['nested_id'];
                $nested_name = $_POST['nested_name'];
                $description = $_POST['description'];
                
                if (!validateName($nested_name)) {
                    $_SESSION['error'] = "Nested subcategory name must contain only letters and spaces.";
                    break;
                }
                
                $stmt = $conn->prepare("UPDATE nested_subcategories SET nested_subcategory_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $nested_name, $description, $nested_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating nested subcategory: " . $stmt->error;
                }
                break;

            case 'update_brand':
                $brand_id = $_POST['brand_id'];
                $brand_name = $_POST['brand_name'];
                
                if (!validateName($brand_name)) {
                    $_SESSION['error'] = "Brand name must contain only letters and spaces.";
                    break;
                }
                
                $stmt = $conn->prepare("UPDATE brand_table SET brand_name = ? WHERE brand_id = ?");
                $stmt->bind_param("si", $brand_name, $brand_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating brand: " . $stmt->error;
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
                    
                    // Deactivate related nested subcategories
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 
                                         WHERE parent_subcategory_id IN 
                                         (SELECT id FROM subcategories WHERE category_id = ?)");
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
                    
                    // Deactivate related nested subcategories
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 WHERE parent_subcategory_id = ?");
                    $stmt->bind_param("i", $subcategory_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Subcategory and nested subcategories deactivated successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error deactivating subcategory: " . $e->getMessage();
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

            case 'delete_brand':
                $brand_id = $_POST['brand_id'];
                $stmt = $conn->prepare("DELETE FROM brand_table WHERE brand_id = ?");
                $stmt->bind_param("i", $brand_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting brand: " . $stmt->error;
                }
                break;

                case 'update_nested_subcategory':
                    $nested_id = $_POST['nested_id'];
                    $nested_name = $_POST['nested_name'];
                    $description = $_POST['description'];
                    
                    if (!validateName($nested_name)) {
                        $_SESSION['error'] = "Nested subcategory name must contain only letters and spaces.";
                        break;
                    }
                    
                    $stmt = $conn->prepare("UPDATE nested_subcategories SET nested_subcategory_name = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $nested_name, $description, $nested_id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Nested subcategory updated successfully";
                    } else {
                        $_SESSION['error'] = "Error updating nested subcategory: " . $stmt->error;
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
        ns.id as nested_id, ns.nested_subcategory_name, ns.description as nested_description,
        b.brand_id, b.brand_name
    FROM categories_table c
    LEFT JOIN subcategories s ON c.category_id = s.category_id AND s.is_active = 1
    LEFT JOIN nested_subcategories ns ON s.id = ns.parent_subcategory_id AND ns.is_active = 1
    LEFT JOIN brand_table b ON s.id = b.subcategory_id
    WHERE c.is_active = 1
    ORDER BY c.name, s.subcategory_name, ns.nested_subcategory_name, b.brand_name
";
$result = $conn->query($query);
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

        .nested-subcategory-list {
            margin-left: 45px;
            border-left: 2px solid #28a745;
            padding-left: 15px;
        }
        
        .brand-list {
            margin-left: 60px;
            border-left: 2px solid #ffc107;
            padding-left: 15px;
        }
        
        .nested-subcategory-item {
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        
        .brand-item {
            margin: 10px 0;
            padding: 10px;
            background: #fff3e0;
            border-radius: 4px;
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
            <h2>Categories and Subcategories</h2>
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                $current_category = null;
                $current_subcategory = null;
                mysqli_data_seek($result, 0);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    // Category level
                    if ($current_category !== $row['category_id']) {
                        if ($current_category !== null) {
                            echo "</div></div>";
                        }
                        echo "<div class='category-section' id='category-{$row['category_id']}'>";
                        echo "<div class='category-header'>";
                        echo "<h3>" . htmlspecialchars($row['category_name']) . "</h3>";
                        echo "<div class='action-buttons'>";
                        echo "<button onclick='showEditCategoryModal({$row['category_id']}, \"" . htmlspecialchars($row['category_name']) . "\", \"" . htmlspecialchars($row['category_description']) . "\")' class='btn btn-edit'>Edit</button>";
                        echo "<button onclick='showAddSubcategoryModal({$row['category_id']}, \"" . htmlspecialchars($row['category_name']) . "\")' class='btn btn-primary'>Add Subcategory</button>";
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='action' value='soft_delete_category'>";
                        echo "<input type='hidden' name='category_id' value='{$row['category_id']}'>";
                        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>";
                        echo "</form>";
                        echo "</div></div>";
                        echo "<p>" . htmlspecialchars($row['category_description']) . "</p>";
                        echo "<div class='subcategory-list'>";
                        $current_category = $row['category_id'];
                    }

                    // Subcategory level
                    if ($row['subcategory_id'] && $current_subcategory !== $row['subcategory_id']) {
                        if ($current_subcategory !== null) {
                            echo "</div>";
                        }
                        echo "<div class='subcategory-item' id='subcategory-{$row['subcategory_id']}'>";
                        echo "<strong>" . htmlspecialchars($row['subcategory_name']) . "</strong>";
                        echo "<p>" . htmlspecialchars($row['subcategory_description']) . "</p>";
                        echo "<div class='action-buttons'>";
                        echo "<button onclick='showEditSubcategoryModal({$row['subcategory_id']}, \"" . htmlspecialchars($row['subcategory_name']) . "\", \"" . htmlspecialchars($row['subcategory_description']) . "\")' class='btn btn-edit'>Edit</button>";
                        echo "<button onclick='showAddNestedSubcategoryModal({$row['subcategory_id']}, \"" . htmlspecialchars($row['subcategory_name']) . "\")' class='btn btn-primary'>Add Nested</button>";
                        echo "<button onclick='showAddBrandModal({$row['category_id']}, {$row['subcategory_id']})' class='btn btn-primary'>Add Brand</button>";
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='action' value='soft_delete_subcategory'>";
                        echo "<input type='hidden' name='subcategory_id' value='{$row['subcategory_id']}'>";
                        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>";
                        echo "</form>";
                        echo "</div>";
                        $current_subcategory = $row['subcategory_id'];
                    }

                   // Display nested subcategories if they exist
if ($row['nested_id']) {
    echo "<div class='nested-subcategory-item'>";
    echo "<strong>" . htmlspecialchars($row['nested_subcategory_name']) . "</strong>";
    echo "<p>" . htmlspecialchars($row['nested_description']) . "</p>";
    echo "<div class='action-buttons'>";
    echo "<button onclick='showEditNestedSubcategoryModal({$row['nested_id']}, \"" . htmlspecialchars($row['nested_subcategory_name']) . "\", \"" . htmlspecialchars($row['nested_description']) . "\")' class='btn btn-edit'>Edit</button>";
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='action' value='delete_nested_subcategory'>";
    echo "<input type='hidden' name='nested_id' value='{$row['nested_id']}'>";
    echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>";
    echo "</form>";
    echo "</div></div>";
}

                    // Display brands if they exist
                    if ($row['brand_id']) {
                        echo "<div class='brand-item'>";
                        echo "<strong>" . htmlspecialchars($row['brand_name']) . "</strong>";
                        echo "<div class='action-buttons'>";
                        echo "<button onclick='showEditBrandModal({$row['brand_id']}, \"" . htmlspecialchars($row['brand_name']) . "\")' class='btn btn-edit'>Edit</button>";
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='action' value='delete_brand'>";
                        echo "<input type='hidden' name='brand_id' value='{$row['brand_id']}'>";
                        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>";
                        echo "</form>";
                        echo "</div></div>";
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
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
            <h2>Edit Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" id="edit_category_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_category_description" required></textarea>
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
                <input type="hidden" name="subcategory_id" id="edit_subcategory_id">
                <div class="form-group">
                    <label>Subcategory Name</label>
                    <input type="text" name="subcategory_name" id="edit_subcategory_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_subcategory_description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Subcategory</button>
            </form>
        </div>
    </div>

    <!-- Add Subcategory Modal -->
    <div id="addSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addSubcategoryModal')">&times;</span>
            <h2>Add New Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_subcategory">
                <input type="hidden" name="category_id" id="subcategory_parent_id">
                <div class="form-group">
                    <label>Parent Category</label>
                    <input type="text" id="subcategory_parent_name" disabled>
                </div>
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

    <!-- Add Nested Subcategory Modal -->
    <div id="addNestedSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addNestedSubcategoryModal')">&times;</span>
            <h2>Add Nested Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_nested_subcategory">
                <input type="hidden" name="parent_subcategory_id" id="nested_parent_id">
                <div class="form-group">
                    <label>Parent Subcategory</label>
                    <input type="text" id="nested_parent_name" disabled>
                </div>
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

    <!-- Add Brand Modal -->
    <div id="addBrandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addBrandModal')">&times;</span>
            <h2>Add Brand</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_brand">
                <input type="hidden" name="category_id" id="brand_category_id">
                <input type="hidden" name="subcategory_id" id="brand_subcategory_id">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Brand</button>
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
                <input type="hidden" name="brand_id" id="edit_brand_id">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" id="edit_brand_name" required>
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
            <input type="hidden" name="nested_id" id="edit_nested_id">
            <div class="form-group">
                <label>Nested Subcategory Name</label>
                <input type="text" name="nested_name" id="edit_nested_name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_nested_description" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Nested Subcategory</button>
        </form>
    </div>
</div>

    <script>
        // JavaScript functions to handle modals
        function showEditCategoryModal(categoryId, categoryName, categoryDescription) {
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category_name').value = categoryName;
            document.getElementById('edit_category_description').value = categoryDescription;
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function showEditSubcategoryModal(subcategoryId, subcategoryName, subcategoryDescription) {
            document.getElementById('edit_subcategory_id').value = subcategoryId;
            document.getElementById('edit_subcategory_name').value = subcategoryName;
            document.getElementById('edit_subcategory_description').value = subcategoryDescription;
            document.getElementById('editSubcategoryModal').style.display = 'block';
        }

        function showAddSubcategoryModal(categoryId, categoryName) {
            document.getElementById('subcategory_parent_id').value = categoryId;
            document.getElementById('subcategory_parent_name').value = categoryName;
            document.getElementById('addSubcategoryModal').style.display = 'block';
        }

        function showAddNestedSubcategoryModal(parentId, parentName) {
            document.getElementById('nested_parent_id').value = parentId;
            document.getElementById('nested_parent_name').value = parentName;
            document.getElementById('addNestedSubcategoryModal').style.display = 'block';
        }

        function showAddBrandModal(categoryId, subcategoryId) {
            document.getElementById('brand_category_id').value = categoryId;
            document.getElementById('brand_subcategory_id').value = subcategoryId;
            document.getElementById('addBrandModal').style.display = 'block';
        }

        function showEditBrandModal(brandId, brandName) {
            document.getElementById('edit_brand_id').value = brandId;
            document.getElementById('edit_brand_name').value = brandName;
            document.getElementById('editBrandModal').style.display = 'block';
        }
        function showEditNestedSubcategoryModal(nestedId, nestedName, nestedDescription) {
    document.getElementById('edit_nested_id').value = nestedId;
    document.getElementById('edit_nested_name').value = nestedName;
    document.getElementById('edit_nested_description').value = nestedDescription;
    document.getElementById('editNestedSubcategoryModal').style.display = 'block';
}

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none'
            }
        }

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>