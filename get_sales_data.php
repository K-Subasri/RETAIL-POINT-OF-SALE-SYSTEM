<?php
require_once 'config.php';
require_once 'db.php';

// Get sales data for the last 7 days
$query = "SELECT DATE(transaction_date) as date, SUM(total_amount) as total 
          FROM sales 
          WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY DATE(transaction_date) 
          ORDER BY date ASC";

$stmt = $db->prepare($query);
$stmt->execute();

$data = ['labels' => [], 'values' => []];

// Fill with zero values for all 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $data['labels'][] = date('D, M j', strtotime($date));
    $data['values'][] = 0;
}

// Replace with actual data where available
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $index = array_search(date('D, M j', strtotime($row['date'])), $data['labels']);
    if ($index !== false) {
        $data['values'][$index] = (float)$row['total'];
    }
}

header('Content-Type: application/json');
echo json_encode($data);