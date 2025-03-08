<?php
/**
 * User Notifications Script
 * Handles system notifications for users
 */

require_once 'init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_notifications':
                // Get unread notifications
                $stmt = $conn->prepare("
                    SELECT id, type, message, created_at 
                    FROM notifications 
                    WHERE user_id = ? AND read_at IS NULL 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $notifications = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;
                
            case 'mark_read':
                $notificationId = $_POST['notification_id'] ?? null;
                
                if ($notificationId) {
                    $stmt = $conn->prepare("
                        UPDATE notifications 
                        SET read_at = NOW() 
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$notificationId, $_SESSION['user_id']]);
                }
                
                echo json_encode(['success' => true]);
                break;
                
            case 'mark_all_read':
                $stmt = $conn->prepare("
                    UPDATE notifications 
                    SET read_at = NOW() 
                    WHERE user_id = ? AND read_at IS NULL
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        exit();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit();
    }
}

// Get all notifications for display
try {
    $stmt = $conn->prepare("
        SELECT id, type, message, created_at, read_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}

// Include header
$pageTitle = "Notificações";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Notificações</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllRead()">
                        <i class="fas fa-check-double"></i> Marcar Todas como Lidas
                    </button>
                </div>
            </div>

            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info">
                        Você não tem notificações.
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="card mb-3 notification <?php echo $notification['read_at'] ? 'bg-light' : ''; ?>" 
                             data-id="<?php echo $notification['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $icon = 'info-circle';
                                        switch ($notification['type']) {
                                            case 'warning':
                                                $icon = 'exclamation-triangle';
                                                break;
                                            case 'success':
                                                $icon = 'check-circle';
                                                break;
                                            case 'error':
                                                $icon = 'times-circle';
                                                break;
                                        }
                                        ?>
                                        <i class="fas fa-<?php echo $icon; ?> text-<?php echo $notification['type']; ?>"></i>
                                        <?php echo h($notification['message']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo formatDate($notification['created_at'], true); ?>
                                        <?php if (!$notification['read_at']): ?>
                                            <button class="btn btn-sm btn-link" onclick="markRead(<?php echo $notification['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Mark notification as read
    function markRead(id) {
        fetch('notifications.php?action=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.querySelector(`.notification[data-id="${id}"]`);
                notification.classList.add('bg-light');
                notification.querySelector('.btn-link').remove();
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Mark all notifications as read
    function markAllRead() {
        fetch('notifications.php?action=mark_all_read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification').forEach(notification => {
                    notification.classList.add('bg-light');
                    const button = notification.querySelector('.btn-link');
                    if (button) button.remove();
                });
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Update notification count in header
    function updateNotificationCount() {
        const countElement = document.querySelector('#notification-count');
        if (countElement) {
            const currentCount = parseInt(countElement.textContent);
            if (currentCount > 0) {
                countElement.textContent = '0';
                countElement.classList.add('d-none');
            }
        }
    }

    // Check for new notifications periodically
    setInterval(() => {
        fetch('notifications.php?action=get_notifications')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    // Update notification count
                    const countElement = document.querySelector('#notification-count');
                    if (countElement) {
                        countElement.textContent = data.notifications.length;
                        countElement.classList.remove('d-none');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }, 60000); // Check every minute
</script>

<style>
    .notification {
        transition: background-color 0.3s ease;
    }
    .notification:not(.bg-light):hover {
        background-color: #f8f9fa;
    }
    .btn-link {
        padding: 0;
        margin-left: 10px;
    }
    .text-warning { color: #ffc107; }
    .text-success { color: #28a745; }
    .text-error { color: #dc3545; }
    .text-info { color: #17a2b8; }
</style>
