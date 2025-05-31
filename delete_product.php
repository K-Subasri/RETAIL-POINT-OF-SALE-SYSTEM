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
    $productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    try {
        // Get product image path first
        $stmt = $db->prepare("SELECT image_path FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        // Delete the product
        $stmt = $db->prepare("DELETE FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        
        // Delete associated image if exists
        if($product['image_path'] && file_exists("../" . $product['image_path'])) {
            unlink("../" . $product['image_path']);
        }
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>