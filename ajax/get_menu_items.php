<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get category and search parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT m.*, c.name as category_name 
          FROM Menu m 
          LEFT JOIN Categories c ON m.category_id = c.category_id 
          WHERE m.is_available = 1";
$params = [];

if ($category !== 'all') {
    $query .= " AND m.category_id = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (m.name LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY m.name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($items);
?> 