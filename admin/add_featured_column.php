<?php
require_once '../config/database.php';

try {
    // Add is_featured column
    $conn->exec("ALTER TABLE Menu ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
    echo "Successfully added is_featured column to Menu table.<br>";
    
    // Optional: Set some items as featured
    $conn->exec("UPDATE Menu SET is_featured = 1 WHERE menu_id IN (1, 2, 3)");
    echo "Successfully set some items as featured.<br>";
    
    echo "You can now use the featured products functionality!";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'is_featured' already exists in Menu table.<br>";
        echo "You can now use the featured products functionality!";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?> 