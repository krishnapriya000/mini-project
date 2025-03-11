<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_nested_subcategory':
                $subcategory_id = $_POST['subcategory_id'];
                $brand_id = $_POST['brand_id'];
                $name = $_POST['nested_subcategory_name'];
                $description = $_POST['description'];
                
                $stmt = $conn->prepare("INSERT INTO nested_subcategories (parent_subcategory_id, brand_id, nested_subcategory_name, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $subcategory_id, $brand_id, $name, $description);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory added successfully";
                } else {
                    $_SESSION['error'] = "Error adding nested subcategory: " . $stmt->error;
                }
                break;

            case 'delete_nested_subcategory':
                $nested_id = $_POST['nested_id'];
                $stmt = $conn->prepare("UPDATE nested_subcategories SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $nested_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting nested subcategory: " . $stmt->error;
                }
                break;

            case 'edit_nested_subcategory':
                $nested_id = $_POST['nested_id'];
                $subcategory_id = $_POST['subcategory_id'];
                $brand_id = $_POST['brand_id'];
                $name = $_POST['nested_subcategory_name'];
                $description = $_POST['description'];
                
                $stmt = $conn->prepare("UPDATE nested_subcategories SET parent_subcategory_id = ?, brand_id = ?, nested_subcategory_name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("iissi", $subcategory_id, $brand_id, $name, $description, $nested_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Nested subcategory updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating nested subcategory: " . $stmt->error;
                }
                break;

            case 'edit_brand':
                $brand_id = $_POST['brand_id'];
                $category_id = $_POST['category_id'];
                $subcategory_id = $_POST['subcategory_id'];
                $brand_name = $_POST['brand_name'];
                
                $stmt = $conn->prepare("UPDATE brand_table SET category_id = ?, subcategory_id = ?, brand_name = ? WHERE brand_id = ?");
                $stmt->bind_param("iisi", $category_id, $subcategory_id, $brand_name, $brand_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating brand: " . $stmt->error;
                }
                break;
        }
    }
}

