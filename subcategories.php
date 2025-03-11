<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_subcategory':
                $category_id = $_POST['category_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
                    $_SESSION['error'] = "Subcategory name must contain only letters and spaces.";
                    break;
                }
                
                $checkStmt = $conn->prepare("SELECT * FROM subcategories_table WHERE subcategory_name = ? AND category_id = ?");
                $checkStmt->bind_param("si", $name, $category_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $_SESSION['error'] = "Subcategory already exists in this category.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO subcategories (category_id, subcategory_name, description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $category_id, $name, $description);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Subcategory added successfully";
                    } else {
                        $_SESSION['error'] = "Error adding subcategory: " . $stmt->error;
                    }
                }
                break;

            case 'update_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $category_id = $_POST['category_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                $stmt = $conn->prepare("UPDATE subcategories SET category_id = ?, subcategory_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("issi", $category_id, $name, $description, $subcategory_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Subcategory updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating subcategory: " . $stmt->error;
                }
                break;

            case 'delete_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $stmt = $conn->prepare("UPDATE subcategories SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $subcategory_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Subcategory deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting subcategory: " . $stmt->error;
                }
                break;
        }
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories_table WHERE is_active = 1";
$categories_result = $conn->query($categories_query);

// Get selected category from filter
$selected_category = isset($_GET['category_id']) ? $_GET['category_id'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Add this CSS section in the head of your subcategories.php file -->
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        display: flex;
        background-color: #f0f2f5;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background-color: #2b2d42;
        color: white;
        height: 100vh;
        padding: 20px 0;
        position: fixed;
    }

    .brand-name {
        font-size: 24px;
        padding: 20px;
        border-bottom: 1px solid #3d3f54;
    }

    .nav-items {
        list-style: none;
        padding: 20px 0;
    }

    .nav-item {
        padding: 10px 20px;
        cursor: pointer;
    }

    .nav-item a {
        color: white;
        text-decoration: none;
        display: block;
    }

    .nav-item:hover {
        background-color: #3d3f54;
    }

    .nav-item.active {
        background-color: #3d3f54;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        padding: 20px;
        margin-left: 250px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .add-btn {
        background-color: #4CAF50;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .success-message {
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .error-message {
        background-color: #f44336;
        color: white;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    /* Table Styles */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .data-table th,
    .data-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .data-table th {
        background-color: #2b2d42;
        color: white;
    }

    .data-table tr:hover {
        background-color: #f5f5f5;
    }

    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .edit-btn {
        background-color: #2196F3;
        color: white;
    }

    .delete-btn {
        background-color: #f44336;
        color: white;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 5px;
        width: 50%;
        max-width: 500px;
    }

    .close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-group textarea {
        height: 100px;
        resize: vertical;
    }

    /* Additional styles for subcategories */
    .category-filter {
        margin-bottom: 20px;
    }

    .category-filter select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 200px;
        font-size: 14px;
    }

    .category-filter select:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }

    .btn-primary:hover {
        background-color: #45a049;
    }

    .btn-outline {
        background-color: transparent;
        border: 1px solid #ddd;
        color: #333;
    }

    .btn-outline:hover {
        background-color: #f5f5f5;
    }

    /* Responsive styles */
    @media screen and (max-width: 768px) {
        .sidebar {
            width: 200px;
        }

        .main-content {
            margin-left: 200px;
        }

        .modal-content {
            width: 90%;
            margin: 10% auto;
        }

        .data-table {
            font-size: 14px;
        }

        .action-btn {
            padding: 4px 8px;
            font-size: 12px;
        }
    }

    @media screen and (max-width: 480px) {
        .sidebar {
            width: 160px;
        }

        .main-content {
            margin-left: 160px;
            padding: 10px;
        }

        .header {
            flex-direction: column;
            gap: 10px;
        }

        .category-filter {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-name">BabyCubs</div>
        <ul class="nav-items">
            <li class="nav-item">
                <a href="dd.php"><i class="fas fa-list"></i> Categories</a>
            </li>
            <li class="nav-item active">
                <a href="subcategories.php"><i class="fas fa-sitemap"></i> Subcategories</a>
            </li>
            <li class="nav-item">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Subcategories</h2>
            <button class="add-btn" onclick="showAddSubcategoryModal()">Add Subcategory</button>
        </div>

        <!-- Category Filter Dropdown -->
        <div class="category-filter">
            <form action="" method="GET" id="categoryFilterForm">
                <select name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php while($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" 
                                <?php echo ($selected_category == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Subcategory Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Modify query based on category filter
                $query = "SELECT s.*, c.name as category_name 
                         FROM subcategories s 
                         JOIN categories_table c ON s.category_id = c.category_id 
                         WHERE s.is_active = 1";
                
                if (!empty($selected_category)) {
                    $query .= " AND s.category_id = " . $conn->real_escape_string($selected_category);
                }
                
                $result = $conn->query($query);
                
                while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['subcategory_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editSubcategory(
                                <?php echo $row['id']; ?>,
                                <?php echo $row['category_id']; ?>,
                                '<?php echo addslashes($row['subcategory_name']); ?>',
                                '<?php echo addslashes($row['description']); ?>'
                            )">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteSubcategory(<?php echo $row['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Subcategory Modal -->
    <div id="addSubcategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addSubcategoryModal')">&times;</span>
            <h2>Add New Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_subcategory">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php
                        // Reset the categories result pointer
                        $categories_result->data_seek(0);
                        while($category = $categories_result->fetch_assoc()) {
                            echo "<option value='".$category['category_id']."'>".htmlspecialchars($category['name'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory Name</label>
                    <input type="text" name="name" required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Subcategory</button>
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
                    <label>Category</label>
                    <select name="category_id" id="edit_category_id" required>
                        <?php
                        $categories = $conn->query("SELECT * FROM categories_table WHERE is_active = 1");
                        while($category = $categories->fetch_assoc()) {
                            echo "<option value='".$category['category_id']."'>".htmlspecialchars($category['name'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory Name</label>
                    <input type="text" name="name" id="edit_subcategory_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_subcategory_description" required></textarea>
                </div>
                <button type="submit" class="add-btn">Update Subcategory</button>
            </form>
        </div>
    </div>

    <script>
        function showAddSubcategoryModal() {
            document.getElementById('addSubcategoryModal').style.display = 'block';
        }

        function editSubcategory(id, categoryId, name, description) {
            document.getElementById('edit_subcategory_id').value = id;
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_subcategory_name').value = decodeHTMLEntities(name);
            document.getElementById('edit_subcategory_description').value = decodeHTMLEntities(description);
            document.getElementById('editSubcategoryModal').style.display = 'block';
        }

        function deleteSubcategory(id) {
            if(confirm('Are you sure you want to delete this subcategory?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_subcategory">
                    <input type="hidden" name="subcategory_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function decodeHTMLEntities(text) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>