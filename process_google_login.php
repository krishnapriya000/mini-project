<?php
session_start();
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

// Extract user data
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';
$uid = $data['uid'] ?? '';
$photo_url = $data['photoURL'] ?? '';

if (empty($email) || empty($uid)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Generate a username from the email (before the @ symbol)
$username_parts = explode('@', $email);
$base_username = $username_parts[0];
$username = $base_username;

// Check if user exists in the database
$user_check_sql = "SELECT * FROM signup WHERE (email = ? OR google_uid = ?) LIMIT 1";
$stmt = $conn->prepare($user_check_sql);
$stmt->bind_param("ss", $email, $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists, update their Google UID if needed and log them in
    $user = $result->fetch_assoc();
    
    // Update Google UID if it's not set
    if (empty($user['google_uid'])) {
        $update_sql = "UPDATE signup SET google_uid = ? WHERE signupid = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $uid, $user['signupid']);
        $update_stmt->execute();
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Your account is ' . $user['status'] . '. Please contact support.']);
        exit;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['signupid'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['reg_type'] = $user['reg_type'];
    
    // For sellers, also store seller_id
    if ($user['reg_type'] === 'seller') {
        $seller_sql = "SELECT seller_id FROM seller WHERE signupid = ?";
        $seller_stmt = $conn->prepare($seller_sql);
        $seller_stmt->bind_param("i", $user['signupid']);
        $seller_stmt->execute();
        $seller_result = $seller_stmt->get_result();
        
        if ($seller_result->num_rows > 0) {
            $seller_row = $seller_result->fetch_assoc();
            $_SESSION['seller_id'] = $seller_row['seller_id'];
        }
    }
    
    // Insert login record
    $login_sql = "INSERT INTO login (signup_id, login_date) VALUES (?, CURDATE())";
    $login_stmt = $conn->prepare($login_sql);
    $login_stmt->bind_param("i", $user['signupid']);
    $login_stmt->execute();
    
    // Determine redirect location
    $redirect = 'index.php';
    switch ($user['reg_type']) {
        case 'admin':
            $redirect = 'admindashboard.php';
            break;
        case 'seller':
            $redirect = 'sellerdashboard.php';
            break;
    }
    
    echo json_encode(['success' => true, 'redirect' => $redirect]);
    exit;
} else {
    // User doesn't exist, create a new account
    
    // Check if username already exists, if so, append numbers until unique
    $username_check_sql = "SELECT username FROM signup WHERE username = ?";
    $username_check_stmt = $conn->prepare($username_check_sql);
    $username_counter = 0;
    
    do {
        $current_username = $username;
        if ($username_counter > 0) {
            $current_username = $username . $username_counter;
        }
        
        $username_check_stmt->bind_param("s", $current_username);
        $username_check_stmt->execute();
        $username_result = $username_check_stmt->get_result();
        
        if ($username_result->num_rows === 0) {
            // Username is unique
            $username = $current_username;
            break;
        }
        
        $username_counter++;
    } while ($username_counter < 100); // Prevent infinite loop
    
    // Generate a random password (user won't need this since they're using Google login)
    $random_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
    
    // Add user to database
    $insert_sql = "INSERT INTO signup (username, email, password, google_uid, reg_type, status, created_date) 
                   VALUES (?, ?, ?, ?, 'user', 'active', CURDATE())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $uid);
    
    if ($insert_stmt->execute()) {
        $new_user_id = $conn->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['reg_type'] = 'user';
        
        // Insert login record
        $login_sql = "INSERT INTO login (signup_id, login_date) VALUES (?, CURDATE())";
        $login_stmt = $conn->prepare($login_sql);
        $login_stmt->bind_param("i", $new_user_id);
        $login_stmt->execute();
        
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $conn->error]);
    }
}

$conn->close();