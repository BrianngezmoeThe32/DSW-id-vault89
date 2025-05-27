<?php
require 'Database.php';

$createTables = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        salt VARCHAR(64) NOT NULL,
        verification_token VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

try {
    $db = Database::getConnection();
    foreach ($createTables as $sql) {
        $db->exec($sql);
    }
    echo "Tables created successfully!";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}