<?php
require_once 'config/database.php';
$pdo->exec("DELETE FROM users WHERE is_verified = 0");
echo "Unverified users deleted.";
