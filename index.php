<?php
$pageTitle = "User Management";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Only allow admin access
if(!$auth->hasRole('admin')) {
    header("Location: /pos_system/modules/dashboard/");
    exit;
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
if(!empty($search)) {
    $where = "WHERE username LIKE :search OR full_name LIKE :search OR email LIKE :search";
}

// Get users with pagination
$query = "SELECT * FROM users $where ORDER BY username ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
if(!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total users for pagination
$countQuery = "SELECT COUNT(*) FROM users $where";
$countStmt = $db->prepare($countQuery);
if(!empty($search)) {
    $countStmt->bindParam(':search', $searchTerm);
}
$countStmt->execute();
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">User Management</h4>
        <div>
            <a href="add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add User
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No users found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $user['role'] == 'admin' ? 'danger' : 
                                    ($user['role'] == 'manager' ? 'warning text-dark' : 'primary'); 
                            ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo $user['last_login'] ? date('M j, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td>
                            <a href="add.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-outline-danger toggle-user-status" 
                                    data-id="<?php echo $user['user_id']; ?>" 
                                    data-action="<?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>"
                                    title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-power-off"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle user status
    $('.toggle-user-status').click(function() {
        const userId = $(this).data('id');
        const action = $(this).data('action');
        const button = $(this);
        
        if(confirm(`Are you sure you want to ${action} this user?`)) {
            $.ajax({
                url: '../../includes/toggle_user_status.php',
                method: 'POST',
                data: { 
                    id: userId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating user status');
                }
            });
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>