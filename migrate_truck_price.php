<?php
define('DEBUG_MODE', true);
require_once 'h:/Drive\'ım/AI_Projelerim/ucuzmazot.com/sunucuya_gidecek_dosyalar/config.php';
require_once INCLUDES_PATH . '/db.php';

try {
    db()->query("ALTER TABLE fuel_prices ADD COLUMN truck_diesel_price DECIMAL(6,2) DEFAULT NULL AFTER diesel_price");
    echo "Success: Column truck_diesel_price added to fuel_prices table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>