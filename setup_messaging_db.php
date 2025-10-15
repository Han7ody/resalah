<?php
// setup_messaging_db.php

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Connected to the database successfully.<br>";

    // SQL to create friendships table
    $sql_friendships = "
    CREATE TABLE IF NOT EXISTS `friendships` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_one_id` INT NOT NULL,
      `user_two_id` INT NOT NULL,
      `status` TINYINT NOT NULL COMMENT '0=pending, 1=accepted, 2=declined, 3=blocked',
      `action_user_id` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_friendship` (`user_one_id`, `user_two_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // SQL to create messages table
    $sql_messages = "
    CREATE TABLE IF NOT EXISTS `messages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `sender_id` INT NOT NULL,
      `receiver_id` INT NOT NULL,
      `message` TEXT NOT NULL,
      `is_read` BOOLEAN DEFAULT FALSE,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->exec($sql_friendships);
    echo "Table 'friendships' created successfully (if it didn't exist).<br>";

    $db->exec($sql_messages);
    echo "Table 'messages' created successfully (if it didn't exist).<br>";

    echo "Database setup for messaging is complete.<br>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
