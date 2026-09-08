<?php
require_once __DIR__ . '/config/database.php';

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE '$column'");
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

try {
    if (!columnExists($pdo, 'users', 'email')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL AFTER username");
    }
    
    if (columnExists($pdo, 'users', 'nik')) {
        $pdo->exec("ALTER TABLE users DROP COLUMN nik");
    }

    if (!columnExists($pdo, 'users', 'reset_token')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
    }
    
    if (!columnExists($pdo, 'users', 'reset_expires')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
    }

    try {
        $pdo->exec("ALTER TABLE reports MODIFY COLUMN status ENUM('Menunggu', 'Diproses', 'Disetujui', 'Ditolak') DEFAULT 'Diproses'");
        $pdo->exec("UPDATE reports SET status = 'Diproses' WHERE status = 'Menunggu'");
        $pdo->exec("ALTER TABLE reports MODIFY COLUMN status ENUM('Diproses', 'Disetujui', 'Ditolak') DEFAULT 'Diproses'");
    } catch(Exception $e) {}

    if (!columnExists($pdo, 'reports', 'is_read')) {
        $pdo->exec("ALTER TABLE reports ADD COLUMN is_read TINYINT(1) DEFAULT 0");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
