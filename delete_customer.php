<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if(!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        exit;
    }
    
    try {
        // Check if customer has any sales
        $stmt = $db->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = :id");
        $stmt->bindParam(':id', $customerId);
        $stmt->execute();
        $salesCount = $stmt->fetchColumn();
        
        if($salesCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete customer with sales history']);
            exit;
        }
        
        // Delete the customer
        $stmt = $db->prepare("DELETE FROM customers WHERE customer_id = :id");
        $stmt->bindParam(':id', $customerId);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>