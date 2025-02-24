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
$subcategory_id = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : 0;

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, nested_subcategory_name FROM nested_subcategories WHERE parent_subcategory_id = ? AND is_active = 1 ORDER BY nested_subcategory_name");
$stmt->bind_param("i", $subcategory_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch data and output as JSON
$nested_subcategories = [];
while ($row = $result->fetch_assoc()) {
    $nested_subcategories[] = $row;
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($nested_subcategories);

// Close connection
$stmt->close();
$conn->close();
?>