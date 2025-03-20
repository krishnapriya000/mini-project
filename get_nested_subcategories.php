<?php
// Include database connection
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

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
$query = "SELECT 
    n.*, 
    c.name as category_name,
    s.subcategory_name,
    b.brand_name,
    n.nested_subcategory_name,
    n.description,
    n.id,
    c.category_id,
    s.id as parent_subcategory_id,
    b.brand_id
FROM nested_subcategories n
JOIN subcategories s ON n.parent_subcategory_id = s.id
JOIN categories_table c ON s.category_id = c.category_id
JOIN brand_table b ON n.brand_id = b.brand_id
WHERE n.is_active = 1";

if ($category_id) {
    $query .= " AND c.category_id = " . intval($category_id);
}
if ($subcategory_id) {
    $query .= " AND s.id = " . intval($subcategory_id);
}
if ($brand_id) {
    $query .= " AND b.brand_id = " . intval($brand_id);
}

$result = $conn->query($query);
$nested_subcategories = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Escape special characters for JavaScript
        $row['nested_subcategory_name'] = htmlspecialchars($row['nested_subcategory_name'], ENT_QUOTES);
        $row['description'] = htmlspecialchars($row['description'], ENT_QUOTES);
        $nested_subcategories[] = $row;
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($nested_subcategories);

// Close connection
$conn->close();
?>