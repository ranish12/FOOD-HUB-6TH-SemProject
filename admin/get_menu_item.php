<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Menu ID is required']);
    exit();
}

$menu_id = $_GET['id'];

// Get menu item details
$stmt = $conn->prepare("SELECT * FROM Menu WHERE menu_id = ?");
$stmt->execute([$menu_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Menu item not found']);
    exit();
}

// Return menu item details as JSON
header('Content-Type: application/json');
echo json_encode($item); 