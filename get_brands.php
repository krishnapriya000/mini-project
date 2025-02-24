<?php
// Include database connection
require_once 'db.php';

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parameters from request
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 0;
$subcategory_id = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : NULL;

// Prepare query based on parameters
if ($subcategory_id) {
    // If subcategory is provided, get brands for specific category and subcategory
    $stmt = $conn->prepare("SELECT brand_id, brand_name FROM brand_table WHERE category_id = ? AND subcategory_id = ? ORDER BY brand_name");
    $stmt->bind_param("ii", $category_id, $subcategory_id);
} else {
    // If only category is provided, get all brands for that category
    $stmt = $conn->prepare("SELECT DISTINCT brand_id, brand_name FROM brand_table WHERE category_id = ? ORDER BY brand_name");
    $stmt->bind_param("i", $category_id);
}

// Execute query
$stmt->execute();
$result = $stmt->get_result();

// Fetch data and output as JSON
$brands = [];
while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($brands);

// Close connection
$stmt->close();
$conn->close();
?>