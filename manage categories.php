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
            case 'add_category':
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
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
                    $stmt = $conn->prepare("INSERT INTO categories_table (name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $description);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Category added successfully";
                    } else {
                        $_SESSION['error'] = "Error adding category: " . $stmt->error;
                    }
                }
                break;

            case 'update_category':
                $category_id = $_POST['category_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
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

            case 'soft_delete_category':
                $category_id = $_POST['category_id'];
                $stmt = $conn->prepare("UPDATE categories_table SET is_active = 0 WHERE category_id = ?");
                $stmt->bind_param("i", $category_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Category deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting category: " . $stmt->error;
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            background-color: rgb(248, 191, 125);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: rgb(179, 69, 10);
            color: white;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgb(240, 225, 163) rgb(240, 225, 163);
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
            transition: all 0.3s ease;
        }

        .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover {
            background-color: rgb(241, 170, 103);
        }

        .nav-item.active {
            background-color:rgb(241, 170, 103);
            border-left: 4px solid #fff;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        .nav-item a i {
            margin-right: 10px;
        }

        /* Add this if you want to ensure proper spacing between icon and text */
        .fa-layer-group {
            font-size: 0.9em; /* Slightly smaller icon for better alignment */
        }

        /* Webkit scrollbar styles */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgb(241, 170, 103);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgb(241, 170, 103);
            border-radius: 3px;
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
            background-color: rgb(179, 69, 10);
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
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-name">BabyCubs</div>
        <ul class="nav-items">
            <li class="nav-item active">
                <a href="dd.php"><i class="fas fa-list"></i> Categories</a>
            </li>
            <li class="nav-item">
                <a href="subcategories.php"><i class="fas fa-sitemap"></i> Subcategories</a>
            </li>
            <li class="nav-item">
                <a href="brand.php"><i class="fas fa-tag"></i> Brands</a>
            </li>
            <li class="nav-item">
                <a href="nested_subcategories.php"><i class="fas fa-layer-group"></i> Nested Subcategories</a>
            </li>
            <li class="nav-item">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Categories</h2>
            <button class="add-btn" onclick="showAddCategoryModal()">Add Category</button>
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
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM categories_table WHERE is_active = 1";
                $result = $conn->query($query);
                
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>".htmlspecialchars($row['name'])."</td>";
                    echo "<td>".htmlspecialchars($row['description'])."</td>";
                    echo "<td>
                            <button class='action-btn edit-btn' onclick='editCategory(".
                            $row['category_id'].", \"".
                            addslashes(htmlspecialchars($row['name']))."\", \"".
                            addslashes(htmlspecialchars($row['description'])).
                            "\")'>Edit</button>
                            <button class='action-btn delete-btn' onclick='deleteCategory(".$row['category_id'].")'>Delete</button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
            <h2>Add New Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <button type="submit" class="add-btn">Add Category</button>
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
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" id="edit_category_name" required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_category_description" required></textarea>
                </div>
                <button type="submit" class="add-btn">Update Category</button>
            </form>
        </div>
    </div>

    <script>
        function showAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'block';
        }

        function editCategory(id, name, description) {
            const decodedName = decodeHTMLEntities(name);
            const decodedDescription = decodeHTMLEntities(description);
            
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = decodedName;
            document.getElementById('edit_category_description').value = decodedDescription;
            
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function decodeHTMLEntities(text) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        }

        function deleteCategory(id) {
            if(confirm('Are you sure you want to delete this category?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="soft_delete_category">
                    <input type="hidden" name="category_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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