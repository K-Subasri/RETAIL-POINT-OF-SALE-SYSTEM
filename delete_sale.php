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
    $saleId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if($saleId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Get sale items to restore inventory
        $stmt = $db->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = :id");
        $stmt->bindParam(':id', $saleId);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Restore product quantities
        foreach($items as $item) {
            $stmt = $db->prepare("UPDATE products SET quantity = quantity + :quantity WHERE product_id = :product_id");
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->execute();
            
            // Log inventory change
            $stmt = $db->prepare("INSERT INTO inventory_logs (product_id, user_id, quantity_change, previous_quantity, new_quantity, reason, reference_id) 
                                VALUES (:product_id, :user_id, :quantity_change, 
                                (SELECT quantity FROM products WHERE product_id = :product_id) - :quantity_change, 
                                (SELECT quantity FROM products WHERE product_id = :product_id), 
                                'return', :reference_id)");
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':quantity_change', $item['quantity']);
            $stmt->bindParam(':reference_id', $saleId);
            $stmt->execute();
        }
        
        // Delete sale items
        $stmt = $db->prepare("DELETE FROM sale_items WHERE sale_id = :id");
        $stmt->bindParam(':id', $saleId);
        $stmt->execute();
        
        // Delete sale
        $stmt = $db->prepare("DELETE FROM sales WHERE sale_id = :id");
        $stmt->bindParam(':id', $saleId);
        $stmt->execute();
        
        $db->commit();
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>