<?php
session_start();
require_once '../config/database.php';
require_once 'admin-dashboard.php';

// Initialize variables
$users = [];
$error = null;
$user_columns = [
    'id',
    'name',       
    'email',
    'role',
    'created_at',
    'last_login',
    'is_active'
];

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE username LIKE :search OR email LIKE :search";
    $params[':search'] = "%$search%";
}

// Get total users for pagination
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $search_condition");
    $stmt->execute($params);
    $total_users = $stmt->fetch()['total'];
    $total_pages = ceil($total_users / $per_page);
    
    // Get users with pagination
    $stmt = $pdo->prepare("
        SELECT " . implode(', ', $user_columns) . " 
        FROM users 
        $search_condition
        ORDER BY created_at DESC 
        LIMIT :offset, :per_page
    ");
    
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(); // Ensure $users is always an array
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'toggle_active') {
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Log the action
                $action_status = $pdo->query("SELECT is_active FROM users WHERE id = $user_id")->fetch()['is_active'];
                $description = $action_status ? "activated user ID $user_id" : "deactivated user ID $user_id";
                logAdminAction($_SESSION['user_id'], 'user_' . ($action_status ? 'activate' : 'deactivate'), $description, null, $user_id);
                
            } elseif ($action === 'change_role' && isset($_POST['new_role'])) {
                $new_role = $_POST['new_role'];
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                
                // Log the action
                logAdminAction($_SESSION['user_id'], 'user_role_change', "changed role to $new_role for user ID $user_id", null, $user_id);
            }
            
            header("Location: user-management.php?page=$page" . (!empty($search) ? "&search=$search" : ''));
            exit;
            
        } catch (PDOException $e) {
            $error = "Failed to update user: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <!-- Your navigation code here -->
        </nav>

        <main class="admin-main">
            <div class="dashboard-header">
                <h1><i class="fas fa-users"></i> User Management</h1>
                <p>Manage all registered users in the system</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="user-search">
                <form method="GET" action="user-management.php">
                    <input type="text" name="search" placeholder="Search by username or email" 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="user-management.php" class="clear-search">Clear search</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="user-list">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <form method="POST" class="role-form">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <select name="new_role" onchange="this.form.submit()">
                                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td><?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?></td>
                                <td>
                                    <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="toggle-active-form">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <button type="submit" class="toggle-btn <?= $user['is_active'] ? 'deactivate' : 'activate' ?>">
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-users">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="user-management.php?page=<?= $page-1 ?><?= !empty($search) ? "&search=$search" : '' ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="user-management.php?page=<?= $i ?><?= !empty($search) ? "&search=$search" : '' ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="user-management.php?page=<?= $page+1 ?><?= !empty($search) ? "&search=$search" : '' ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>