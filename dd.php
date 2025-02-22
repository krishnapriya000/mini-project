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

// Function to validate category/subcategory names (no numbers allowed)
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
                
                // Validate category name
                if (!validateName($name)) {
                    $_SESSION['error'] = "Category name must contain only letters and spaces.";
                    break;
                }
            
                // Check if category already exists
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
                    $subcategory_name = $_POST['subcategory_name'];  // Changed from subcategory_names
                    $description = $_POST['description'];
                    
                    // Validate subcategory name
                    if (!validateName($subcategory_name)) {
                        $_SESSION['error'] = "Subcategory names must contain only letters and spaces.";
                        // continue;
                    }
                    
                    // Check if subcategory already exists under the same category
                    $checkStmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? AND subcategory_name = ?");
                    $checkStmt->bind_param("is", $category_id, $subcategory_name);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name, description) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $category_id, $subcategory_name, $description);
                        $stmt->execute();
                    }
                
                $_SESSION['message'] = "Subcategories added successfully.";
                break;

            case 'add_nested_subcategory':
                $parent_subcategory_id = $_POST['parent_subcategory_id'];
                $nested_subcategory_names = $_POST['nested_subcategory_names']; // Expecting an array of nested subcategories
                $description = $_POST['description'];
                
                foreach ($nested_subcategory_names as $nested_subcategory_name) {
                    // Validate nested subcategory name
                    if (!validateName($nested_subcategory_name)) {
                        $_SESSION['error'] = "Nested subcategory names must contain only letters and spaces.";
                        continue;
                    }
                    
                    // Check if nested subcategory already exists
                    $checkStmt = $conn->prepare("SELECT * FROM nested_subcategories WHERE parent_subcategory_id = ? AND nested_subcategory_name = ?");
                    $checkStmt->bind_param("is", $parent_subcategory_id, $nested_subcategory_name);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO nested_subcategories (parent_subcategory_id, nested_subcategory_name, description) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $parent_subcategory_id, $nested_subcategory_name, $description);
                        $stmt->execute();
                    }
                }
                $_SESSION['message'] = "Nested subcategories added successfully.";
                break;
    









            case 'soft_delete_category':
                $category_id = $_POST['category_id'];
                
                // Start transaction
                $conn->begin_transaction();
                try {
                    // Deactivate category
                    $stmt = $conn->prepare("UPDATE categories_table SET is_active = 0 WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    // Deactivate all related subcategories
                    $stmt = $conn->prepare("UPDATE subcategories SET is_active = 0 WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Category and its subcategories have been deactivated";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error deactivating category: " . $e->getMessage();
                }
                break;

            case 'soft_delete_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $stmt = $conn->prepare("UPDATE subcategories SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $subcategory_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Subcategory has been deactivated";
                } else {
                    $_SESSION['error'] = "Error deactivating subcategory: " . $stmt->error;
                }
                break;

                case 'update_category':
                    $category_id = $_POST['category_id'];
                    $name = $_POST['name'];
                    $description = $_POST['description'];
                
                    // Check if the new name already exists (excluding the current category)
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
                    
                        // Check if the new subcategory name already exists under the same category (excluding the current subcategory)
                        $checkStmt = $conn->prepare("SELECT * FROM subcategories WHERE subcategory_name = ? AND category_id = (SELECT category_id FROM subcategories WHERE id = ?) AND id != ?");
                        $checkStmt->bind_param("sii", $subcategory_name, $subcategory_id, $subcategory_id);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                    
                        if ($checkResult->num_rows > 0) {
                            $_SESSION['error'] = "Subcategory name already exists under this category.";
                        } else {
                            $stmt = $conn->prepare("UPDATE subcategories SET subcategory_name = ?, description = ? WHERE id = ?");
                            $stmt->bind_param("ssi", $subcategory_name, $description, $subcategory_id);
                            if ($stmt->execute()) {
                                $_SESSION['message'] = "Subcategory updated successfully";
                            } else {
                                $_SESSION['error'] = "Error updating subcategory: " . $stmt->error;
                            }
                        }
                        break;
        }
        // header("Location: category_admin.php");
        // exit();
    }
}
// Fetch categories and subcategories for the sidebar
$sidebarCategoriesQuery = "SELECT c.category_id, c.name as category_name, s.id as subcategory_id, s.subcategory_name 
                           FROM categories_table c 
                           LEFT JOIN subcategories s ON c.category_id = s.category_id 
                           WHERE c.is_active = 1 AND (s.is_active = 1 OR s.is_active IS NULL)
                           ORDER BY c.name, s.subcategory_name";
$sidebarCategoriesResult = mysqli_query($conn, $sidebarCategoriesQuery);


// Fetch categories and subcategories
$categoriesQuery = "SELECT * FROM categories_table WHERE is_active = 1 ORDER BY name";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

