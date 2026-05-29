<?php
// seed_inventory.php
session_start();
require_once 'config/db.php';

// Ensure only admins can run this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    die("Access Denied.");
}

$parts_to_add = [
    // Hero MotoCorp
    ['HER-FIL-01', 'Hero Splendor Oil Filter', 20, 95.00, 5],
    ['HER-BRK-01', 'Hero Passion Brake Pad', 15, 150.00, 3],
    // Bajaj Auto
    ['BAJ-FIL-01', 'Bajaj Pulsar Oil Filter', 25, 110.00, 5],
    ['BAJ-CHN-01', 'Bajaj Dominar Chain Kit', 5, 2500.00, 2],
    // Honda India
    ['HON-BEL-01', 'Honda Activa Drive Belt', 18, 550.00, 4],
    ['HON-PLG-01', 'Honda Spark Plug (NGK)', 40, 120.00, 10],
    // TVS Motor
    ['TVS-SHK-01', 'TVS Apache Rear Shock', 8, 1200.00, 2],
    ['TVS-BRK-01', 'TVS Jupiter Brake Shoe', 12, 280.00, 4],
    // Royal Enfield
    ['RE-OIL-01', 'RE 15W50 Engine Oil (1L)', 50, 450.00, 10],
    ['RE-CLU-01', 'RE Classic 350 Clutch Plate', 10, 1800.00, 3],
    // Yamaha India
    ['YAM-AIR-01', 'Yamaha FZ Air Filter', 20, 220.00, 5],
    // Maruti Suzuki
    ['MAR-OIL-01', 'Maruti Swift Oil Filter', 30, 350.00, 5],
    ['MAR-WPR-01', 'Maruti WagonR Wiper Blades', 15, 450.00, 3],
    // Hyundai India
    ['HYU-AIR-01', 'Hyundai i20 AC Filter', 12, 600.00, 3],
    // Tata Motors
    ['TAT-FLT-01', 'Tata Nexon Fuel Filter', 8, 1500.00, 2],
    // Mahindra & Mahindra
    ['MAH-BRK-01', 'Mahindra Scorpio Brake Pads', 6, 2200.00, 2],
    // Toyota Kirloskar
    ['TOY-FIL-01', 'Toyota Innova Cabin Filter', 10, 900.00, 3]
];

// Add dummy parts to reach ~100 items
for ($i = 1; $i <= 85; $i++) {
    $parts_to_add[] = ["DUM-PRT-" . str_pad($i, 3, '0', STR_PAD_LEFT), "Dummy Part $i", rand(1, 100), rand(50, 5000) / 10, rand(1, 10)];
}

try {
    $stmt = $pdo->prepare("INSERT INTO inventory (part_code, part_name, quantity, price_per_unit, low_stock_threshold) 
                           VALUES (?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           quantity = VALUES(quantity), 
                           price_per_unit = VALUES(price_per_unit)");

    foreach ($parts_to_add as $part) {
        $stmt->execute($part);
    }
    echo "Inventory successfully updated! <a href='inventory.php'>View Inventory</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>