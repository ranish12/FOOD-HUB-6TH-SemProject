<?php
session_start();
require_once '../../config/database.php';

// Get category and search parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT m.*, c.name as category_name, m.stock_quantity 
          FROM Menu m 
          LEFT JOIN Categories c ON m.category_id = c.category_id 
          WHERE m.is_deleted = FALSE";
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

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($items);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 