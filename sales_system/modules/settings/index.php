<?php
session_start();
require_once '../../config/database.php';

// Check authentication and admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get current settings
try {
    $stmt = $conn->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar configurações";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema de Vendas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configurações do Sistema</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Settings Form -->
                <div class="row">
                    <!-- Company Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-building"></i> Informações da Empresa
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="companyForm">
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Nome da Empresa</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_document" class="form-label">CNPJ</label>
                                        <input type="text" class="form-control" id="company_document" name="company_document" 
                                               value="<?php echo htmlspecialchars($settings['company_document'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_address" class="form-label">Endereço</label>
                                        <textarea class="form-control" id="company_address" name="company_address" rows="3"
                                        ><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_phone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                               value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="company_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="company_email" name="company_email" 
                                               value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Informações da Empresa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs"></i> Configurações do Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="systemForm">
                                    <div class="mb-3">
                                        <label for="printer_type" class="form-label">Tipo de Impressora</label>
                                        <select class="form-select" id="printer_type" name="printer_type">
                                            <option value="thermal" <?php echo ($settings['printer_type'] ?? '') === 'thermal' ? 'selected' : ''; ?>>Térmica</option>
                                            <option value="matrix" <?php echo ($settings['printer_type'] ?? '') === 'matrix' ? 'selected' : ''; ?>>Matricial</option>
                                            <option value="laser" <?php echo ($settings['printer_type'] ?? '') === 'laser' ? 'selected' : ''; ?>>Laser</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="printer_name" class="form-label">Nome da Impressora</label>
                                        <input type="text" class="form-control" id="printer_name" name="printer_name" 
                                               value="<?php echo htmlspecialchars($settings['printer_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="receipt_header" class="form-label">Cabeçalho do Recibo</label>
                                        <textarea class="form-control" id="receipt_header" name="receipt_header" rows="3"
                                        ><?php echo htmlspecialchars($settings['receipt_header'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="receipt_footer" class="form-label">Rodapé do Recibo</label>
                                        <textarea class="form-control" id="receipt_footer" name="receipt_footer" rows="3"
                                        ><?php echo htmlspecialchars($settings['receipt_footer'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Configurações do Sistema
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- System Messages -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-comment-alt"></i> Mensagens do Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="messagesForm">
                                    <div class="mb-3">
                                        <label for="sale_success_message" class="form-label">Mensagem de Venda Concluída</label>
                                        <input type="text" class="form-control" id="sale_success_message" name="sale_success_message" 
                                               value="<?php echo htmlspecialchars($settings['sale_success_message'] ?? 'Venda realizada com sucesso!'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_success_message" class="form-label">Mensagem de Pagamento Recebido</label>
                                        <input type="text" class="form-control" id="payment_success_message" name="payment_success_message" 
                                               value="<?php echo htmlspecialchars($settings['payment_success_message'] ?? 'Pagamento registrado com sucesso!'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="low_stock_message" class="form-label">Mensagem de Estoque Baixo</label>
                                        <input type="text" class="form-control" id="low_stock_message" name="low_stock_message" 
                                               value="<?php echo htmlspecialchars($settings['low_stock_message'] ?? 'Atenção: Produto com estoque baixo!'); ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Mensagens
                                    </button>
                                </form>
                            </div>
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Handle company form submission
        $('#companyForm').on('submit', function(e) {
            e.preventDefault();
            saveSettings('company', $(this).serialize());
        });

        // Handle system form submission
        $('#systemForm').on('submit', function(e) {
            e.preventDefault();
            saveSettings('system', $(this).serialize());
        });

        // Handle messages form submission
        $('#messagesForm').on('submit', function(e) {
            e.preventDefault();
            saveSettings('messages', $(this).serialize());
        });

        // Function to save settings
        function saveSettings(type, data) {
            $.ajax({
                url: 'api.php?action=save_settings&type=' + type,
                method: 'POST',
                data: data,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: 'Configurações salvas com sucesso!'
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao salvar configurações'
                    });
                }
            });
        }

        // Initialize CNPJ mask
        $('#company_document').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            $(this).val(value);
        });

        // Initialize phone mask
        $('#company_phone').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else {
                value = value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            }
            $(this).val(value);
        });
    </script>
</body>
</html>
