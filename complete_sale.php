<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Validate required fields
if (empty($input['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Parse cart items
    $cart = json_decode($input['cart'], true);
    $subtotal = 0;
    
    // Calculate subtotal
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Calculate totals
    $taxRate = 0.1; // 10% tax
    $taxAmount = $subtotal * $taxRate;
    $discountAmount = floatval($input['discount'] ?? 0);
    $totalAmount = $subtotal + $taxAmount - $discountAmount;
    
    // Create sale record
    $stmt = $db->prepare("INSERT INTO sales 
        (customer_id, user_id, total_amount, tax_amount, discount_amount, payment_method_id, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['customer_id'] ?? null,
        $_SESSION['user_id'],
        $totalAmount,
        $taxAmount,
        $discountAmount,
        $input['payment_method_id'],
        $input['notes'] ?? null
    ]);
    
    $saleId = $db->lastInsertId();
    
    // Add sale items and update inventory
    foreach ($cart as $item) {
        // Add sale item
        $stmt = $db->prepare("INSERT INTO sale_items 
            (sale_id, product_id, quantity, unit_price, subtotal) 
            VALUES (?, ?, ?, ?, ?)");
        
        $itemTotal = $item['price'] * $item['quantity'];
        $stmt->execute([
            $saleId,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $itemTotal
        ]);
        
        // Update product quantity
        $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
        
        // Log inventory change
        $stmt = $db->prepare("INSERT INTO inventory_logs 
            (product_id, user_id, quantity_change, previous_quantity, new_quantity, reason, reference_id) 
            VALUES (?, ?, ?, ?, ?, 'sale', ?)");
        
        $stmt->execute([
            $item['id'],
            $_SESSION['user_id'],
            -$item['quantity'],
            $item['current_quantity'],
            $item['current_quantity'] - $item['quantity'],
            $saleId
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale_id' => $saleId,
        'redirect' => '../../modules/sales/receipt.php?id=' . $saleId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error completing sale: ' . $e->getMessage()]);
}