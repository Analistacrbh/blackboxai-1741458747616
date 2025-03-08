<?php
/**
 * Maintenance page
 * To enable maintenance mode, create a file named 'maintenance.flag' in the root directory
 */

// Check if system is really in maintenance mode
if (!file_exists(__DIR__ . '/maintenance.flag')) {
    header('Location: index.php');
    exit();
}

// Get maintenance details if available
$maintenanceDetails = '';
if (file_exists(__DIR__ . '/maintenance.flag')) {
    $maintenanceDetails = file_get_contents(__DIR__ . '/maintenance.flag');
}

// Get estimated completion time
$estimatedTime = '';
if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $maintenanceDetails, $matches)) {
    $estimatedTime = $matches[0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Em Manutenção - Sistema de Vendas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .maintenance-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 90%;
        }
        .maintenance-icon {
            font-size: 64px;
            color: #ffc107;
            margin-bottom: 30px;
        }
        .maintenance-title {
            font-size: 32px;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        .maintenance-message {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .estimated-time {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
            margin-top: 10px;
        }
        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .refresh-button {
            margin-top: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fa-cog {
            animation: spin 4s linear infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-container">
            <div class="maintenance-icon">
                <i class="fas fa-cog"></i>
            </div>
            
            <div class="maintenance-title">
                Sistema em Manutenção
            </div>
            
            <div class="maintenance-message">
                Estamos realizando uma manutenção programada para melhorar nossos serviços.
                O sistema estará indisponível durante este período.
            </div>
            
            <?php if ($estimatedTime): ?>
            <div class="estimated-time">
                <strong>Previsão de Conclusão:</strong>
                <div class="countdown" id="countdown">
                    <?php echo date('d/m/Y H:i', strtotime($estimatedTime)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($maintenanceDetails): ?>
            <div class="alert alert-info">
                <?php echo nl2br(htmlspecialchars($maintenanceDetails)); ?>
            </div>
            <?php endif; ?>
            
            <div class="contact-info">
                <h5>Precisa de Ajuda?</h5>
                <p>Entre em contato com nosso suporte:</p>
                <p>
                    <i class="fas fa-envelope"></i> suporte@exemplo.com<br>
                    <i class="fas fa-phone"></i> (00) 0000-0000
                </p>
            </div>
            
            <div class="refresh-button">
                <button class="btn btn-primary" onclick="checkStatus()">
                    <i class="fas fa-sync-alt"></i> Verificar Status
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to check if maintenance is over
        function checkStatus() {
            fetch('check_maintenance.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.maintenance) {
                        window.location.reload();
                    } else {
                        alert('O sistema ainda está em manutenção. Por favor, tente novamente mais tarde.');
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                });
        }
        
        // Update countdown if estimated time is set
        <?php if ($estimatedTime): ?>
        function updateCountdown() {
            const estimatedTime = new Date('<?php echo $estimatedTime; ?>').getTime();
            const now = new Date().getTime();
            const distance = estimatedTime - now;
            
            if (distance < 0) {
                document.getElementById('countdown').innerHTML = 'Em andamento...';
                return;
            }
            
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            
            document.getElementById('countdown').innerHTML = 
                hours + 'h ' + minutes + 'm restantes';
        }
        
        // Update countdown every minute
        updateCountdown();
        setInterval(updateCountdown, 60000);
        <?php endif; ?>
        
        // Check status periodically (every 5 minutes)
        setInterval(checkStatus, 300000);
    </script>
</body>
</html>
