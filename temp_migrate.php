<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/db.php';

try {
    db()->query("ALTER TABLE users ADD COLUMN is_password_set TINYINT(1) DEFAULT 1 AFTER is_verified");
    db()->query("UPDATE users SET is_password_set = 0 WHERE role = 'driver'");
    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
