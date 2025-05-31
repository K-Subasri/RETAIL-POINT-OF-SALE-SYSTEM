<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php'; // Adjust if $auth comes from somewhere else

// Only allow admin access
if(!$auth->hasRole('admin')) {
    header("Location: /pos_system/modules/dashboard/");
    exit;
}

$userId = isset($_GET['id']) ? $_GET['id'] : null;
$user = null;

// Load existing user data if editing
if($userId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if($userId) {
            // Update user
            if(!empty($password)) {
                if($password !== $confirm_password) {
                    throw new Exception("Passwords do not match.");
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET 
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    role = :role,
                    is_active = :is_active,
                    password = :password
                    WHERE user_id = :user_id");
                $stmt->bindParam(':password', $hashed_password);
            } else {
                $stmt = $db->prepare("UPDATE users SET 
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    role = :role,
                    is_active = :is_active
                    WHERE user_id = :user_id");
            }
            $stmt->bindParam(':user_id', $userId);
        } else {
            // Create new user
            if(empty($password)) {
                throw new Exception("Password is required for new users.");
            }
            if($password !== $confirm_password) {
                throw new Exception("Passwords do not match.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) 
                                  VALUES (:username, :password, :full_name, :email, :role, :is_active)");
            $stmt->bindParam(':password', $hashed_password);
        }

        // Common bindings
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "User " . ($userId ? "updated" : "added") . " successfully!";
        header("Location: index.php");
        exit;
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Set title and load page
$pageTitle = $userId ? "Edit User" : "Add User";
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><?php echo $userId ? 'Edit' : 'Add'; ?> User</h4>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="cashier" <?php echo ($user['role'] ?? '') == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                        <option value="manager" <?php echo ($user['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="admin" <?php echo ($user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label"><?php echo $userId ? 'New ' : ''; ?>Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <?php if($userId): ?>
                        <small class="text-muted">Leave blank to keep current password</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                       <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_active">Active User</label>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
