<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");
    // For existing users, let's set them to verified so they aren't locked out
    $pdo->exec("UPDATE users SET is_verified = 1");
    echo "Database altered successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
