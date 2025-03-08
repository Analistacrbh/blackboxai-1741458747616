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
    case 'search_products':
        searchProducts();
        break;
    case 'search_customers':
        searchCustomers();
        break;
    case 'process_sale':
        processSale();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function searchProducts() {
    global $conn;
    
    try {
        $search = $_GET['q'] ?? '';
        $query = "SELECT id, code, name, price, stock_quantity 
                 FROM products 
                 WHERE (code LIKE :search OR name LIKE :search)
                 AND status = 'active' 
                 AND stock_quantity > 0
                 LIMIT 10";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute(['search' => "%$search%"]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function searchCustomers() {
    global $conn;
    
    try {
        $search = $_GET['q'] ?? '';
        $query = "SELECT id, name, document_number, phone 
                 FROM customers 
                 WHERE (name LIKE :search OR document_number LIKE :search)
                 AND status = 'active'
                 LIMIT 10";
                 
        $stmt = $conn->prepare($query);
        $stmt->execute(['search' => "%$search%"]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $customers]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function processSale() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Invalid request data');
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Insert sale record
        $stmt = $conn->prepare("
            INSERT INTO sales (
                customer_id, user_id, total_amount, discount, 
                final_amount, payment_method, status, notes
            ) VALUES (
                :customer_id, :user_id, :total_amount, :discount,
                :final_amount, :payment_method, :status, :notes
            )
        ");
        
        $stmt->execute([
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => $_SESSION['user_id'],
            'total_amount' => $data['subtotal'],
            'discount' => $data['discount'],
            'final_amount' => $data['total'],
            'payment_method' => $data['payment_method'],
            'status' => 'completed',
            'notes' => $data['notes'] ?? null
        ]);
        
        $sale_id = $conn->lastInsertId();
        
        // Insert sale items
        $stmt = $conn->prepare("
            INSERT INTO sale_items (
                sale_id, product_id, quantity, unit_price, total_price
            ) VALUES (
                :sale_id, :product_id, :quantity, :unit_price, :total_price
            )
        ");
        
        foreach ($data['items'] as $item) {
            $stmt->execute([
                'sale_id' => $sale_id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['total']
            ]);
            
            // Update product stock
            $updateStock = $conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - :quantity 
                WHERE id = :product_id
            ");
            $updateStock->execute([
                'quantity' => $item['quantity'],
                'product_id' => $item['id']
            ]);
        }
        
        // If payment method is installments, create receivables
        if ($data['payment_method'] === 'installments') {
            $installments = $data['installments'];
            $installmentAmount = $data['total'] / $installments;
            $dueDate = new DateTime();
            
            $stmt = $conn->prepare("
                INSERT INTO accounts_receivable (
                    sale_id, installment_number, amount, due_date, status
                ) VALUES (
                    :sale_id, :installment_number, :amount, :due_date, 'pending'
                )
            ");
            
            for ($i = 1; $i <= $installments; $i++) {
                $dueDate->modify('+1 month');
                $stmt->execute([
                    'sale_id' => $sale_id,
                    'installment_number' => $i,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate->format('Y-m-d')
                ]);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Sale processed successfully',
            'sale_id' => $sale_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Error processing sale: ' . $e->getMessage()]);
    }
}
?>
