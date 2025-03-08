<?php
session_start();
require_once '../../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different API endpoints
switch ($action) {
    case 'get_sales_data':
        getSalesData();
        break;
    case 'get_overdue_receivables':
        getOverdueReceivables();
        break;
    case 'register_payment':
        registerPayment();
        break;
    case 'get_monthly_summary':
        getMonthlySummary();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function getSalesData() {
    global $conn;
    
    try {
        // Get sales data for the last 6 months
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                SUM(final_amount) as total
            FROM sales 
            WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND status = 'completed'
            GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $salesData]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getOverdueReceivables() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                ar.id,
                c.name as customer_name,
                ar.sale_id,
                ar.due_date,
                ar.amount - ar.paid_amount as amount,
                DATEDIFF(CURDATE(), ar.due_date) as days_overdue
            FROM accounts_receivable ar
            JOIN sales s ON ar.sale_id = s.id
            JOIN customers c ON s.customer_id = c.id
            WHERE ar.status IN ('pending', 'partial')
            AND ar.due_date < CURDATE()
            ORDER BY ar.due_date ASC
        ");
        $stmt->execute();
        $receivables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for DataTables
        echo json_encode([
            'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
            'recordsTotal' => count($receivables),
            'recordsFiltered' => count($receivables),
            'data' => $receivables
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function registerPayment() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['receivable_id']) || !isset($data['amount'])) {
            throw new Exception('Missing required fields');
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get current receivable info
        $stmt = $conn->prepare("
            SELECT amount, paid_amount
            FROM accounts_receivable
            WHERE id = :id
            FOR UPDATE
        ");
        $stmt->execute(['id' => $data['receivable_id']]);
        $receivable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receivable) {
            throw new Exception('Receivable not found');
        }
        
        $remaining = $receivable['amount'] - $receivable['paid_amount'];
        $newPaidAmount = $receivable['paid_amount'] + $data['amount'];
        
        // Validate payment amount
        if ($data['amount'] <= 0 || $data['amount'] > $remaining) {
            throw new Exception('Invalid payment amount');
        }
        
        // Update receivable
        $stmt = $conn->prepare("
            UPDATE accounts_receivable
            SET 
                paid_amount = :paid_amount,
                status = CASE 
                    WHEN :paid_amount >= amount THEN 'paid'
                    ELSE 'partial'
                END,
                payment_date = CASE 
                    WHEN :paid_amount >= amount THEN CURDATE()
                    ELSE payment_date
                END
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $data['receivable_id'],
            'paid_amount' => $newPaidAmount
        ]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment registered successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getMonthlySummary() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as total_sales,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_transactions,
                AVG(CASE WHEN status = 'completed' THEN final_amount ELSE NULL END) as average_sale
            FROM sales
            WHERE MONTH(sale_date) = MONTH(CURDATE())
            AND YEAR(sale_date) = YEAR(CURDATE())
        ");
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get payment methods distribution
        $stmt = $conn->prepare("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(final_amount) as total
            FROM sales
            WHERE MONTH(sale_date) = MONTH(CURDATE())
            AND YEAR(sale_date) = YEAR(CURDATE())
            AND status = 'completed'
            GROUP BY payment_method
        ");
        $stmt->execute();
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary['payment_methods'] = $paymentMethods;
        
        echo json_encode(['success' => true, 'data' => $summary]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
