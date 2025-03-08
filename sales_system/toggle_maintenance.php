<?php
/**
 * Toggle Maintenance Mode Script
 * Requires administrator access
 */

require_once 'init.php';

// Check if user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: error.php?code=403&message=Acesso não autorizado');
    exit();
}

// Get maintenance status and details
$maintenanceFile = __DIR__ . '/maintenance.flag';
$isInMaintenance = file_exists($maintenanceFile);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'enable') {
            // Enable maintenance mode
            $estimatedTime = $_POST['estimated_time'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            $maintenanceDetails = "Manutenção Programada\n";
            $maintenanceDetails .= "Início: " . date('Y-m-d H:i:s') . "\n";
            if ($estimatedTime) {
                $maintenanceDetails .= "Previsão de Conclusão: $estimatedTime\n";
            }
            if ($reason) {
                $maintenanceDetails .= "\nMotivo: $reason\n";
            }
            
            file_put_contents($maintenanceFile, $maintenanceDetails);
            
            // Log maintenance start
            $logger->info("Maintenance mode enabled", [
                'user_id' => $_SESSION['user_id'],
                'estimated_time' => $estimatedTime,
                'reason' => $reason
            ]);
            
        } elseif ($_POST['action'] === 'disable') {
            // Disable maintenance mode
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
                
                // Log maintenance end
                $logger->info("Maintenance mode disabled", [
                    'user_id' => $_SESSION['user_id']
                ]);
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get maintenance details if in maintenance mode
$maintenanceDetails = '';
if ($isInMaintenance) {
    $maintenanceDetails = file_get_contents($maintenanceFile);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Manutenção - Sistema de Vendas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        .maintenance-status {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .maintenance-active {
            background-color: #ffe5e5;
            border: 1px solid #ffcccc;
        }
        .maintenance-inactive {
            background-color: #e5ffe5;
            border: 1px solid #ccffcc;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Controle de Manutenção</h4>
                    </div>
                    <div class="card-body">
                        <!-- Current Status -->
                        <div class="maintenance-status <?php echo $isInMaintenance ? 'maintenance-active' : 'maintenance-inactive'; ?>">
                            <h5>
                                <i class="fas <?php echo $isInMaintenance ? 'fa-tools' : 'fa-check-circle'; ?>"></i>
                                Status Atual: 
                                <strong><?php echo $isInMaintenance ? 'Em Manutenção' : 'Sistema Operacional'; ?></strong>
                            </h5>
                            
                            <?php if ($isInMaintenance && $maintenanceDetails): ?>
                                <div class="mt-3">
                                    <strong>Detalhes:</strong>
                                    <pre class="mt-2"><?php echo htmlspecialchars($maintenanceDetails); ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isInMaintenance): ?>
                            <!-- Disable Maintenance Form -->
                            <form method="post" class="mb-4">
                                <input type="hidden" name="action" value="disable">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Deseja realmente desativar o modo de manutenção?')">
                                    <i class="fas fa-power-off"></i> Desativar Modo de Manutenção
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Enable Maintenance Form -->
                            <form method="post">
                                <input type="hidden" name="action" value="enable">
                                
                                <div class="mb-3">
                                    <label for="estimated_time" class="form-label">Previsão de Conclusão</label>
                                    <input type="text" class="form-control" id="estimated_time" name="estimated_time" 
                                           placeholder="Selecione a data e hora">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Motivo da Manutenção</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" 
                                              placeholder="Descreva o motivo da manutenção"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Deseja realmente ativar o modo de manutenção?')">
                                    <i class="fas fa-tools"></i> Ativar Modo de Manutenção
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Back Button -->
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <script>
        // Initialize datetime picker
        flatpickr("#estimated_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            locale: "pt",
            time_24hr: true
        });
    </script>
</body>
</html>
