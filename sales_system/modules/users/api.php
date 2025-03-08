<?php
session_start();
require_once '../../config/database.php';

// Check authentication and admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the action
$action = $_GET['action'] ?? '';

// Handle different API endpoints
switch ($action) {
    case 'list_users':
        listUsers();
        break;
    case 'get_user':
        getUser();
        break;
    case 'create_user':
        createUser();
        break;
    case 'update_user':
        updateUser();
        break;
    case 'delete_user':
        deleteUser();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function listUsers() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT id, username, full_name, user_level, status, created_at
            FROM users
            ORDER BY id DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for DataTables
        echo json_encode([
            'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
            'recordsTotal' => count($users),
            'recordsFiltered' => count($users),
            'data' => $users
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getUser() {
    global $conn;
    
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('User ID is required');
        }
        
        $stmt = $conn->prepare("
            SELECT id, username, full_name, user_level, status
            FROM users
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        echo json_encode(['success' => true, 'data' => $user]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function createUser() {
    global $conn;
    
    try {
        // Validate required fields
        $requiredFields = ['username', 'password', 'full_name', 'user_level'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $_POST['username']]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Hash password
        $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (
                username, password_hash, full_name, user_level, status
            ) VALUES (
                :username, :password_hash, :full_name, :user_level, :status
            )
        ");
        
        $stmt->execute([
            'username' => $_POST['username'],
            'password_hash' => $passwordHash,
            'full_name' => $_POST['full_name'],
            'user_level' => $_POST['user_level'],
            'status' => $_POST['status'] ?? 'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'id' => $conn->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateUser() {
    global $conn;
    
    try {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception('User ID is required');
        }
        
        // Check if username exists (excluding current user)
        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE username = :username 
            AND id != :id
        ");
        $stmt->execute([
            'username' => $_POST['username'],
            'id' => $id
        ]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Start building update query
        $updateFields = [
            'username = :username',
            'full_name = :full_name',
            'user_level = :user_level',
            'status = :status'
        ];
        $params = [
            'id' => $id,
            'username' => $_POST['username'],
            'full_name' => $_POST['full_name'],
            'user_level' => $_POST['user_level'],
            'status' => $_POST['status']
        ];
        
        // Add password update if provided
        if (!empty($_POST['password'])) {
            $updateFields[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Update user
        $stmt = $conn->prepare("
            UPDATE users 
            SET " . implode(', ', $updateFields) . "
            WHERE id = :id
        ");
        
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function deleteUser() {
    global $conn;
    
    try {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception('User ID is required');
        }
        
        // Prevent deleting the last admin user
        $stmt = $conn->prepare("
            SELECT COUNT(*) as admin_count 
            FROM users 
            WHERE user_level = 'admin' 
            AND status = 'active'
        ");
        $stmt->execute();
        $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['admin_count'];
        
        $stmt = $conn->prepare("
            SELECT user_level 
            FROM users 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminCount <= 1 && $user['user_level'] === 'admin') {
            throw new Exception('Cannot delete the last admin user');
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
