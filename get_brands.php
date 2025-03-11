<?php
// Include database connection
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Get parameters from request
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$subcategory_id = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : null;

$query = "SELECT b.*, c.name as category_name, s.subcategory_name 
          FROM brand_table b
          JOIN categories_table c ON b.category_id = c.category_id
          JOIN subcategories s ON b.subcategory_id = s.id
          WHERE b.is_active = 1";

if ($category_id) {
    $query .= " AND b.category_id = " . $conn->real_escape_string($category_id);
}

if ($subcategory_id) {
    $query .= " AND b.subcategory_id = " . $conn->real_escape_string($subcategory_id);
}

$result = $conn->query($query);
$brands = [];

while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($brands);
?>