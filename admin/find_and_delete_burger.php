<?php
require_once __DIR__ . '/../config/database.php';

try {
    // First, find the burger
    $stmt = $conn->query("SELECT menu_id, name, category_name FROM Menu m LEFT JOIN Categories c ON m.category_id = c.category_id WHERE m.is_deleted = FALSE AND m.name LIKE '%burger%'");
    $burgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($burgers)) {
        echo "No burgers found in the menu.\n";
        exit;
    }
    
    echo "Found the following burgers:\n";
    foreach ($burgers as $burger) {
        echo "ID: {$burger['menu_id']} - {$burger['name']} ({$burger['category_name']})\n";
    }
    
    // Delete the first burger found
    $burger_id = $burgers[0]['menu_id'];
    $stmt = $conn->prepare("UPDATE Menu SET is_deleted = TRUE WHERE menu_id = ?");
    if ($stmt->execute([$burger_id])) {
        echo "\nSuccessfully deleted burger with ID: $burger_id\n";
    } else {
        echo "\nFailed to delete burger\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?> 