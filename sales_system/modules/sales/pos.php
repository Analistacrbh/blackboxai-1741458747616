<?php
session_start();
require_once '../../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - Sistema de Vendas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .product-list {
            height: calc(100vh - 350px);
            overflow-y: auto;
        }
        .total-section {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .payment-options .btn {
            margin: 5px;
        }
        #searchProduct {
            padding: 20px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column - Product List -->
            <div class="col-md-8 p-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> PDV - Ponto de Venda</h4>
                    </div>
                    <div class="card-body">
                        <!-- Search Product -->
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="searchProduct" class="form-control" placeholder="Código do produto ou nome">
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#productSearchModal">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <!-- Current Sale Items -->
                        <div class="product-list">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Qtd</th>
                                        <th>Preço Un.</th>
                                        <th>Total</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="saleItems">
                                    <!-- Items will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="total-section">
                                            Subtotal: R$ <span id="subtotal">0,00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="total-section">
                                            Desconto: R$ <span id="discount">0,00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="total-section text-primary">
                                            Total: R$ <span id="total">0,00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Payment Options -->
            <div class="col-md-4 p-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-money-bill-wave"></i> Pagamento</h4>
                    </div>
                    <div class="card-body">
                        <!-- Customer Selection -->
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customerName" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#customerSearchModal">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="payment-options">
                            <label class="form-label">Forma de Pagamento</label>
                            <div class="d-grid gap-2">
                                <button class="btn btn-lg btn-outline-primary" onclick="selectPayment('money')">
                                    <i class="fas fa-money-bill-alt"></i> Dinheiro
                                </button>
                                <button class="btn btn-lg btn-outline-primary" onclick="selectPayment('credit')">
                                    <i class="fas fa-credit-card"></i> Cartão de Crédito
                                </button>
                                <button class="btn btn-lg btn-outline-primary" onclick="selectPayment('debit')">
                                    <i class="fas fa-credit-card"></i> Cartão de Débito
                                </button>
                                <button class="btn btn-lg btn-outline-primary" onclick="selectPayment('pix')">
                                    <i class="fas fa-qrcode"></i> PIX
                                </button>
                                <button class="btn btn-lg btn-outline-primary" onclick="selectPayment('installments')">
                                    <i class="fas fa-clock"></i> Parcelado
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <div class="d-grid gap-2">
                                <button class="btn btn-lg btn-success" onclick="finalizeSale()">
                                    <i class="fas fa-check-circle"></i> Finalizar Venda
                                </button>
                                <button class="btn btn-lg btn-danger" onclick="cancelSale()">
                                    <i class="fas fa-times-circle"></i> Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Search Modal -->
    <div class="modal fade" id="productSearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="productSearchInput" placeholder="Digite para pesquisar...">
                    <table class="table table-striped" id="productTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Products will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Search Modal -->
    <div class="modal fade" id="customerSearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="customerSearchInput" placeholder="Digite para pesquisar...">
                    <table class="table table-striped" id="customerTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Documento</th>
                                <th>Telefone</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Customers will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finalizar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Payment form will be loaded dynamically based on payment method -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        let currentSale = {
            items: [],
            customer: null,
            paymentMethod: null,
            subtotal: 0,
            discount: 0,
            total: 0
        };

        // Function to add product to sale
        function addProduct(product) {
            const existingItem = currentSale.items.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.price;
            } else {
                currentSale.items.push({
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: 1,
                    total: product.price
                });
            }
            
            updateSaleDisplay();
        }

        // Function to update the sale display
        function updateSaleDisplay() {
            const tbody = document.getElementById('saleItems');
            tbody.innerHTML = '';
            
            currentSale.subtotal = 0;
            
            currentSale.items.forEach((item, index) => {
                currentSale.subtotal += item.total;
                
                tbody.innerHTML += `
                    <tr>
                        <td>${item.name}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, -1)">-</button>
                                <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                                <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, 1)">+</button>
                            </div>
                        </td>
                        <td>R$ ${item.price.toFixed(2)}</td>
                        <td>R$ ${item.total.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            currentSale.total = currentSale.subtotal - currentSale.discount;
            
            document.getElementById('subtotal').textContent = currentSale.subtotal.toFixed(2);
            document.getElementById('discount').textContent = currentSale.discount.toFixed(2);
            document.getElementById('total').textContent = currentSale.total.toFixed(2);
        }

        // Function to update item quantity
        function updateQuantity(index, change) {
            const item = currentSale.items[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity > 0) {
                item.quantity = newQuantity;
                item.total = item.quantity * item.price;
                updateSaleDisplay();
            }
        }

        // Function to remove item
        function removeItem(index) {
            currentSale.items.splice(index, 1);
            updateSaleDisplay();
        }

        // Function to select payment method
        function selectPayment(method) {
            currentSale.paymentMethod = method;
            
            // Show payment modal with appropriate form
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const modalBody = document.querySelector('#paymentModal .modal-body');
            
            let form = '';
            switch(method) {
                case 'money':
                    form = `
                        <div class="mb-3">
                            <label class="form-label">Valor Recebido</label>
                            <input type="number" class="form-control" id="receivedAmount" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Troco</label>
                            <input type="text" class="form-control" id="change" readonly>
                        </div>
                    `;
                    break;
                case 'installments':
                    form = `
                        <div class="mb-3">
                            <label class="form-label">Número de Parcelas</label>
                            <select class="form-control" id="installments">
                                <option value="2">2x</option>
                                <option value="3">3x</option>
                                <option value="4">4x</option>
                                <option value="5">5x</option>
                                <option value="6">6x</option>
                            </select>
                        </div>
                    `;
                    break;
                // Add other payment methods as needed
            }
            
            form += `
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-primary" onclick="processPayment()">
                        Confirmar Pagamento
                    </button>
                </div>
            `;
            
            modalBody.innerHTML = form;
            modal.show();
        }

        // Function to process payment
        function processPayment() {
            // Here you would typically make an AJAX call to your backend
            // to process the payment and save the sale
            
            // For demonstration, we'll just show a success message
            alert('Venda finalizada com sucesso!');
            
            // Reset the sale
            currentSale = {
                items: [],
                customer: null,
                paymentMethod: null,
                subtotal: 0,
                discount: 0,
                total: 0
            };
            
            updateSaleDisplay();
            
            // Close the payment modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
        }

        // Function to cancel sale
        function cancelSale() {
            if (confirm('Tem certeza que deseja cancelar a venda?')) {
                currentSale = {
                    items: [],
                    customer: null,
                    paymentMethod: null,
                    subtotal: 0,
                    discount: 0,
                    total: 0
                };
                updateSaleDisplay();
            }
        }

        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Product search
            document.getElementById('searchProduct').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    // Here you would typically make an AJAX call to search for the product
                    // For demonstration, we'll add a dummy product
                    addProduct({
                        id: 1,
                        name: 'Produto Exemplo',
                        price: 99.99
                    });
                }
            });

            // Calculate change in money payment
            document.getElementById('paymentModal').addEventListener('input', function(e) {
                if (e.target.id === 'receivedAmount') {
                    const received = parseFloat(e.target.value) || 0;
                    const change = received - currentSale.total;
                    document.getElementById('change').value = change >= 0 ? change.toFixed(2) : '0.00';
                }
            });
        });
    </script>
</body>
</html>
