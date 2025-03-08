<?php
/**
 * Error page for system errors
 */

// Get error details
$errorCode = $_GET['code'] ?? 500;
$errorMessage = $_GET['message'] ?? 'Um erro inesperado ocorreu.';

// Map error codes to messages
$errorMessages = [
    400 => 'Requisição inválida',
    401 => 'Não autorizado',
    403 => 'Acesso negado',
    404 => 'Página não encontrada',
    500 => 'Erro interno do servidor',
    503 => 'Serviço indisponível'
];

// Get friendly message
$friendlyMessage = $errorMessages[$errorCode] ?? $errorMessages[500];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro <?php echo $errorCode; ?> - Sistema de Vendas</title>
    
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
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .error-details {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .back-button {
            margin-top: 20px;
        }
        .icon-container {
            margin-bottom: 30px;
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="icon-container">
                <?php if ($errorCode == 404): ?>
                    <i class="fas fa-search error-icon"></i>
                <?php elseif ($errorCode == 403): ?>
                    <i class="fas fa-lock error-icon"></i>
                <?php elseif ($errorCode == 401): ?>
                    <i class="fas fa-user-lock error-icon"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle error-icon"></i>
                <?php endif; ?>
            </div>
            
            <div class="error-code">
                <?php echo $errorCode; ?>
            </div>
            
            <div class="error-message">
                <?php echo $friendlyMessage; ?>
            </div>
            
            <div class="error-details">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            
            <div class="back-button">
                <?php if ($errorCode == 401): ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Fazer Login
                    </a>
                <?php else: ?>
                    <a href="javascript:history.back()" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Página Inicial
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Log error to console in development mode
        <?php if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE): ?>
        console.error('Error <?php echo $errorCode; ?>:', <?php echo json_encode($errorMessage); ?>);
        <?php endif; ?>
        
        // Automatically redirect to login page if session expired
        <?php if ($errorCode == 401): ?>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
