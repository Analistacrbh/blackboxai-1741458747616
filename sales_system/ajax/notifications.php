<?php
/**
 * AJAX Handler for Notifications
 * Handles real-time notification updates
 */

require_once '../init.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit();
}

// Initialize notification handler
$notification = Notification::getInstance();

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_unread':
            // Get unread notifications
            $notifications = $notification->getUnread();
            $count = $notification->getUnreadCount();
            
            // Format notifications for display
            $formatted = array_map(function($n) {
                return [
                    'id' => $n['id'],
                    'message' => $n['message'],
                    'type' => $n['type'],
                    'module' => $n['module'],
                    'link' => $n['link'],
                    'created_at' => formatDate($n['created_at'], true),
                    'html' => generateNotificationHtml($n)
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'notifications' => $formatted,
                'count' => $count
            ]);
            break;
            
        case 'mark_read':
            // Validate notification ID
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('Notification ID required');
            }
            
            // Mark notification as read
            $success = $notification->markRead($id);
            
            echo json_encode([
                'success' => $success,
                'count' => $notification->getUnreadCount()
            ]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $success = $notification->markAllRead();
            
            echo json_encode([
                'success' => $success,
                'count' => 0
            ]);
            break;
            
        case 'delete':
            // Validate notification ID
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('Notification ID required');
            }
            
            // Delete notification
            $success = $notification->delete($id);
            
            echo json_encode([
                'success' => $success,
                'count' => $notification->getUnreadCount()
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate HTML for notification
 * @param array $notification Notification data
 * @return string HTML content
 */
function generateNotificationHtml($notification) {
    $icons = [
        'info' => 'info-circle',
        'warning' => 'exclamation-triangle',
        'success' => 'check-circle',
        'error' => 'times-circle'
    ];
    
    $icon = $icons[$notification['type']] ?? 'info-circle';
    
    $html = '<div class="notification-item" data-id="' . $notification['id'] . '">';
    $html .= '<div class="d-flex align-items-center">';
    $html .= '<div class="flex-grow-1">';
    $html .= '<i class="fas fa-' . $icon . ' text-' . $notification['type'] . '"></i> ';
    $html .= htmlspecialchars($notification['message']);
    
    if ($notification['link']) {
        $html .= ' <a href="' . htmlspecialchars($notification['link']) . '">Ver mais</a>';
    }
    
    $html .= '<div class="small text-muted">' . formatDate($notification['created_at'], true) . '</div>';
    $html .= '</div>';
    $html .= '<div class="ms-2">';
    $html .= '<button type="button" class="btn btn-sm btn-link mark-read" onclick="markRead(' . $notification['id'] . ')">';
    $html .= '<i class="fas fa-check"></i>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>
