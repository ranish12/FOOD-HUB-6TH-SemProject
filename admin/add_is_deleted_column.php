<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Add is_deleted column to Menu table if it doesn't exist
    $sql = "ALTER TABLE Menu ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE";
    $conn->exec($sql);
    echo "Successfully added is_deleted column to Menu table";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?> 