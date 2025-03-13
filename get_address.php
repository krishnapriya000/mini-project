<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['address_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$address_id = (int)$_GET['address_id'];
$user_id = $_SESSION['user_id'];

// Fetch address details
$stmt = $conn->prepare("
    SELECT * FROM shipping_addresses 
    WHERE address_id = ? AND signupid = (
        SELECT signupid FROM user_table WHERE user_id = ?
    )
");
$stmt->bind_param("ii", $address_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $address = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'address_line1' => $address['address_line1'],
        'city' => $address['city'],
        'state' => $address['state'],
        'postal_code' => $address['postal_code']
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>