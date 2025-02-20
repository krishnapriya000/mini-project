<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/baby/connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Mark user as deleted in signup table
        $sql = "UPDATE signup SET deleted = 1 WHERE signupid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Mark user as deleted in user_table
        $sql = "UPDATE user_table SET deleted = 1 WHERE signupid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Destroy the session and redirect to login page
        session_destroy();
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting account: " . $e->getMessage());
        header("Location: profile.php?error=delete_failed");
        exit();
    }
}
?>