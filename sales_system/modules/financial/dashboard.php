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

// Get financial summary data
try {
    // Today's sales
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as total
        FROM sales 
        WHERE DATE(sale_date) = CURDATE()
        AND status = 'completed'
    ");
    $stmt->execute();
    $todaySales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Weekly sales
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as total
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status = 'completed'
    ");
    $stmt->execute();
    $weeklySales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Monthly sales
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as total
        FROM sales 
        WHERE MONTH(sale_date) = MONTH(CURDATE())
        AND YEAR(sale_date) = YEAR(CURDATE())
        AND status = 'completed'
    ");
    $stmt->execute();
    $monthlySales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Accounts receivable summary
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status = 'pending' AND due_date >= CURDATE() THEN amount - paid_amount ELSE 0 END) as upcoming,
            SUM(CASE WHEN status IN ('pending', 'partial') AND due_date < CURDATE() THEN amount - paid_amount ELSE 0 END) as overdue
        FROM accounts_receivable
    ");
    $stmt->execute();
    $receivables = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error and show generic message
    error_log("Database error: " . $e->getMessage());
    $error = "Erro ao carregar dados financeiros";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro - Sistema de Vendas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .card-dashboard {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include sidebar here -->
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Financeiro</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportExcel()">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="dateRange">
                            <i class="fas fa-calendar"></i> Esta Semana
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Sales Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card card-dashboard bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Vendas Hoje</h5>
                                <h2 class="card-text">R$ <?php echo number_format($todaySales, 2, ',', '.'); ?></h2>
                                <p class="card-text"><small>Atualizado agora</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashboard bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Vendas na Semana</h5>
                                <h2 class="card-text">R$ <?php echo number_format($weeklySales, 2, ',', '.'); ?></h2>
                                <p class="card-text"><small>Últimos 7 dias</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashboard bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Vendas no Mês</h5>
                                <h2 class="card-text">R$ <?php echo number_format($monthlySales, 2, ',', '.'); ?></h2>
                                <p class="card-text"><small>Mês atual</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <h5 class="card-title">Vendas por Período</h5>
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashboard">
                            <div class="card-body">
                                <h5 class="card-title">Contas a Receber</h5>
                                <div class="chart-container">
                                    <canvas id="receivablesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accounts Receivable Table -->
                <div class="card card-dashboard mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Contas a Receber Vencidas</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="overdueTable">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Venda</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                        <th>Dias Vencido</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#overdueTable').DataTable({
                ajax: 'api.php?action=get_overdue_receivables',
                columns: [
                    { data: 'customer_name' },
                    { data: 'sale_id' },
                    { data: 'due_date' },
                    { 
                        data: 'amount',
                        render: function(data) {
                            return 'R$ ' + parseFloat(data).toFixed(2).replace('.', ',');
                        }
                    },
                    { data: 'days_overdue' },
                    {
                        data: null,
                        render: function(data) {
                            return '<button class="btn btn-sm btn-primary" onclick="registerPayment(' + data.id + ')">Registrar Pagamento</button>';
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
        });

        // Sales Chart
        const salesChart = new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho'],
                datasets: [{
                    label: 'Vendas',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Receivables Chart
        const receivablesChart = new Chart(document.getElementById('receivablesChart'), {
            type: 'doughnut',
            data: {
                labels: ['A Vencer', 'Vencido'],
                datasets: [{
                    data: [
                        <?php echo $receivables['upcoming']; ?>,
                        <?php echo $receivables['overdue']; ?>
                    ],
                    backgroundColor: [
                        'rgb(75, 192, 192)',
                        'rgb(255, 99, 132)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Function to register payment
        function registerPayment(id) {
            // Implement payment registration logic
        }

        // Function to export to PDF
        function exportPDF() {
            // Implement PDF export logic
        }

        // Function to export to Excel
        function exportExcel() {
            // Implement Excel export logic
        }
    </script>
</body>
</html>
