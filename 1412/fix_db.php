<?php
require_once 'db.php';

try {
    echo "<h2>Updating Database Schema...</h2>";

    // 1. Add 'name' column if not exists
    try {
        $pdo->exec("ALTER TABLE admin ADD COLUMN name VARCHAR(255) DEFAULT 'Admin'");
        echo "✅ Added column 'name'<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false || strpos($e->getMessage(), "exists") !== false) {
            echo "ℹ️ Column 'name' already exists<br>";
        } else {
            echo "❌ Error adding 'name': " . $e->getMessage() . "<br>";
        }
    }

    // 2. Add 'avatar' column if not exists
    try {
        $pdo->exec("ALTER TABLE admin ADD COLUMN avatar LONGBLOB");
        echo "✅ Added column 'avatar'<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false || strpos($e->getMessage(), "exists") !== false) {
            echo "ℹ️ Column 'avatar' already exists<br>";
        } else {
            echo "❌ Error adding 'avatar': " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>🎉 Database Updated Successfully!</h3>";
    echo "<a href='admin_users.php'>Go back to Admin Users</a>";

} catch (Exception $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
?>