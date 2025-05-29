<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user's notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Use your MyNotifications.html template but make it dynamic -->
<div class="notifications-list">
    <?php foreach ($notifications as $notification): ?>
    <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?>">
        <div class="notification-icon">
            <i class="fas fa-<?= 
                $notification['type'] === 'approve' ? 'check-circle' : 
                ($notification['type'] === 'reject' ? 'times-circle' : 'info-circle')
            ?>"></i>
        </div>
        <div class="notification-content">
            <h3><?= $notification['title'] ?></h3>
            <p><?= $notification['message'] ?></p>
            <span class="notification-time">
                <?= time_elapsed_string($notification['created_at']) ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
// Helper function for time display
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $string = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    
    return $full ? implode(', ', $string) . ' ago' : current($string) . ' ago';
}
?>