<?php
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $conn->query("SELECT menu_id, name, category_name, is_deleted FROM Menu m LEFT JOIN Categories c ON m.category_id = c.category_id ORDER BY m.name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "No menu items found.\n";
        exit;
    }
    
    echo "Menu Items:\n";
    echo "----------------------------------------\n";
    foreach ($items as $item) {
        $status = $item['is_deleted'] ? 'DELETED' : 'ACTIVE';
        echo "ID: {$item['menu_id']} - {$item['name']} ({$item['category_name']}) - {$status}\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?> 