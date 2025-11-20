<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $menu_id = $_POST['id'];
    
    try {
        // Get image URL before deleting
        $stmt = $conn->prepare("SELECT image_url FROM Menu WHERE menu_id = ?");
        $stmt->execute([$menu_id]);
        $image_url = $stmt->fetchColumn();
        
        // Delete menu item
        $stmt = $conn->prepare("DELETE FROM Menu WHERE menu_id = ?");
        $stmt->execute([$menu_id]);
        
        // Delete image file if exists
        if ($image_url && file_exists('../' . $image_url)) {
            unlink('../' . $image_url);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
} 