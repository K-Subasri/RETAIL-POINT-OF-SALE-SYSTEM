<?php
require_once 'config.php';
require_once 'db.php';

// Get inventory status counts
$query = "SELECT 
            SUM(CASE WHEN quantity > reorder_level THEN 1 ELSE 0 END) as in_stock,
            SUM(CASE WHEN quantity > 0 AND quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
          FROM products";

$stmt = $db->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
    'values' => [
        $result['in_stock'] ?? 0,
        $result['low_stock'] ?? 0,
        $result['out_of_stock'] ?? 0
    ]
]);