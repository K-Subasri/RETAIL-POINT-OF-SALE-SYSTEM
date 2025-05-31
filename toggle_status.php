<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify authentication and admin role
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$itemId = $input['id'] ?? null;
$itemType = $input['type'] ?? null;
$action = $input['action'] ?? null;

// Validate input
if (!$itemId || !$itemType || !in_array($action, ['activate', 'deactivate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $newStatus = $action === 'activate' ? 1 : 0;
    
    switch ($itemType) {
        case 'user':
            // Prevent modifying own status
            if ($itemId == $_SESSION['user_id']) {
                throw new Exception("You cannot modify your own status");
            }
            
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$newStatus, $itemId]);
            break;
            
        case 'product':
            $stmt = $db->prepare("UPDATE products SET is_active = ? WHERE product_id = ?");
            $stmt->execute([$newStatus, $itemId]);
            break;
            
        default:
            throw new Exception("Invalid item type");
    }
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($itemType) . ' ' . $action . 'd successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}