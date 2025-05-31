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
    $userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if($userId <= 0 || !in_array($action, ['activate', 'deactivate'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Prevent modifying own status
    if($userId == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot modify your own status']);
        exit;
    }
    
    try {
        $is_active = $action === 'activate' ? 1 : 0;
        $stmt = $db->prepare("UPDATE users SET is_active = :is_active WHERE user_id = :user_id");
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId);
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