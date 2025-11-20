<?php
require_once '../config/database.php';

try {
    // Start transaction
    $conn->beginTransaction();

    // First delete order items that reference menu items in test categories
    $stmt = $conn->prepare("DELETE FROM orderitems WHERE menu_id IN 
        (SELECT menu_id FROM menu WHERE category_id IN 
            (SELECT category_id FROM categories WHERE name LIKE '%test%'))");
    $stmt->execute();

    // Then delete menu items linked to test categories
    $stmt = $conn->prepare("DELETE FROM menu WHERE category_id IN 
        (SELECT category_id FROM categories WHERE name LIKE '%test%')");
    $stmt->execute();
    $menuCount = $stmt->rowCount();
    
    // Finally delete the test categories
    $stmt = $conn->prepare("DELETE FROM categories WHERE name LIKE '%test%'");
    $stmt->execute();
    $categoryCount = $stmt->rowCount();
    
    // Commit the transaction
    $conn->commit();
    
    echo "Successfully deleted " . $categoryCount . " test categories and " . $menuCount . " menu items.";
    
} catch(PDOException $e) {
    // Rollback the transaction if something failed
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
