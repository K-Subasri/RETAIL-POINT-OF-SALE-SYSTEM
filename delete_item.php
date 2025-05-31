<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if(!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemId = $_POST['id'] ?? null;
    $itemType = $_POST['type'] ?? '';
    
    try {
        switch($itemType) {
            case 'product':
                // Check if product exists in any sales
                $check = $db->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
                $check->execute([$itemId]);
                if($check->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete product with sales history']);
                    exit;
                }
                
                // Delete product
                $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
                break;
                
            case 'customer':
                // Check if customer has any sales
                $check = $db->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
                $check->execute([$itemId]);
                if($check->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete customer with purchase history']);
                    exit;
                }
                
                // Delete customer
                $stmt = $db->prepare("DELETE FROM customers WHERE customer_id = ?");
                break;
                
            case 'sale':
                // Delete sale (with cascade to sale_items)
                $stmt = $db->prepare("DELETE FROM sales WHERE sale_id = ?");
                break;
                
            case 'user':
                // Prevent deleting own account
                if($itemId == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                    exit;
                }
                
                // Delete user
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid item type']);
                exit;
        }
        
        $stmt->execute([$itemId]);
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>