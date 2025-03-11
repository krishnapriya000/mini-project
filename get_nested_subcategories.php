<?php
// Include database connection
require_once 'db.php';

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get subcategory_id from request
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$subcategory_id = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : null;
$brand_id = isset($_GET['brand_id']) ? $_GET['brand_id'] : null;

// Prepare and execute query
$query = "SELECT n.*, c.name as category_name, s.subcategory_name, b.brand_name 
          FROM nested_subcategories n
          JOIN subcategories s ON n.parent_subcategory_id = s.id
          JOIN categories_table c ON s.category_id = c.category_id
          JOIN brand_table b ON n.brand_id = b.brand_id
          WHERE n.is_active = 1";

if ($category_id) {
    $query .= " AND s.category_id = " . $conn->real_escape_string($category_id);
}

if ($subcategory_id) {
    $query .= " AND n.parent_subcategory_id = " . $conn->real_escape_string($subcategory_id);
}

if ($brand_id) {
    $query .= " AND n.brand_id = " . $conn->real_escape_string($brand_id);
}

$result = $conn->query($query);
$nested = [];

while ($row = $result->fetch_assoc()) {
    $nested[] = $row;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($nested);

// Close connection
$conn->close();
?>