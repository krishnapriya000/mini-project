<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];
    
    $stmt = $conn->prepare("SELECT id, subcategory_name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY subcategory_name");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subcategories = array();
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($subcategories);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'Category ID not provided'));
}
?>