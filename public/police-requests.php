<?php
require_once '../config/database.php';
require_once 'admin-dashboard.php';

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
        SELECT id, username, email, role, created_at, last_login, is_active 
        FROM users 
        $search_condition
        ORDER BY created_at DESC 
        LIMIT :offset, :per_page
    ");
    
    $params[':offset'] = $offset;
    $params[':per_page'] = $per_page;
    
    foreach ($params as $key => &$val) {
        if ($key === ':offset' || $key === ':per_page') {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val);
        }
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle user actions (activate/deactivate, change role)
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

function logAdminAction($admin_id, $action_type, $description, $document_id = null, $user_id = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action_type, description, document_id, user_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$admin_id, $action_type, $description, $document_id, $user_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault | Admin</div>
            <ul>
                <li><a href="police-requests.php">Police Forum Requests</a></li>
                <li><a href="local-certification-requests.php">Local Certifications</a></li>
                <li><a href="home-affairs-requests.php">Home Affairs</a></li>
                <li><a href="approval-history.php">Approval History</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../search.php">Search</a>
                <i class="fa-solid fa-arrow-right-from-bracket"></i><a href="../logout.php">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="dashboard.php">Dashboard Overview</a>
            <a href="user-management.php" class="active">User Management</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>User Management</h1>
                <p>Manage all registered users in the system</p>
            </div>
        </main>

        <section class="user-management">
            <div class="user-search">
                <form method="GET" action="user-management.php">
                    <input type="text" name="search" placeholder="Search by username or email" value="<?= htmlspecialchars($search) ?>">
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
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
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
                    </tbody>
                </table>
            </div>

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
        </section>
    </div>
</body>
</html>
