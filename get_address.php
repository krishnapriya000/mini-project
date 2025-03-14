<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Include the database connection file
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Check database connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : 0;

// Security check - ensure the address belongs to the logged-in user
$query = "SELECT * FROM shipping_addresses WHERE address_id = ? AND signupid = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
    exit();
}

$stmt->bind_param("ii", $address_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Address not found']);
    exit();
}

$address = $result->fetch_assoc();
echo json_encode(['status' => 'success', 'address' => $address]);
?>