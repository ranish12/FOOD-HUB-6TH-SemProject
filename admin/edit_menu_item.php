<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = $_POST['menu_id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    try {
        // Get current image URL
        $stmt = $conn->prepare("SELECT image_url FROM Menu WHERE menu_id = ?");
        $stmt->execute([$menu_id]);
        $current_image = $stmt->fetchColumn();
        
        // Handle image upload if new image is provided
        $image_url = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/menu/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Get file extension
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            // Check if file is an image
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                header('Location: dashboard.php?error=invalid_image');
                exit();
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if ($current_image && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                $image_url = 'assets/images/menu/' . $new_filename;
            } else {
                error_log("Failed to move uploaded file. Upload path: " . $upload_path);
                header('Location: dashboard.php?error=upload_failed');
                exit();
            }
        }
        
        // Update menu item
        $stmt = $conn->prepare("
            UPDATE Menu 
            SET name = ?, 
                category_id = ?, 
                price = ?, 
                description = ?, 
                image_url = ?, 
                is_available = ?, 
                is_featured = ? 
            WHERE menu_id = ?
        ");
        
        if ($stmt->execute([$name, $category_id, $price, $description, $image_url, $is_available, $is_featured, $menu_id])) {
            header('Location: dashboard.php?success=menu_updated');
        } else {
            header('Location: dashboard.php?error=update_failed');
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header('Location: dashboard.php?error=database_error');
    }
} else {
    header('Location: dashboard.php');
}
exit(); 