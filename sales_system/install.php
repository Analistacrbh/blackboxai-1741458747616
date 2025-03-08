<?php
/**
 * Installation Script for Sales System
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 or higher is required. Current version: ' . PHP_VERSION);
}

// Check required PHP extensions
$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'mbstring',
    'json',
    'gd',
    'zip'
];

$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    die('Required PHP extensions missing: ' . implode(', ', $missingExtensions));
}

// Function to create directory if not exists
function createDirectory($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0777, true);
    }
    return true;
}

// Function to make directory writable
function makeWritable($path) {
    return chmod($path, 0777);
}

// Function to check database connection
function checkDatabase($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Try to create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` 
                   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Installation status
$status = [
    'directories' => true,
    'database' => false,
    'config' => false
];

$messages = [];

// Check and create required directories
$directories = [
    'logs',
    'backups',
    'uploads',
    'cache'
];

foreach ($directories as $dir) {
    if (!createDirectory(__DIR__ . '/' . $dir)) {
        $status['directories'] = false;
        $messages[] = "Failed to create directory: $dir";
    }
    if (!makeWritable(__DIR__ . '/' . $dir)) {
        $status['directories'] = false;
        $messages[] = "Failed to make directory writable: $dir";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';
    
    // Check database connection
    if (checkDatabase($dbHost, $dbName, $dbUser, $dbPass)) {
        $status['database'] = true;
        
        // Create database configuration file
        $configContent = file_get_contents(__DIR__ . '/config/database.example.php');
        $configContent = str_replace(
            ['your_database_name', 'your_username', 'your_password'],
            [$dbName, $dbUser, $dbPass],
            $configContent
        );
        
        if (file_put_contents(__DIR__ . '/config/database.php', $configContent)) {
            $status['config'] = true;
            
            // Import database schema
            try {
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", 
                              $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $schema = file_get_contents(__DIR__ . '/db/schema.sql');
                $pdo->exec($schema);
                
                $messages[] = "Database schema imported successfully.";
            } catch (PDOException $e) {
                $messages[] = "Error importing database schema: " . $e->getMessage();
            }
        } else {
            $messages[] = "Failed to create database configuration file.";
        }
    } else {
        $messages[] = "Failed to connect to database. Please check your credentials.";
    }
}

// Installation complete check
$installationComplete = $status['directories'] && 
                       $status['database'] && 
                       $status['config'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Vendas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Instalação do Sistema de Vendas</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-info">
                                <ul class="mb-0">
                                    <?php foreach ($messages as $message): ?>
                                        <li><?php echo htmlspecialchars($message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($installationComplete): ?>
                            <div class="alert alert-success">
                                <h5>Instalação Concluída!</h5>
                                <p>O sistema foi instalado com sucesso. Por favor:</p>
                                <ol>
                                    <li>Delete este arquivo de instalação (install.php)</li>
                                    <li>Acesse o sistema usando as credenciais padrão:
                                        <ul>
                                            <li>Usuário: admin</li>
                                            <li>Senha: admin123</li>
                                        </ul>
                                    </li>
                                    <li>Altere a senha do administrador após o primeiro acesso</li>
                                </ol>
                                <a href="login.php" class="btn btn-primary">Acessar o Sistema</a>
                            </div>
                        <?php else: ?>
                            <form method="post" class="needs-validation" novalidate>
                                <h5 class="mb-3">Configuração do Banco de Dados</h5>
                                
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" 
                                           value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Nome do Banco</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Usuário</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                           required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    Instalar Sistema
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Status da Instalação</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Diretórios
                                <?php if ($status['directories']): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Erro</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Banco de Dados
                                <?php if ($status['database']): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Configuração
                                <?php if ($status['config']): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