// Fetch categories for initial dropdown
$categories_query = "SELECT * FROM categories_table WHERE is_active = 1";
$categories_result = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nested Subcategories Management</title>
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
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #3d3f54 #2b2d42;
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
            background-color: #3d3f54;
        }

        .nav-item.active {
            background-color: #3d3f54;
            border-left: 4px solid #fff;
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
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h2 {
            color: #2b2d42;
            font-size: 24px;
        }

        /* Filter Section Styles */
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-section select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-section select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }

        /* Button Styles */
        .add-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .add-btn:hover {
            background-color: #45a049;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #2b2d42;
            color: white;
            font-weight: 500;
        }

        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Action Button Styles */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #2196F3;
            color: white;
            margin-right: 5px;
        }

        .edit-btn:hover {
            background-color: #1976D2;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
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
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }

        /* Message Styles */
        .success-message,
        .error-message {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error-message {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* Responsive Styles */
        @media screen and (max-width: 1024px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-section select {
                width: 100%;
            }

            .modal-content {
                width: 70%;
            }
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }

            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .data-table {
                font-size: 14px;
            }

            .modal-content {
                width: 90%;
                margin: 10% auto;
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

            .action-btn {
                padding: 4px 8px;
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 8px;
            }
        }

        /* Loading Indicator */
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1001;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ddd;
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
            <li class="nav-item">
                <a href="subcategories.php"><i class="fas fa-sitemap"></i> Subcategories</a>
            </li>
            <li class="nav-item">
                <a href="brand.php"><i class="fas fa-tag"></i> Brands</a>
            </li>
            <li class="nav-item active">
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
            <h2>Nested Subcategories</h2>
            <button class="add-btn" onclick="showAddNestedModal()">Add Nested Subcategory</button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <select id="categoryFilter" onchange="loadSubcategories(this.value)">
                <option value="">Select Category</option>
                <?php while($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select id="subcategoryFilter" onchange="loadBrands(this.value)">
                <option value="">Select Subcategory</option>
            </select>

            <select id="brandFilter" onchange="loadNestedSubcategories()">
                <option value="">Select Brand</option>
            </select>
        </div>

        <!-- Messages -->
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

        <!-- Nested Subcategories Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Brand</th>
                    <th>Nested Subcategory</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="nestedTableBody">
                <!-- Will be populated via JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Add Nested Subcategory Modal -->
    <div id="addNestedModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addNestedModal')">&times;</span>
            <h2>Add New Nested Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_nested_subcategory">
                
                <div class="form-group">
                    <label>Category</label>
                    <select id="modalCategorySelect" onchange="loadModalSubcategories(this.value)" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categories_result->data_seek(0);
                        while($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subcategory</label>
                    <select name="subcategory_id" id="modalSubcategorySelect" onchange="loadModalBrands(this.value)" required>
                        <option value="">Select Subcategory</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand_id" id="modalBrandSelect" required>
                        <option value="">Select Brand</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nested Subcategory Name</label>
                    <input type="text" name="nested_subcategory_name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>

                <button type="submit" class="add-btn">Add Nested Subcategory</button>
            </form>
        </div>
    </div>

    <!-- Edit Nested Subcategory Modal -->
    <div id="editNestedModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editNestedModal')">&times;</span>
            <h2>Edit Nested Subcategory</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_nested_subcategory">
                <input type="hidden" name="nested_id" id="edit_nested_id">
                
                <div class="form-group">
                    <label>Category</label>
                    <select id="editModalCategorySelect" onchange="loadModalSubcategories(this.value, 'edit')" required>
                        <?php 
                        $categories_result->data_seek(0);
                        while($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subcategory</label>
                    <select name="subcategory_id" id="editModalSubcategorySelect" onchange="loadModalBrands(this.value, 'edit')" required>
                        <option value="">Select Subcategory</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand_id" id="editModalBrandSelect" required>
                        <option value="">Select Brand</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nested Subcategory Name</label>
                    <input type="text" name="nested_subcategory_name" id="edit_nested_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_nested_description" required></textarea>
                </div>
                
                <button type="submit" class="add-btn">Update Nested Subcategory</button>
            </form>
        </div>
    </div>

    <script>
        function showAddNestedModal() {
            document.getElementById('addNestedModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function loadSubcategories(categoryId, targetId = 'subcategoryFilter') {
            if (!categoryId) {
                document.getElementById(targetId).innerHTML = '<option value="">Select Subcategory</option>';
                return;
            }

            fetch(`get_subcategories.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById(targetId);
                    select.innerHTML = '<option value="">Select Subcategory</option>';
                    data.forEach(subcategory => {
                        select.innerHTML += `<option value="${subcategory.id}">${subcategory.subcategory_name}</option>`;
                    });
                });
        }

        function loadBrands(subcategoryId, targetId = 'brandFilter') {
            if (!subcategoryId) {
                document.getElementById(targetId).innerHTML = '<option value="">Select Brand</option>';
                return;
            }

            const categoryId = document.getElementById('categoryFilter').value;
            fetch(`get_brands.php?category_id=${categoryId}&subcategory_id=${subcategoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById(targetId);
                    select.innerHTML = '<option value="">Select Brand</option>';
                    data.forEach(brand => {
                        select.innerHTML += `<option value="${brand.brand_id}">${brand.brand_name}</option>`;
                    });
                });
        }

        function loadNestedSubcategories() {
            const categoryId = document.getElementById('categoryFilter').value;
            const subcategoryId = document.getElementById('subcategoryFilter').value;
            const brandId = document.getElementById('brandFilter').value;

            if (!categoryId || !subcategoryId) return;

            fetch(`get_nested_subcategories.php?category_id=${categoryId}&subcategory_id=${subcategoryId}&brand_id=${brandId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('nestedTableBody');
                    tbody.innerHTML = '';
                    data.forEach(nested => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${nested.category_name}</td>
                                <td>${nested.subcategory_name}</td>
                                <td>${nested.brand_name}</td>
                                <td>${nested.nested_subcategory_name}</td>
                                <td>${nested.description}</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editNested(
                                        ${nested.id},
                                        ${nested.category_id},
                                        ${nested.parent_subcategory_id},
                                        ${nested.brand_id},
                                        '${nested.nested_subcategory_name}',
                                        '${nested.description}'
                                    )">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteNested(${nested.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function loadModalSubcategories(categoryId, mode = 'add') {
            const selectId = mode === 'edit' ? 'editModalSubcategorySelect' : 'modalSubcategorySelect';
            if (!categoryId) {
                document.getElementById(selectId).innerHTML = '<option value="">Select Subcategory</option>';
                return;
            }

            try {
                const response = await fetch(`get_subcategories.php?category_id=${categoryId}`);
                const data = await response.json();
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Select Subcategory</option>';
                data.forEach(subcategory => {
                    select.innerHTML += `<option value="${subcategory.id}">${subcategory.subcategory_name}</option>`;
                });
            } catch (error) {
                console.error('Error loading subcategories:', error);
            }
        }

        async function loadModalBrands(subcategoryId, mode = 'add') {
            const selectId = mode === 'edit' ? 'editModalBrandSelect' : 'modalBrandSelect';
            if (!subcategoryId) {
                document.getElementById(selectId).innerHTML = '<option value="">Select Brand</option>';
                return;
            }

            const categoryId = document.getElementById(mode === 'edit' ? 'editModalCategorySelect' : 'modalCategorySelect').value;
            try {
                const response = await fetch(`get_brands.php?category_id=${categoryId}&subcategory_id=${subcategoryId}`);
                const data = await response.json();
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Select Brand</option>';
                data.forEach(brand => {
                    select.innerHTML += `<option value="${brand.brand_id}">${brand.brand_name}</option>`;
                });
            } catch (error) {
                console.error('Error loading brands:', error);
            }
        }

        function editNested(nestedId, categoryId, subcategoryId, brandId, name, description) {
            document.getElementById('edit_nested_id').value = nestedId;
            document.getElementById('editModalCategorySelect').value = categoryId;
            document.getElementById('edit_nested_name').value = name;
            document.getElementById('edit_nested_description').value = description;
            
            // Load subcategories and brands, then set the selected values
            loadModalSubcategories(categoryId, 'edit').then(() => {
                document.getElementById('editModalSubcategorySelect').value = subcategoryId;
                return loadModalBrands(subcategoryId, 'edit');
            }).then(() => {
                document.getElementById('editModalBrandSelect').value = brandId;
            });
            
            document.getElementById('editNestedModal').style.display = 'block';
        }

        function deleteNested(nestedId) {
            if(confirm('Are you sure you want to delete this nested subcategory?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `