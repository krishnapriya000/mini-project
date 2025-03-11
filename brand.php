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
            case 'add_brand':
                $category_id = $_POST['category_id'];
                $subcategory_id = $_POST['subcategory_id'];
                $brand_name = $_POST['brand_name'];
                
                $stmt = $conn->prepare("INSERT INTO brand_table (category_id, subcategory_id, brand_name) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $category_id, $subcategory_id, $brand_name);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand added successfully";
                } else {
                    $_SESSION['error'] = "Error adding brand: " . $stmt->error;
                }
                break;

            case 'delete_brand':
                $brand_id = $_POST['brand_id'];
                $stmt = $conn->prepare("UPDATE brand_table SET is_active = 0 WHERE brand_id = ?");
                $stmt->bind_param("i", $brand_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Brand deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting brand: " . $stmt->error;
                }
                break;
        }
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories_table WHERE is_active = 1";
$categories_result = $conn->query($categories_query);

// Get selected category and subcategory from filter
$selected_category = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$selected_subcategory = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Management</title>
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

        .nav-item i {
            width: 20px;
            text-align: center;
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
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
            position: relative;
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
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
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
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-section select {
                width: 100%;
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

            .header {
                flex-direction: column;
                gap: 10px;
            }

            .data-table {
                font-size: 13px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 12px;
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
            <li class="nav-item">
                <a href="subcategories.php"><i class="fas fa-sitemap"></i> Subcategories</a>
            </li>
            <li class="nav-item active">
                <a href="brand.php"><i class="fas fa-tag"></i> Brands</a>
            </li>
            <li class="nav-item">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Brands</h2>
            <button class="add-btn" onclick="showAddBrandModal()">Add Brand</button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <select id="categoryFilter" onchange="loadSubcategories(this.value)">
                <option value="">Select Category</option>
                <?php while($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['category_id']; ?>"
                            <?php echo ($selected_category == $category['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select id="subcategoryFilter" onchange="filterBrands()">
                <option value="">Select Subcategory</option>
                <!-- Will be populated via JavaScript -->
            </select>
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
                    <th>Subcategory</th>
                    <th>Brand Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="brandTableBody">
                <!-- Will be populated via JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Add Brand Modal -->
    <div id="addBrandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addBrandModal')">&times;</span>
            <h2>Add New Brand</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_brand">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="modalCategorySelect" required onchange="loadModalSubcategories(this.value)">
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
                    <select name="subcategory_id" id="modalSubcategorySelect" required>
                        <option value="">Select Subcategory</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" required>
                </div>
                <button type="submit" class="add-btn">Add Brand</button>
            </form>
        </div>
    </div>

    <script>
        function showAddBrandModal() {
            document.getElementById('addBrandModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function loadSubcategories(categoryId) {
            if (!categoryId) {
                document.getElementById('subcategoryFilter').innerHTML = '<option value="">Select Subcategory</option>';
                return;
            }

            fetch(`get_subcategories.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('subcategoryFilter');
                    select.innerHTML = '<option value="">Select Subcategory</option>';
                    data.forEach(subcategory => {
                        select.innerHTML += `<option value="${subcategory.id}">${subcategory.subcategory_name}</option>`;
                    });
                });
        }

        function loadModalSubcategories(categoryId) {
            if (!categoryId) {
                document.getElementById('modalSubcategorySelect').innerHTML = '<option value="">Select Subcategory</option>';
                return;
            }

            fetch(`get_subcategories.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('modalSubcategorySelect');
                    select.innerHTML = '<option value="">Select Subcategory</option>';
                    data.forEach(subcategory => {
                        select.innerHTML += `<option value="${subcategory.id}">${subcategory.subcategory_name}</option>`;
                    });
                });
        }

        function filterBrands() {
            const categoryId = document.getElementById('categoryFilter').value;
            const subcategoryId = document.getElementById('subcategoryFilter').value;

            if (!categoryId) return;

            fetch(`get_brands.php?category_id=${categoryId}&subcategory_id=${subcategoryId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('brandTableBody');
                    tbody.innerHTML = '';
                    data.forEach(brand => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${brand.category_name}</td>
                                <td>${brand.subcategory_name}</td>
                                <td>${brand.brand_name}</td>
                                <td>
                                    <button class="action-btn delete-btn" onclick="deleteBrand(${brand.brand_id})">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function deleteBrand(brandId) {
            if(confirm('Are you sure you want to delete this brand?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_brand">
                    <input type="hidden" name="brand_id" value="${brandId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Load subcategories if category is selected
        if (document.getElementById('categoryFilter').value) {
            loadSubcategories(document.getElementById('categoryFilter').value);
        }
    </script>
</body>
</html>