$subcategoriesQuery = "SELECT s.*, c.name as category_name 
                      FROM subcategories s 
                      JOIN categories_table c ON s.category_id = c.category_id 
                      WHERE s.is_active = 1 AND c.is_active = 1
                      ORDER BY c.name, s.subcategory_name";
$subcategoriesResult = mysqli_query($conn, $subcategoriesQuery);
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
                    if ($sidebarCategoriesResult && mysqli_num_rows($sidebarCategoriesResult) > 0) {
                        $currentCategory = null;
                        while ($row = mysqli_fetch_assoc($sidebarCategoriesResult)) {
                            if ($currentCategory !== $row['category_name']) {
                                if ($currentCategory !== null) {
                                    echo "</div>"; // Close previous subcategory list
                                }
                                echo "<div class='category-item'>";
                                echo "<strong>" . htmlspecialchars($row['category_name']) . "</strong>";
                                echo "<div class='subcategory-list'>";
                                $currentCategory = $row['category_name'];
                            }
                            if ($row['subcategory_name']) {
                                echo "<a href='#'>" . htmlspecialchars($row['subcategory_name']) . "</a>";
                            }
                        }
                        if ($currentCategory !== null) {
                            echo "</div></div>"; // Close the last category and subcategory list
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
    <div class="message success">
        <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="message error">
        <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

        <div class="card">
            <h2>Add New Category</h2><br><br>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
    <label>Subcategory Name</label>
    <input type="text" name="subcategory_name" required>
</div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
        </div>

        <!-- Category and Subcategory Display -->
        <div class="card">
            <h2>Categories and Subcategories</h2><br><br>
            <?php
            if ($categoriesResult && mysqli_num_rows($categoriesResult) > 0) {
                while ($category = mysqli_fetch_assoc($categoriesResult)) {
                    echo "<div class='category-section'>";
                    echo "<div class='category-header'>";
                    echo "<h3>" . htmlspecialchars($category['name']) . "</h3>";
                    echo "<div class='action-buttons'>";
                    echo "<button onclick='showEditCategoryModal({$category['category_id']}, \"" . htmlspecialchars($category['name']) . "\", \"" . htmlspecialchars($category['description']) . "\")' class='btn btn-edit'>Edit</button>";
                    echo "<button onclick='showAddSubcategoryModal({$category['category_id']}, \"" . htmlspecialchars($category['name']) . "\")' class='btn btn-primary'>Add Subcategory</button>";
                    echo "<form method='POST' style='display: inline;'>";
                    echo "<input type='hidden' name='action' value='soft_delete_category'>";
                    echo "<input type='hidden' name='category_id' value='{$category['category_id']}'>";
                    echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to deactivate this category and all its subcategories?\")'>Delete</button>";
                    echo "</form>";
                    echo "</div></div>";
                    echo "<p>" . htmlspecialchars($category['description']) . "</p>";
                    
                    // Display subcategories
                    if ($subcategoriesResult) {
                        mysqli_data_seek($subcategoriesResult, 0);
                        echo "<div class='subcategory-list'>";
                        $hasSubcategories = false;
                        while ($subcategory = mysqli_fetch_assoc($subcategoriesResult)) {
                            if ($subcategory['category_id'] == $category['category_id']) {
                                $hasSubcategories = true;
                                echo "<div class='subcategory-item'>";
                                echo "<strong>" . htmlspecialchars($subcategory['subcategory_name']) . "</strong>";
                                echo "<p>" . htmlspecialchars($subcategory['description']) . "</p>";
                                echo "<div class='action-buttons'>";
                                echo "<button onclick='showEditSubcategoryModal({$subcategory['id']}, \"" . htmlspecialchars($subcategory['subcategory_name']) . "\", \"" . htmlspecialchars($subcategory['description']) . "\")' class='btn btn-edit'>Edit</button>";
                                echo "<form method='POST' style='display: inline;'>";
                                echo "<input type='hidden' name='action' value='soft_delete_subcategory'>";
                                echo "<input type='hidden' name='subcategory_id' value='{$subcategory['id']}'>";
                                echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to deactivate this subcategory?\")'>Delete</button>";
                                echo "</form>";
                                echo "</div></div>";
                            }
                        }
                        if (!$hasSubcategories) {
                            echo "<p>No subcategories yet</p>";
                        }
                        echo "</div>";
                    }
                    echo "</div>";
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
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function showAddSubcategoryModal(categoryId, categoryName) {
            document.getElementById('subcategory_parent_id').value = categoryId;
            document.getElementById('subcategory_parent_name').value = categoryName;
            document.getElementById('addSubcategoryModal').style.display = 'block';
        }

        function showEditCategoryModal(categoryId, name, description) {
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_description').value = description;
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function showEditSubcategoryModal(subcategoryId, name, description) {
            document.getElementById('edit_subcategory_id').value = subcategoryId;
            document.getElementById('edit_subcategory_name').value = name;
            document.getElementById('edit_subcategory_description').value = description;
            document.getElementById('editSubcategoryModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>