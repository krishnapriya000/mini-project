<?php
// Include database connection
require_once 'db.php';

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get category_id from request
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 0;

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, subcategory_name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY subcategory_name");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch data and output as JSON
$subcategories = [];
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($subcategories);

// Close connection
$stmt->close();
$conn->close();
?>